<?php

namespace App\Http\Controllers;

use App\Services\RoseAdvisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIAdvisorController extends Controller
{
    public function __construct(
        protected RoseAdvisorService $advisorService
    ) {}

    /**
     * Procesa la consulta del usuario enviada desde el widget flotante del Home.
     */
    public function query(Request $request): JsonResponse
    {
        $rawMessage = $request->input('message');
        if (! is_string($rawMessage) || mb_strlen(trim($rawMessage)) < 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'El campo message es obligatorio y debe tener al menos 2 caracteres.',
                'errors' => [
                    'message' => ['El campo message es obligatorio y debe tener al menos 2 caracteres.'],
                ],
                'reply' => 'Por favor escribe una consulta con al menos dos caracteres para poder orientarte.',
            ], 422);
        }

        $userMessage = mb_substr(trim($rawMessage), 0, 2000);

        // Sanitizar historial sin riesgo de fallos por longitud de respuestas anteriores
        $rawHistory = $request->input('history', []);
        $history = [];
        if (is_array($rawHistory)) {
            foreach (array_slice($rawHistory, -12) as $item) {
                if (is_array($item) && ! empty($item['content']) && is_string($item['content'])) {
                    $role = in_array($item['role'] ?? '', ['assistant', 'model']) ? 'assistant' : 'user';
                    $history[] = [
                        'role' => $role,
                        'content' => mb_substr(trim($item['content']), 0, 4000),
                    ];
                }
            }
        }

        try {
            $reply = $this->advisorService->respondToQuery($userMessage, $history);

            return response()->json([
                'status' => 'success',
                'reply' => $reply,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in AIAdvisorController: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'reply' => 'En este momento nuestro asesor virtual se encuentra ocupado. Por favor intenta de nuevo en unos momentos o contáctanos por nuestro formulario.',
            ], 500);
        }
    }
}
