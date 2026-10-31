<?php

namespace App\Http\Controllers\Diagnostico;

use App\Http\Controllers\Controller;
use App\Services\Diagnostico\DiagnosticoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticoController extends Controller
{
    public function __construct(private DiagnosticoService $diagnosticoService) {}

    public function exibir(Request $requisicao): View
    {
        $informacoes = $this->diagnosticoService->montarInformacoes(
            $requisicao->getHost(),
            $requisicao->ip(),
            $requisicao->server('SERVER_ADDR', '-'),
        );

        return view('diagnostico', ['informacoes' => $informacoes]);
    }
}
