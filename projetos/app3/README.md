# app3 (Laravel 13)

App de estudo da esteira: **desenvolvido com Sail** e **entregue pelo pipeline** (GitLab → Jenkins → registry → deploy). Parte do lab em `D:\rede_docker` (veja o README de lá).

## Desenvolvimento (Sail)
```bash
cp .env.example .env                 # na primeira vez
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate   # na primeira vez
./vendor/bin/sail artisan migrate
```
- App: http://localhost:8000 (diagnóstico em `/diagnostico`)
- Mailpit: http://localhost:8025
- MySQL de dev: `localhost:3307` (usuário `sail`)

## Testes
```bash
./vendor/bin/sail artisan test
```
O banco é mockado nos testes de diagnóstico. No pipeline, os testes rodam no estágio `testes` do `Dockerfile`.

## Domínio
- `diagnostico`: `DiagnosticoService` monta as informações do container e testa o banco; `DiagnosticoController` exibe a página `/diagnostico`.

## Entrega
- `Dockerfile`: estágios `base` → `testes` → `final` (PHP 8.5.12 + Apache, sem dependências de dev).
- `Jenkinsfile`: Testes → Build → Push → Deploy.
- `deploy/compose.yml`: produção. `APP_KEY` e o banco vêm das credenciais do Jenkins.

Variáveis de ambiente: veja `.env.example` (dev) e `deploy/compose.yml` (produção).
