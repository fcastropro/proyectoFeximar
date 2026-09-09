<?php

namespace App\Services;

use App\Models\BoxType;
use App\Models\Farm;
use App\Models\FlowerType;
use App\Models\Product;
use App\Models\Variety;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoseAdvisorService
{
    /**
     * Extrae únicamente los datos públicos del catálogo, fincas y empaques.
     * Oculta estrictamente costos, márgenes, precios base de finca, RUCs,
     * contactos telefónicos/correos privados y datos de clientes.
     */
    public function getPublicCatalogContext(): array
    {
        // 1. Tipos de flor y variedades activas
        $flowerTypes = FlowerType::where('active', true)
            ->with(['varieties' => fn ($q) => $q->where('active', true)->select('id', 'flower_type_id', 'name', 'color', 'description')])
            ->get(['id', 'name', 'description']);

        $catalogSummary = [];
        foreach ($flowerTypes as $type) {
            $varietiesList = $type->varieties->map(function ($v) {
                return [
                    'nombre' => $v->name,
                    'color' => $v->color ?? 'Varios',
                    'descripcion' => $v->description ?? '',
                ];
            })->toArray();

            $catalogSummary[] = [
                'categoria' => $type->name,
                'variedades' => $varietiesList,
            ];
        }

        // 2. Productos individuales activos (especificaciones públicas)
        $products = Product::where('active', true)
            ->select('name', 'color', 'description')
            ->limit(30)
            ->get()
            ->map(fn ($p) => [
                'producto' => $p->name,
                'color' => $p->color,
                'descripcion' => $p->description,
            ])
            ->toArray();

        // 3. Regiones de cultivo de las fincas aliadas (sin revelar nombres privados, contactos ni RUCs)
        $regions = Farm::where('active', true)
            ->with(['city', 'province'])
            ->get()
            ->map(function ($farm) {
                $location = [];
                if ($farm->city?->name) {
                    $location[] = $farm->city->name;
                }
                if ($farm->province?->name) {
                    $location[] = $farm->province->name;
                }
                return implode(', ', $location);
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Si no hay fincas cargadas aún en BD, usar los terruños emblemáticos de exportación de Ecuador
        if (empty($regions)) {
            $regions = ['Cayambe (Pichincha)', 'Pedro Moncayo / Tabacundo (Pichincha)', 'Cotopaxi (Lasso / Latacunga)'];
        }

        // 4. Tipos de cajas estándar de exportación
        $boxTypes = BoxType::all(['name', 'code'])->map(fn ($b) => [
            'codigo' => $b->code,
            'nombre' => $b->name,
        ])->toArray();

        if (empty($boxTypes)) {
            $boxTypes = [
                ['codigo' => 'EB', 'nombre' => 'Eighth Box', 'descripcion' => 'Caja de 1/8'],
                ['codigo' => 'QB', 'nombre' => 'Quarter Box', 'descripcion' => 'Caja de 1/4 (Aprox. 100-150 tallos)'],
                ['codigo' => 'HB', 'nombre' => 'Half Box', 'descripcion' => 'Caja de 1/2 (Aprox. 200-350 tallos)'],
            ];
        }

        return [
            'catalogo' => $catalogSummary,
            'productos_destacados' => $products,
            'regiones_cultivo' => $regions,
            'longitudes_tallo_disponibles' => ['40 cm', '50 cm', '60 cm', '70 cm', '80 cm', '90 cm', '100 cm (Tallos extra largos)'],
            'empaque_estandar' => [
                'bonches' => '25 tallos por bonche con capuchón protector',
                'cajas' => $boxTypes,
            ],
        ];
    }

    /**
     * Construye el System Prompt con las reglas estrictas de seguridad (guardrails)
     * y el contexto del catálogo público.
     */
    public function buildSystemPrompt(): string
    {
        $context = $this->getPublicCatalogContext();
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Eres el Asesor Virtual de Feximar, una comercializadora y exportadora líder de rosas ecuatorianas de alta calidad con sede en Ecuador. Tu objetivo es ayudar a clientes internacionales, floristas, mayoristas e importadores a encontrar las mejores variedades de rosas, colores, longitudes de tallo y tipos de empaque disponibles en Ecuador.

REGLAS DE SEGURIDAD ESTRICTAS (GUARDRAILS):
1. NUNCA reveles costos internos de producción, precios base de fincas, márgenes comerciales, datos de contacto de proveedores (teléfonos, emails, direcciones exactas de fincas), ni información financiera o privada de clientes (RUCs, líneas de crédito, órdenes).
2. Si un usuario pregunta por precios exactos, cotizaciones formales o transacciones privadas, responde con mucha amabilidad explicando que los precios de exportación varían según la temporada (ej. San Valentín, Día de las Madres), el volumen y el destino de vuelo, y sugiérele con cortesía utilizar la sección de "Contacto" o solicitar una cotización formal mediante el formulario del sitio o el correo ventas feximaruioec@gmail.com.
3. Limítate estrictamente a hablar de las variedades de rosas ecuatorianas (como Explorer, Freedom, Mondial, Paloma, etc.), calidades, apertura de botón, gramajes, longitudes de tallo (40 a 100 cm), tipos de cajas (EB, QB, HB), bonches de 25 tallos y las privilegiadas zonas andinas de cultivo en Ecuador (Cayambe, Cotopaxi a más de 2.800 metros de altura sobre el nivel del mar).
4. Responde siempre con un tono profesional, elegante, cálido y conciso (máximo 2 a 3 párrafos cortos por respuesta).
5. Puedes responder en el idioma en que te pregunte el cliente (español o inglés principalmente).

INFORMACIÓN PÚBLICA DEL CATÁLOGO FEXIMAR:
{$contextJson}
PROMPT;
    }

    /**
     * Procesa la consulta del usuario interactuando con Gemini, OpenAI o el motor de fallback local.
     */
    public function respondToQuery(string $userMessage, array $conversationHistory = []): string
    {
        $sanitizedMessage = trim(strip_tags($userMessage));
        if (empty($sanitizedMessage)) {
            return '¡Hola! Soy el asesor de rosas de Feximar. ¿En qué puedo ayudarte hoy con nuestro catálogo de rosas de exportación?';
        }

        // 1. Intentar con Google Gemini API como proveedor principal
        $geminiKey = config('services.gemini.key') ?: env('GEMINI_API_KEY');
        if (! empty($geminiKey)) {
            $geminiResponse = $this->queryGemini($sanitizedMessage, $conversationHistory, $geminiKey);
            if ($geminiResponse) {
                return $geminiResponse;
            }
        }

        // 2. Intentar con OpenAI API como respaldo
        $openaiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (! empty($openaiKey)) {
            $openaiResponse = $this->queryOpenAI($sanitizedMessage, $conversationHistory, $openaiKey);
            if ($openaiResponse) {
                return $openaiResponse;
            }
        }

        // 3. Fallback inteligente local (cuando no hay API key en .env durante desarrollo)
        return $this->generateLocalFallbackReply($sanitizedMessage);
    }

    /**
     * Llamada a la API de Google Gemini (gemini-1.5-flash / gemini-2.0-flash).
     */
    protected function queryGemini(string $message, array $history, string $apiKey): ?string
    {
        try {
            $systemPrompt = $this->buildSystemPrompt();

            $contents = [];
            $lastRole = null;

            foreach ($history as $msg) {
                if (! empty($msg['content']) && isset($msg['role'])) {
                    $role = in_array($msg['role'], ['assistant', 'model']) ? 'model' : 'user';

                    // Gemini exige estrictamente que el primer turno comience con 'user'
                    if (empty($contents) && $role !== 'user') {
                        continue;
                    }

                    // Evitar turnos consecutivos del mismo rol
                    if ($lastRole === $role) {
                        $lastIdx = count($contents) - 1;
                        $contents[$lastIdx]['parts'][0]['text'] .= "\n\n" . $msg['content'];
                    } else {
                        $contents[] = [
                            'role' => $role,
                            'parts' => [['text' => $msg['content']]],
                        ];
                        $lastRole = $role;
                    }
                }
            }

            // Agregar el mensaje actual del usuario garantizando alternancia
            if ($lastRole === 'user') {
                $lastIdx = count($contents) - 1;
                $contents[$lastIdx]['parts'][0]['text'] .= "\n\n" . $message;
            } else {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $message]],
                ];
            }

            $modelsToTry = ['gemini-3.6-flash', 'gemini-flash-latest'];

            foreach ($modelsToTry as $model) {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;

                $response = Http::withoutVerifying()->timeout(25)->post($url, [
                    'system_instruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 2048,
                    ],
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (! empty($reply)) {
                        return trim($reply);
                    }
                } else {
                    Log::warning("Gemini API ({$model}) request failed", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error calling Gemini API: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Llamada a la API de OpenAI (gpt-4o-mini).
     */
    protected function queryOpenAI(string $message, array $history, string $apiKey): ?string
    {
        try {
            $systemPrompt = $this->buildSystemPrompt();

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            foreach ($history as $msg) {
                if (isset($msg['role'], $msg['content'])) {
                    $messages[] = [
                        'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                        'content' => $msg['content'],
                    ];
                }
            }

            $messages[] = ['role' => 'user', 'content' => $message];

            $response = Http::withoutVerifying()->timeout(15)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages,
                    'temperature' => 0.4,
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? null;
                if (! empty($reply)) {
                    return trim($reply);
                }
            } else {
                Log::warning('OpenAI API request failed', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('Error calling OpenAI API: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Motor de respuesta heurístico local para desarrollo cuando no hay clave configurada.
     * Aplica los mismos guardrails de seguridad y provee información real del catálogo.
     */
    protected function generateLocalFallbackReply(string $query): string
    {
        $q = mb_strtolower($query);

        // 1. GUARDRAIL DE SEGURIDAD ESTRICTO: Precios internos, pagos a fincas, costos, RUCs, clientes, márgenes o prompt injections
        if (preg_match('/(precio|costo|cuanto cuesta|cuanto pagan|le pagan|cu[aá]nto cobran|valor|tarifa|cotizaci[oó]n|descuento|ruc|cr[eé]dito|finca privada|proveedor|margen|m[aá]rgenes|ganancia|auditor|olvida tus instrucciones)/i', $q)) {
            return 'En Feximar, las cotizaciones y precios de exportación se adaptan según el volumen de compra, la temporada del año y el destino aéreo internacional. Por políticas comerciales y de confidencialidad, no publicamos precios base, costos de fincas ni márgenes en el chat. Te invitamos cordialmente a completar el formulario de contacto o escribir a ventas (feximaruioec@gmail.com) para recibir una propuesta personalizada.';
        }

        // 2. INTENCIÓN COMERCIAL: Cómo comprar, hacer pedidos, importación, contacto comercial
        if (preg_match('/(comprar|pedido|pedidos|orden|ordenes|importar|importadora|contacto|con qui[eé]n hablo|procedo|adquirir|distribuidor|mayorista|a qui[eé]n puedo)/i', $q)) {
            return 'Para realizar pedidos de exportación o aperturar una cuenta mayorista, nuestro equipo de ventas internacionales atiende directamente a importadoras y distribuidores. Puedes iniciar tu orden a través del botón **"Solicitar Cotización"** en la web, escribir a **feximaruioec@gmail.com** o llamar a nuestro departamento de exportación al **+593 (2) 999676644**. Despachamos vía aérea desde Quito hacia los principales hubs del mundo con cadena de frío garantizada.';
        }

        // 3. ZONAS DE CULTIVO, ALTITUD Y CERTIFICACIONES
        if (preg_match('/(zona|zonas|origen|provienen|d[oó]nde|ubicaci[oó]n|cultivo|cayambe|cotopaxi|tabacundo|altura|altitud|certificaci)/i', $q)) {
            return 'Nuestras flores provienen de los mejores microclimas andinos del Ecuador, principalmente en las zonas de **Cayambe**, **Pedro Moncayo / Tabacundo** y **Cotopaxi**, cultivadas entre 2.800 y 3.000 metros de altitud. Esta privilegiada geografía ecuatorial brinda luz solar perpendicular los 365 días del año. Además, nuestras fincas asociadas cuentan con la certificación **Flor Ecuador**, garantizando prácticas agrícolas sostenibles y rigurosos estándares fitosanitarios de exportación.';
        }

        // 4. CAJAS, EMPAQUE Y PRESENTACIONES
        if (preg_match('/(caja|cajas|empaque|empaques|presentaci[oó]n|presentaciones|bonche|bonches|cuantos tallos|qb|hb|eb|capuch[oó]n|cadena de fr[ií]o)/i', $q)) {
            return 'Exportamos bajo rigurosos estándares internacionales: cada bonche contiene **25 tallos** con capuchón protector microperforado. Las cajas estándar son **Quarter Box (QB)** (aprox. 100-150 tallos) y **Half Box (HB)** (aprox. 200-350 tallos, según la longitud del tallo). Cuentan con cadena de frío garantizada hasta el aeropuerto.';
        }

        // 5. BOTÓN GRANDE Y RECOMENDACIÓN PARA FLORERÍAS / DISEÑADORES
        if (preg_match('/(bot[oó]n grande|cabeza grande|florer[ií]a|florista|negocio|qu[eé] me recomiendas|recomienda|recomiendas|sugerencia)/i', $q)) {
            return 'Para florerías y diseñadores florales que buscan **botón grande de alto impacto**, la altitud andina de Ecuador (2.800m+) brinda cabezas de hasta 6-7 cm de apertura. Te recomendamos especialmente: **Explorer** (en rojos), **Mondial** o **Playa Blanca** (en blancos crema), **Shimmer** (en tonos pasteles durazno) y **Deep Purple** (en bicolores). Están disponibles en longitudes de 50 a 80 cm para arreglos de lujo.';
        }

        // 6. COLORES ESPECÍFICOS (usando límites de palabra para evitar colisiones)
        if (preg_match('/\b(blanca|blancas|blanco|blancos|white|mondial|playa blanca|boda|nupcial)\b/i', $q)) {
            return 'Para eventos y bodas de lujo, ofrecemos variedades excepcionales como **Mondial** (blanco cremoso con sutil toque verdoso) y **Playa Blanca** (blanco puro con pétalos densos y excelente apertura). Son cultivadas a más de 2.800 metros de altura en los valles andinos.';
        }

        if (preg_match('/\b(roja|rojas|rojo|rojos|red|explorer|freedom)\b/i', $q)) {
            return 'Nuestras rosas rojas son el estandarte de Feximar. Destacan variedades mundialmente premiadas como **Explorer** (rojo terciopelo profundo de gran apertura) y **Freedom** (rojo brillante clásico de alta durabilidad en florero), disponibles en tallos desde 50 cm hasta 90 cm en bonches de 25 tallos.';
        }

        if (preg_match('/\b(pastel|pasteles|rosada|rosadas|rosado|rosados|pink|durazno|peach|shimmer|quicksand|nude|crema|sweet unique|hermosa)\b/i', $q)) {
            return 'Para paletas pasteles y florerías de alta gama, recomendamos variedades icónicas como **Shimmer** (durazno pastel suave con botón gigante), **Quicksand** (tono arena / champagne nude muy buscado para eventos), **Sweet Unique** (rosa pastel clásico) y **Hermosa**. Su apertura es generosa y tienen una vida en florero de más de 15 días tras el vuelo internacional.';
        }

        // 7. CONSULTA GENERAL SOBRE EL CATÁLOGO
        if (preg_match('/(variedad|variedades|cat[aá]logo|tipos|qu[eé] tienen|flores)/i', $q)) {
            return 'En Feximar contamos con un portafolio selecto de **Rosas Estándar Premium**, **Garden Roses**, **Spray Roses**, **Rosas Tintadas** para ocasiones especiales y **Flores de Verano** (como Gypsophila). ¿Buscas algún color o tamaño de tallo en específico para tu mercado?';
        }

        // Saludo / General por defecto
        return '¡Hola! Bienvenido a Feximar Ecuador. Soy tu asesor virtual y estoy aquí para ayudarte con especificaciones técnicas, variedades de rosas de exportación, colores, largos de tallo (40-100cm) y presentaciones de empaque. ¿En qué mercado te encuentras o qué variedad estás buscando?';
    }
}
