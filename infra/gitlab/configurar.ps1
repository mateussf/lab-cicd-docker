# Configura o GitLab do lab de uma vez (pode rodar de novo sem problema).
# Faz por script o que, numa empresa, um admin faria pela interface:
#   1. cria um token pessoal para o root (usado pelo Jenkins e pelo git push)
#   2. libera webhooks para a rede local (o GitLab bloqueia por padrão, por segurança)
#   3. cria os projetos e o webhook de push de cada um apontando para o Jenkins

$ErrorActionPreference = 'Stop'
$PROJETOS = @('app-python', 'app3')

function Ler-Variavel($arquivo, $nome) {
    (Select-String -Path $arquivo -Pattern "^$nome=(.*)").Matches[0].Groups[1].Value
}

$token = Ler-Variavel "$PSScriptRoot\.env" 'GITLAB_ROOT_TOKEN'
$tokenWebhook = Ler-Variavel "$PSScriptRoot\..\jenkins\.env" 'WEBHOOK_TOKEN'

Write-Host '1/3 Criando token do root e liberando webhooks locais...'
$ruby = @'
usuario = User.find_by_username('root')
token = usuario.personal_access_tokens.find_by(name: 'lab') ||
        usuario.personal_access_tokens.new(name: 'lab', expires_at: 365.days.from_now,
          scopes: %w[api read_repository write_repository read_registry write_registry])
token.set_token(ENV['GITLAB_ROOT_TOKEN'])
token.save!
ApplicationSetting.current.update!(allow_local_requests_from_web_hooks_and_services: true)
puts 'ok'
'@
docker exec gitlab gitlab-rails runner $ruby

function Chamar-Api($metodo, $caminho, $corpo) {
    $parametros = @{
        Method  = $metodo
        Uri     = "http://localhost/api/v4$caminho"
        Headers = @{ 'PRIVATE-TOKEN' = $token; Host = 'gitlab.localhost' }
    }
    if ($corpo) { $parametros.Body = $corpo }
    Invoke-RestMethod @parametros
}

foreach ($nome in $PROJETOS) {
    Write-Host "2/3 Projeto root/$nome"
    # Guardar em variável antes de filtrar: no PowerShell 5 o array JSON chega como um único objeto
    $projetos = Chamar-Api GET '/projects?owned=true'
    $projeto = $projetos | Where-Object path -eq $nome
    if (-not $projeto) {
        $projeto = Chamar-Api POST '/projects' @{ name = $nome; visibility = 'private'; default_branch = 'main' }
    }

    Write-Host "3/3 Webhook de push de $nome -> Jenkins"
    $urlJenkins = "http://jenkins:8080/project/$nome"
    $webhooks = Chamar-Api GET "/projects/$($projeto.id)/hooks"
    $existe = $webhooks | Where-Object url -eq $urlJenkins
    if (-not $existe) {
        Chamar-Api POST "/projects/$($projeto.id)/hooks" @{
            url = $urlJenkins; token = $tokenWebhook; push_events = 'true'; enable_ssl_verification = 'false'
        } | Out-Null
    }
}

Write-Host 'GitLab configurado.'
