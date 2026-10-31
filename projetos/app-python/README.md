# app-python

App de diagnóstico sem framework (`http.server` da biblioteca padrão), usado para estudar a esteira GitLab → Jenkins → registry → deploy. Mostra a versão da imagem (número do build), o hostname e o IP do container, o IP de quem chamou (o proxy), o cabeçalho Host e o teste de conexão com o banco externo.

## Estrutura
- `src/servidor.py`: servidor HTTP na porta 8000
- `src/config/conexao.py`: conexão com o banco (PyMySQL), lida das variáveis de ambiente
- `tests/`: testes com `unittest` + `mock` (não tocam no banco real)
- `Dockerfile`: estágios `base` → `testes` → `final`
- `Jenkinsfile`: Testes → Build → Push → Deploy
- `deploy/compose.yml`: como o app roda no "servidor" (rede `lab`, sem portas publicadas)

## Variáveis de ambiente
Veja `.env.example`. No deploy, o Jenkins injeta `DB_USER`/`DB_PASSWORD` a partir da credencial `banco-externo`.

## Rodar os testes
```bash
docker build --target testes .
```

## Deploy e rollback manual
```powershell
$env:TAG='4'; docker compose -f deploy/compose.yml up -d
```
