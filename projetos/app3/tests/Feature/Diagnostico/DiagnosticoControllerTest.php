<?php

namespace Tests\Feature\Diagnostico;

use App\Services\Diagnostico\DiagnosticoService;
use Mockery\MockInterface;
use Tests\TestCase;

class DiagnosticoControllerTest extends TestCase
{
    public function test_deve_exibir_pagina_de_diagnostico(): void
    {
        $this->mock(DiagnosticoService::class, function (MockInterface $servico) {
            $servico->shouldReceive('montarInformacoes')->once()->andReturn(['Banco de dados' => 'Conectado']);
        });

        $this->get('/diagnostico')
            ->assertOk()
            ->assertSee('Banco de dados')
            ->assertSee('Conectado');
    }
}
