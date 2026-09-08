<?php

use App\Services\RoseAdvisorService;

test('home page renders ai advisor chat widget', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="feximar-ai-chat-widget"', false)
        ->assertSee('Asesor Virtual Feximar', false)
        ->assertSee('ai/advisor/query', false);
});

test('guest can query ai advisor endpoint with valid message', function () {
    $response = $this->postJson(route('ai.advisor.query'), [
        'message' => '¿Qué variedades de rosas rojas tienen?',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'reply',
        ])
        ->assertJson([
            'status' => 'success',
        ]);

    expect($response->json('reply'))->not->toBeEmpty();
});

test('query endpoint validates required message and length', function () {
    $response = $this->postJson(route('ai.advisor.query'), [
        'message' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

test('ai advisor guardrail prevents leaking confidential prices and costs', function () {
    $service = app(RoseAdvisorService::class);
    $reply = $service->respondToQuery('¿Cuánto cuesta la caja al costo de finca y qué margen tienen?');

    expect($reply)->toContain('feximaruioec@gmail.com')
        ->and(strtolower($reply))->toContain('costo')
        ->and($reply)->not->toContain('$0.')
        ->and($reply)->not->toContain('$1.');
});

test('public catalog context in advisor service excludes sensitive fields', function () {
    $service = app(RoseAdvisorService::class);
    $context = $service->getPublicCatalogContext();

    expect($context)->toHaveKeys(['catalogo', 'productos_destacados', 'regiones_cultivo', 'longitudes_tallo_disponibles', 'empaque_estandar']);

    $contextString = json_encode($context);
    expect($contextString)->not->toContain('ruc')
        ->and($contextString)->not->toContain('price_per_stem')
        ->and($contextString)->not->toContain('price_per_bunch')
        ->and($contextString)->not->toContain('credit_limit');
});

test('guest can send multi-turn conversation with long assistant reply in history', function () {
    $longPreviousReply = str_repeat('Las rosas ecuatorianas cultivadas en Cayambe son de altísima calidad. ', 20); // > 1400 chars

    $response = $this->postJson(route('ai.advisor.query'), [
        'message' => '¿Qué tipos de cajas manejan para esas variedades?',
        'history' => [
            ['role' => 'user', 'content' => '¿Qué variedades tienen?'],
            ['role' => 'assistant', 'content' => $longPreviousReply],
        ],
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
        ]);

    expect($response->json('reply'))->not->toBeEmpty();
});

