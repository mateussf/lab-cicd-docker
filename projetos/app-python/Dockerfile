# Estágio comum: dependências + código
FROM python:3.15-slim AS base
ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY src ./src

# Roda os testes na MESMA base da produção; se um teste falhar, o build falha
FROM base AS testes
COPY tests ./tests
RUN python -m unittest discover -s tests -t . -v

# Imagem entregue: sem os testes e sem rodar como root
FROM base AS final
RUN useradd --create-home app
USER app
EXPOSE 8000
CMD ["python", "-m", "src.servidor"]
