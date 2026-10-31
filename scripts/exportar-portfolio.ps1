# Monta o repositório de portfólio (GitHub) a partir do lab.
# No lab, cada app tem o próprio repositório no GitLab (é o que o pipeline usa).
# Para o portfólio, tudo vira um repositório só, sem segredos e sem dependências baixadas.
#
# Uso: powershell -ExecutionPolicy Bypass -File scripts\exportar-portfolio.ps1 [-Destino D:\portfolio\lab-cicd-docker]

param(
    [string]$Destino = 'D:\portfolio\lab-cicd-docker',
    [string]$OrigemApp3 = '\\wsl.localhost\Ubuntu\home\mateus\projetos\app3'
)

$ErrorActionPreference = 'Stop'
$lab = Split-Path $PSScriptRoot -Parent

# Pastas e arquivos que nunca vão para o portfólio
$PASTAS_IGNORADAS = @('.git', 'vendor', 'node_modules', '__pycache__', '.claude')
$ARQUIVOS_IGNORADOS = @('.env', '*.log')

function Copiar-Pasta($origem, $destinoPasta) {
    # /MIR espelha; as pastas excluídas (inclusive o .git do destino) não são apagadas
    robocopy $origem $destinoPasta /MIR /NFL /NDL /NJH /NJS /NP /XD @PASTAS_IGNORADAS /XF @ARQUIVOS_IGNORADOS | Out-Null
    # robocopy retorna 0-7 para sucesso
    if ($LASTEXITCODE -ge 8) { throw "Falha ao copiar $origem (robocopy $LASTEXITCODE)" }
}

Write-Host "Copiando o lab de $lab..."
Copiar-Pasta $lab $Destino

Write-Host "Copiando o app3 de $OrigemApp3..."
Copiar-Pasta $OrigemApp3 (Join-Path $Destino 'projetos\app3')

$segredos = Get-ChildItem $Destino -Recurse -Force -File -Filter '.env' |
    Where-Object { $_.FullName -notmatch '\\\.git\\' }
if ($segredos) { throw "Arquivo .env encontrado no destino: $($segredos.FullName -join ', ')" }

Write-Host "Portfólio exportado em $Destino"
