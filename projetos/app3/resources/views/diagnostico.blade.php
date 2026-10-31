<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>App 3 (Laravel)</title>
</head>
<body>
    <h1>App 3 (Laravel)</h1>
    <table border="1" cellpadding="6">
        @foreach ($informacoes as $rotulo => $valor)
            <tr>
                <th align="left">{{ $rotulo }}</th>
                <td>{{ $valor }}</td>
            </tr>
        @endforeach
    </table>
    <p>Desenvolvido com Sail, entregue pelo pipeline: GitLab → Jenkins → registry → deploy.</p>
</body>
</html>
