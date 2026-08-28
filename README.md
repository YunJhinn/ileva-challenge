# Desafio técnico — ILEVA Gestão Inteligente

Solução para as três tarefas do processo seletivo: parênteses balanceados, uma API REST de lista de contatos em PHP, e um front-end em JavaScript que consome essa API.

## Estrutura do repositório

```
ileva-challenge/
├── 01-balanced-brackets/   # Tarefa 1 — validador de colchetes (PHP + JS)
├── 02-contacts-api/        # Tarefa 2 — API REST (PHP)
├── 03-contacts-frontend/   # Tarefa 3 — front-end (JS puro, HTML, CSS)
└── docker-compose.yml      # sobe as três peças (+ MySQL) de uma vez
```

Cada pasta tem seu próprio README com detalhes específicos.

## Stack e decisões

- **Tarefa 1**: mesma solução (busca com pilha) implementada em PHP e em JavaScript, com testes para os dois.
- **Tarefa 2**: PHP puro (8.1+), sem framework — um roteador, `Request`/`Response` e camada de acesso a dados (PDO) escritos à mão (~250 linhas no total). Isso mantém a API sem nenhuma dependência de terceiros em tempo de execução, então ela roda com `php -S` sem precisar de `composer install` baixando nada da internet. Usa SQLite por padrão (zero configuração) e MySQL quando rodando via Docker — a troca é feita só por variável de ambiente (`DB_CONNECTION`), o resto do código nem sabe qual banco está por trás.
- **Tarefa 3**: JavaScript puro (sem framework/build step), consumindo a API via `fetch`. Isso deixa o front-end deployável como arquivos estáticos em qualquer lugar (Nginx, Vercel, Netlify, GitHub Pages...).
- **Docker**: cada serviço tem seu próprio `Dockerfile`; o `docker-compose.yml` na raiz sobe API + front-end + MySQL juntos.

> Por que não usar Slim/Laravel/etc.? Para uma API deste tamanho (2 entidades, 9 rotas), um framework completo adiciona mais superfície do que valor. Prefiro mostrar que entendo o que um framework faz por baixo dos panos. Dito isso, o código está organizado em camadas (Controller → Repository → PDO) exatamente como ficaria com um framework, então trocar por Slim/Laravel depois seria uma refatoração pequena caso o time prefira.

## Como rodar cada parte

### Sem Docker

```bash
# Tarefa 2 — API (porta 8080)
cd 02-contacts-api
cp .env.example .env
php -S 0.0.0.0:8080 -t public

# Tarefa 3 — Front-end (porta 8081), em outro terminal
cd 03-contacts-frontend
php -S 0.0.0.0:8081
```

Só precisa de PHP instalado — nada de Composer para simplesmente rodar (o projeto não tem dependências externas; se o Composer não tiver gerado `vendor/autoload.php`, o `public/index.php` usa um autoloader próprio como alternativa). Composer só entra em cena se você quiser rodar a suíte PHPUnit.

Depois abra `http://localhost:8081` no navegador. O front-end já aponta para `http://localhost:8080/api` por padrão (ver `index.html`).

### Com Docker

```bash
docker compose up --build
```

- API: `http://localhost:8080/api` (MySQL como banco, subido automaticamente)
- Front-end: `http://localhost:8081`

> Nota de transparência: este repositório foi montado em um ambiente sandbox sem acesso ao Docker Hub, então os `Dockerfile`s foram escritos e revisados com cuidado (e o `docker-compose.yml` foi validado sintaticamente com `docker compose config`), mas o `docker compose up --build` completo não pôde ser executado aqui. Toda a lógica de aplicação (API e front-end) foi validada de outras formas — veja "Como eu testei" abaixo. Vale rodar o build localmente antes de considerar essa parte 100% fechada.

## Documentação da API (Tarefa 2)

Base: `/api`

| Método | Rota                          | Descrição                              |
|--------|-------------------------------|-----------------------------------------|
| GET    | `/health`                     | Healthcheck                             |
| GET    | `/people`                     | Lista pessoas (com seus contatos)       |
| POST   | `/people`                     | Cria pessoa — `{ "name": "..." }`       |
| GET    | `/people/{id}`                | Detalhe de uma pessoa + contatos        |
| PUT    | `/people/{id}`                | Atualiza nome da pessoa                 |
| DELETE | `/people/{id}`                | Remove pessoa (contatos vão junto)      |
| POST   | `/people/{id}/contacts`       | Adiciona contato — `{ "type", "value" }`|
| GET    | `/contacts/{id}`               | Detalhe de um contato                   |
| PUT    | `/contacts/{id}`               | Atualiza um contato                     |
| DELETE | `/contacts/{id}`               | Remove um contato                       |

`type` aceita `phone`, `email` ou `whatsapp`. Erros de validação voltam como `422` com um mapa `{campo: mensagem}`; recurso não encontrado volta `404`; tudo em JSON.

## Como eu testei

- **Tarefa 1**: casos do enunciado + casos extras, rodados em PHP e Node diretamente.
- **Tarefa 2**: suíte de testes PHPUnit (`tests/Unit`, `tests/Feature`, banco SQLite em memória) e um script de smoke test sem dependências (`tests/smoke-test.sh`) que sobe a API de verdade e bate em cada rota com `curl`, checando status code e corpo da resposta — os 15 cenários passam.
- **Tarefa 3**: teste end-to-end com navegador real (Playwright/Chromium): cria pessoa, adiciona dois contatos, testa validação de e-mail inválido, edita a pessoa — e captura de tela do resultado, tudo conferido visualmente.

## Próximos passos que ficam com você

1. **GitHub**: `git init` já foi feito neste repositório localmente. Falta só:
   ```bash
   git remote add origin git@github.com:SEU_USUARIO/SEU_REPO.git
   git branch -M main
   git push -u origin main
   ```
2. **Deploy em nuvem (tarefas 2 e 3, conforme pedido no enunciado)**: algumas opções simples e com camada gratuita —
   - **Railway** ou **Render**: sobem o `docker-compose.yml` quase sem alteração (ou os `Dockerfile`s individualmente + um MySQL/Postgres gerenciado).
   - **Fly.io**: bom para os dois `Dockerfile`s, um app por serviço.
   - Front-end sozinho (é só HTML/CSS/JS estático): **Vercel**, **Netlify** ou **GitHub Pages** também servem — só lembre de apontar `API_BASE_URL` (em `03-contacts-frontend/index.html`) para a URL pública da API depois do deploy do back-end.
3. Depois do deploy, atualizar o `API_BASE_URL` do front-end para a URL pública da API (hoje ele assume `http://localhost:8080/api`).
