<?php

namespace Tests\Feature\Diagnostico;

use App\Services\Diagnostico\DiagnosticoService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class DiagnosticoServiceTest extends TestCase
{
    public function test_deve_informar_versao_quando_conecta(): void
    {
        DB::shouldReceive('selectOne')->once()->andReturn((object) ['versao' => '8.4.0']);

        $resultado = (new DiagnosticoService)->testarBanco();

        $this->assertStringContainsString('8.4.0', $resultado);
    }

    public function test_deve_informar_falha_quando_banco_indisponivel(): void
    {
        $erroDeConexao = new QueryException('mysql', 'select version()', [], new PDOException('Connection refused'));
        DB::shouldReceive('selectOne')->once()->andThrow($erroDeConexao);

        $resultado = (new DiagnosticoService)->testarBanco();

        $this->assertSame('Falha na conexão: Connection refused', $resultado);
    }

    public function test_deve_mostrar_versao_da_imagem_e_dados_da_requisicao(): void
    {
        config(['app.versao' => '42']);
        DB::shouldReceive('selectOne')->andReturn((object) ['versao' => '8.4.0']);

        $informacoes = (new DiagnosticoService)->montarInformacoes('app3.localhost', '172.24.0.4', '172.24.0.9');

        $this->assertSame('42', $informacoes['Versão da imagem (build do Jenkins)']);
        $this->assertSame('app3.localhost', $informacoes['Cabeçalho Host recebido']);
        $this->assertSame('172.24.0.4', $informacoes['IP de quem chamou']);
    }
}
