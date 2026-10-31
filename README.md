# Lab de esteira CI/CD com Docker

Simula o ambiente de uma empresa numa máquina só: **código no GitLab → webhook → pipeline no Jenkins → imagem no registry → deploy atrás de um proxy reverso**, com os apps acessando um banco **externo** (um MySQL fora da rede Docker, no papel do Oracle de um ERP).

**Tecnologias:** Docker, Docker Compose, redes Docker, Apache (proxy reverso), GitLab CE + Container Registry, Jenkins (Pipeline + Configuration as Code + Job DSL), PHP 8.5, Laravel 13 + Sail, Python 3.15, MySQL, WSL2.

**O que demonstra:**
- vários serviços num servidor com **uma porta só**, roteados pelo nome de domínio;
- pipeline **testes → build → push → deploy**, disparado por push e versionado por tag (com rollback);
- **paridade dev/prod** (Sail no dev e Dockerfile multi-stage na entrega, mesma versão do PHP);
- **segredos fora do código**: `.env` locais e credenciais do Jenkins; só os `.env.example` estão no repositório;
- infraestrutura e configuração **como código** (compose, `casc.yaml`, script de setup do GitLab).

> **Sobre este repositório:** no lab, cada app (`app-python`, `app3`) é um repositório próprio no GitLab local, que é o que o Jenkins clona. Aqui no GitHub tudo está reunido num repositório só para facilitar a leitura, gerado pelo `scripts/exportar-portfolio.ps1`. O `app3` fica dentro do Ubuntu (WSL) no lab e, aqui, em `projetos/app3`.

```
 você (git push)                            navegador: *.localhost (sem porta)
       │                                               │ :80
       ▼                                               ▼
┌──────────────────────────── rede Docker "lab" ─────────────────────────────┐
│                                                                            │
│  gitlab ──webhook──> jenkins ──build/push──> registry (gitlab :5050)       │
│     ▲                   │                        │                         │
│     └──── clone ────────┘                        │ pull                    │
│                         └──────── deploy ────────▼                         │
│                                                                            │
│  proxy (:80) ──Host──> app1 · app2 · app-python · gitlab · jenkins         │
└─────────────────────────────────────┬──────────────────────────────────────┘
                                      │ saída via NAT
                                      ▼
                     host.docker.internal:3306 (banco "externo")
```

Só **duas portas** são publicadas no Windows: **80** (proxy) e **5050** (registry). Todo o resto conversa pela rede `lab`, pelo nome do container.

## Estrutura
```
infra/
  proxy/     Apache como proxy reverso (vhosts.conf montado como volume)
  gitlab/    GitLab CE enxuto + registry de imagens; configurar.ps1 (setup automático)
  jenkins/   Jenkins com Docker CLI + Configuration as Code (casc.yaml)
projetos/
  php/         imagem base PHP 8.5.12 + Apache (app1 e app2)
  app1/, app2/ apps PHP de diagnóstico (código montado como volume)
  app-python/  repositório git próprio; só chega ao "servidor" pelo pipeline
```
Cada sistema tem o **próprio `compose.yml`** e sobe ou cai sem afetar os outros. O que os une é a rede `lab`, declarada como `external: true` em todos.

## Acessos
| Endereço | O quê | Login |
|---|---|---|
| http://app1.localhost, http://app2.localhost | apps PHP | - |
| http://python.localhost | app Python (versão = número do build) | - |
| http://app3.localhost/diagnostico | app3 Laravel em "produção" (imagem do registry) | - |
| http://localhost:8000 · http://localhost:8025 | app3 em **dev** (Sail) · Mailpit | - |
| http://gitlab.localhost | GitLab | `root` / `GITLAB_ROOT_PASSWORD` em `infra/gitlab/.env` |
| http://jenkins.localhost | Jenkins | `admin` / `JENKINS_ADMIN_PASSWORD` em `infra/jenkins/.env` |

Os `.env` têm **valores de teste**, gerados só para este lab. Para os apps conectarem no banco, preencha o `DB_PASSWORD` em `projetos/app1/.env`, `projetos/app2/.env` e `infra/jenkins/.env` (este é usado no deploy do app-python).

## Subir e parar
```bash
docker network create lab                                   # só na primeira vez
docker compose -f infra/proxy/compose.yml up -d --build
docker compose -f infra/gitlab/compose.yml up -d            # 3 a 5 min até responder
docker compose -f infra/jenkins/compose.yml up -d --build
docker compose -f projetos/app1/compose.yml up -d --build
docker compose -f projetos/app2/compose.yml up -d --build
powershell -ExecutionPolicy Bypass -File infra/gitlab/configurar.ps1   # só na primeira vez
```
Para liberar memória quando não estiver estudando (o GitLab usa ~2,7 GB e o Jenkins ~0,9 GB):
```bash
docker compose -f infra/gitlab/compose.yml stop
docker compose -f infra/jenkins/compose.yml stop
```

## O que acontece num `git push`
1. O **GitLab** recebe o push e dispara o **webhook** para `http://jenkins:8080/project/app-python`, com um token secreto (`WEBHOOK_TOKEN`).
2. O **Jenkins** clona `http://gitlab/root/app-python.git` e executa o `Jenkinsfile` do repositório:
   - **Testes:** `docker build --target testes`. Os testes rodam na mesma base da produção, e se falharem, nada é entregue.
   - **Build:** gera `localhost:5050/root/app-python:<número do build>`.
   - **Push:** login no registry com o token do GitLab (credencial do Jenkins) e envio da imagem.
   - **Deploy:** `docker compose -f deploy/compose.yml up -d` com a nova tag. O container é recriado.
3. O **proxy** encaminha `python.localhost` para o novo container (`disablereuse=On` faz o Apache resolver o nome de novo).

## Faça você
1. Clone e altere o app:
   ```bash
   git clone http://gitlab.localhost/root/app-python.git
   ```
   Usuário `root`; como senha, use o `GITLAB_ROOT_TOKEN` de `infra/gitlab/.env`. Mude o texto do `<p>` em `src/servidor.py`.
2. Faça commit e `git push`, e acompanhe o build em http://jenkins.localhost/job/app-python/.
3. Recarregue http://python.localhost: a versão subiu.
4. **Rollback:** volte para uma tag anterior, sem rebuild:
   ```bash
   cd projetos/app-python
   ```
   ```powershell
   $env:TAG='4'; docker compose -f deploy/compose.yml up -d
   ```
5. Quebre um teste de propósito e faça push: o pipeline para no estágio **Testes**, e a versão no ar não muda.
6. Veja as imagens em GitLab → app-python → *Deploy → Container registry*.

## Problemas reais que apareceram (e viram boas histórias de entrevista)
| Sintoma | Causa | Solução |
|---|---|---|
| Jenkins não clonava de `gitlab.localhost`, embora o DNS da rede resolvesse | O curl/git trata **qualquer** `*.localhost` como 127.0.0.1, sem consultar DNS | Dentro da rede, usar o nome do container: `http://gitlab/...` |
| `docker push` falhava com "lookup gitlab.localhost: no such host" | Para autenticar no registry, o **daemon** do Docker busca um token no endereço do GitLab (o *realm*), e o daemon não resolve esse nome | `registry['token_realm'] = 'http://localhost'` + o proxy repassa `/jwt/auth` ao GitLab |
| Push com "blob unknown to registry" | O BuildKit anexa atestados (provenance/SBOM) que o registry não aceitou | `docker build --provenance=false --sbom=false` |
| `healthy` no GitLab, mas o banco ainda sem tabelas | O healthcheck responde antes de o `reconfigure` terminar | Esperar o "gitlab Reconfigured!" no log |

## Perguntas de entrevista que este lab responde
- **CI x CD?** CI: a cada push, testar e gerar um artefato (a imagem). CD: levar esse artefato ao ambiente de forma automática e repetível.
- **Por que tag por build e não `latest`?** Para saber exatamente o que está rodando, e porque o rollback é só voltar a tag.
- **Onde ficam os segredos?** Nunca no Git nem na imagem: nas credenciais do Jenkins (injetadas em tempo de execução) e em `.env` fora do repositório.
- **Como expor vários serviços num servidor?** Com um proxy reverso roteando pelo cabeçalho `Host`. Os apps ficam sem portas publicadas.
- **Como o container acessa o banco legado?** Por conexão de saída (NAT). O firewall do banco libera o IP do **servidor Docker**.
- **Configuração do Jenkins?** Como código (`casc.yaml`): versionável e reproduzível, sem cliques.
- **Riscos?** O Jenkins com acesso ao `docker.sock` controla o host inteiro. Em produção, usam-se agentes de build isolados.

## Adicionar um novo projeto
1. Crie o repositório com `Dockerfile`, `Jenkinsfile` e `deploy/compose.yml` (copie o `app-python` como modelo).
2. Adicione o nome na lista `PROJETOS` de `infra/gitlab/configurar.ps1` e na lista de jobs de `infra/jenkins/casc.yaml`.
3. Adicione um `<VirtualHost>` em `infra/proxy/vhosts.conf` e rode `docker exec proxy httpd -k graceful`.

## app3: Laravel, desenvolvido com Sail e entregue pelo pipeline
O código fica **dentro do Ubuntu (WSL)**, em `~/projetos/app3`, e não no `D:`, por desempenho. No VS Code, abra pela extensão WSL (`code ~/projetos/app3` no terminal do Ubuntu).

| | Desenvolvimento (Sail) | Produção (pipeline) |
|---|---|---|
| Arquivo | `compose.yaml` (gerado pelo Sail) | `Dockerfile` + `deploy/compose.yml` |
| PHP | 8.5 (runtime do Sail) | 8.5.12 + Apache |
| Código | montado como volume (edita e vê na hora) | copiado para dentro da imagem |
| Banco | MySQL do Sail, descartável (porta 3307 no host) | banco "externo" via `host.docker.internal` |
| Acesso | http://localhost:8000 | http://app3.localhost |
| Configuração | `.env` local | variáveis no deploy + credenciais do Jenkins (`APP_KEY`, banco) |

Comandos do dia a dia, no terminal do Ubuntu, dentro de `~/projetos/app3`:
```bash
./vendor/bin/sail up -d          # sobe o ambiente de dev
./vendor/bin/sail artisan test   # roda os testes
./vendor/bin/sail down           # derruba o ambiente de dev
git push                         # entrega: dispara o pipeline do app3
```
As portas do Sail foram ajustadas no `.env` (`APP_PORT=8000`, `FORWARD_DB_PORT=3307`) para não conflitar com o proxy (80) e com o seu MySQL (3306).

Pegadinhas que apareceram:
- O Laravel 13 não traz mais o Sail por padrão: `composer require laravel/sail --dev` e depois `php artisan sail:install`.
- O `networkingMode=mirrored` no `.wslconfig` quebrou a integração do Docker Desktop com o Ubuntu (o socket do backend não aparecia). Sem ele, o Ubuntu acessa as portas publicadas pelo `localhost` normalmente.
- Sessão e cache do Laravel vêm configurados para usar o banco (`database`). No deploy foram trocados para `file`, para a página abrir mesmo sem banco.

## Indo para a empresa
- Domínios reais e **HTTPS** no proxy. O GitLab e o registry ganham nomes de DNS, e o ajuste do `token_realm` deixa de ser necessário.
- O banco passa a ser o Oracle do ERP: `DB_HOST`/`DB_PORT` no deploy, e Oracle Instant Client + `oci8`/`oracledb` na imagem.
- Os servidores de CI e de aplicação ficam separados. O deploy é feito por SSH ou por um orquestrador.
