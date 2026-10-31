import os
import unittest
from unittest.mock import patch

import pymysql

from src.servidor import montar_informacoes, testar_banco


class TestarBancoTest(unittest.TestCase):
    @patch.dict(os.environ, {'DB_HOST': 'banco.teste', 'DB_PORT': '3306'})
    @patch('src.servidor.criar_conexao')
    def test_deve_informar_versao_quando_conecta(self, criar_conexao_mock):
        conexao = criar_conexao_mock.return_value.__enter__.return_value
        cursor = conexao.cursor.return_value.__enter__.return_value
        cursor.fetchone.return_value = ('8.4.0',)

        resultado = testar_banco()

        self.assertIn('banco.teste:3306', resultado)
        self.assertIn('8.4.0', resultado)

    @patch('src.servidor.criar_conexao', side_effect=pymysql.err.OperationalError(2003, 'Sem rota'))
    def test_deve_informar_falha_quando_banco_indisponivel(self, _criar_conexao_mock):
        resultado = testar_banco()

        self.assertTrue(resultado.startswith('Falha na conexão'))


class MontarInformacoesTest(unittest.TestCase):
    @patch.dict(os.environ, {'APP_VERSAO': '42'})
    @patch('src.servidor.testar_banco', return_value='ok')
    def test_deve_mostrar_versao_da_imagem_e_dados_da_requisicao(self, _testar_banco_mock):
        informacoes = montar_informacoes('python.localhost', '172.24.0.4', '172.24.0.9')

        self.assertEqual(informacoes['Versão da imagem (build do Jenkins)'], '42')
        self.assertEqual(informacoes['Cabeçalho Host recebido'], 'python.localhost')
        self.assertEqual(informacoes['IP de quem chamou'], '172.24.0.4')


if __name__ == '__main__':
    unittest.main()
