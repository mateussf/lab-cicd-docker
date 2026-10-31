<?php

namespace App\Services\Diagnostico;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DiagnosticoService
{
    public function montarInformacoes(string $hostRecebido, string $ipCliente, string $ipServidor): array
    {
        return [
            'Versão da imagem (build do Jenkins)' => config('app.versao'),
            'Hostname do container' => gethostname(),
            'IP deste container' => $ipServidor,
            'IP de quem chamou' => $ipCliente,
            'Cabeçalho Host recebido' => $hostRecebido,
            'Banco de dados' => $this->testarBanco(),
        ];
    }

    public function testarBanco(): string
    {
        try {
            $versao = DB::selectOne('select version() as versao')->versao;
        } catch (QueryException $erro) {
            return 'Falha na conexão: '.$erro->getPrevious()?->getMessage();
        }

        $conexao = config('database.connections.'.config('database.default'));

        return "Conectado em {$conexao['host']}:{$conexao['port']} — versão {$versao}";
    }
}
