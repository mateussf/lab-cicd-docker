<?php

require __DIR__ . '/config/conexao.php';

const NOME_APP = 'App 2';

function testarBanco(): string
{
    try {
        $versao = criarConexao()->query('SELECT VERSION()')->fetchColumn();
        return "Conectado em " . getenv('DB_HOST') . ':' . getenv('DB_PORT') . " — versão $versao";
    } catch (PDOException $erro) {
        return 'Falha na conexão: ' . $erro->getMessage();
    }
}

$informacoes = [
    'Hostname do container' => gethostname(),
    'IP deste container (SERVER_ADDR)' => $_SERVER['SERVER_ADDR'] ?? '-',
    'IP de quem chamou (REMOTE_ADDR)' => $_SERVER['REMOTE_ADDR'] ?? '-',
    'Cabeçalho Host recebido' => $_SERVER['HTTP_HOST'] ?? '-',
    'Banco de dados' => testarBanco(),
];
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title><?= NOME_APP ?></title>
</head>
<body>
    <h1><?= NOME_APP ?></h1>
    <table border="1" cellpadding="6">
        <?php foreach ($informacoes as $rotulo => $valor): ?>
            <tr>
                <th align="left"><?= htmlspecialchars($rotulo) ?></th>
                <td><?= htmlspecialchars($valor) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p>Se "IP de quem chamou" for o IP do proxy, o navegador nunca falou direto com este container.</p>
</body>
</html>
