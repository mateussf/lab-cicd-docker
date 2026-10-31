import os

import pymysql

TEMPO_LIMITE_SEGUNDOS = 5
PORTA_PADRAO_MYSQL = '3306'


# Os dados vêm do ambiente para que a mesma imagem rode em qualquer lugar (dev, lab, empresa)
def criar_conexao() -> pymysql.connections.Connection:
    return pymysql.connect(
        host=os.getenv('DB_HOST'),
        port=int(os.getenv('DB_PORT', PORTA_PADRAO_MYSQL)),
        database=os.getenv('DB_NAME'),
        user=os.getenv('DB_USER'),
        password=os.getenv('DB_PASSWORD', ''),
        connect_timeout=TEMPO_LIMITE_SEGUNDOS,
    )
