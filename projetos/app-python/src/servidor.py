import html
import os
import socket
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

import pymysql

from src.config.conexao import criar_conexao

PORTA = 8000


def testar_banco() -> str:
    try:
        with criar_conexao() as conexao, conexao.cursor() as cursor:
            cursor.execute('SELECT VERSION()')
            versao = cursor.fetchone()[0]
    except pymysql.MySQLError as erro:
        return f'Falha na conexão: {erro}'
    return f"Conectado em {os.getenv('DB_HOST')}:{os.getenv('DB_PORT')} — versão {versao}"


def montar_informacoes(host_recebido: str, ip_cliente: str, ip_servidor: str) -> dict:
    return {
        'Versão da imagem (build do Jenkins)': os.getenv('APP_VERSAO', 'dev'),
        'Hostname do container': socket.gethostname(),
        'IP deste container': ip_servidor,
        'IP de quem chamou': ip_cliente,
        'Cabeçalho Host recebido': host_recebido,
        'Banco de dados': testar_banco(),
    }


def renderizar_pagina(informacoes: dict) -> str:
    linhas = ''.join(
        f'<tr><th align="left">{html.escape(rotulo)}</th><td>{html.escape(valor)}</td></tr>'
        for rotulo, valor in informacoes.items()
    )
    return (
        '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><title>App Python</title></head>'
        f'<body><h1>App Python</h1><table border="1" cellpadding="6">{linhas}</table>'
        '<p>Entregue pelo pipeline: GitLab → Jenkins → registry → deploy.</p></body></html>'
    )


class Requisicao(BaseHTTPRequestHandler):
    def do_GET(self):
        informacoes = montar_informacoes(
            self.headers.get('Host', '-'),
            self.client_address[0],
            self.connection.getsockname()[0],
        )
        corpo = renderizar_pagina(informacoes).encode('utf-8')
        self.send_response(200)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.send_header('Content-Length', str(len(corpo)))
        self.end_headers()
        self.wfile.write(corpo)


if __name__ == '__main__':
    print(f'Escutando na porta {PORTA}', flush=True)
    ThreadingHTTPServer(('0.0.0.0', PORTA), Requisicao).serve_forever()
