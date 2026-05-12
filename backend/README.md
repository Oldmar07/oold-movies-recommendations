# OOLD / OldFlix - Backend

API em PHP puro para autenticação, filmes, favoritos, watchlist, avaliações, recomendações e exportação de dados.

## Requisitos

- XAMPP com Apache, PHP e MySQL/MariaDB
- PHP 8 ou superior
- MySQL/MariaDB na porta `3306`
- Chave da TMDB, caso a chave padrão deixe de funcionar

## Estrutura

```text
backend/
├── config/          Configuração da app e da base de dados
├── controllers/     Controllers HTTP
├── core/            Router, Database, Response e Middleware
├── database/        Schema SQL
├── models/          Acesso à base de dados
├── services/        TMDB, JWT, email e exportação
├── cache/           Cache local da TMDB, gerado em runtime
└── index.php        Entry point e rotas
```

## Instalação Rápida

1. Abre o XAMPP Control Panel.
2. Inicia o `MySQL`.
3. Cria a base de dados importando o schema:

```powershell
cd C:\xampp\htdocs\OldFlix
Get-Content backend\database\schema.sql | C:\xampp\mysql\bin\mysql.exe -u root
```

Também podes importar `backend/database/schema.sql` pelo phpMyAdmin.

4. Entra na pasta do backend:

```powershell
cd C:\xampp\htdocs\OldFlix\backend
```

5. Arranca a API:

```powershell
php -S localhost:8000
```

6. Testa no browser:

```text
http://localhost:8000/movies/popular
```

## Configuração

As configurações principais ficam em:

- `backend/config/database.php`
- `backend/config/app.php`

Por padrão, a app usa:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=movie_recommendation
DB_USER=root
DB_PASS=
```

Se o MySQL do teu computador tiver outra password, altera `backend/config/database.php` ou define variáveis de ambiente.

Configuração da TMDB:

```php
define('TMDB_API_KEY', getenv('TMDB_API_KEY') ?: '...');
```

A API também usa cache local em `backend/cache/tmdb`. Essa pasta é criada automaticamente e melhora bastante a velocidade depois da primeira chamada.

## Conta Admin Padrão

```text
Email: admin@movies.ao
Senha: Admin@1234
```

## Endpoints Principais

### Autenticação

| Método | Rota | Descrição |
|---|---|---|
| POST | `/auth/register` | Criar conta |
| POST | `/auth/login` | Login |
| GET | `/auth/me` | Dados do utilizador autenticado |
| POST | `/auth/forgot-password` | Pedir reset de senha |
| POST | `/auth/reset-password` | Redefinir senha |

### Filmes

| Método | Rota | Descrição |
|---|---|---|
| GET | `/movies/popular` | Filmes populares |
| GET | `/movies/top-rated` | Mais bem avaliados |
| GET | `/movies/now-playing` | Em exibição |
| GET | `/movies/upcoming` | Em breve |
| GET | `/movies/search?q=matrix` | Pesquisar filmes |
| GET | `/movies/genres` | Listar géneros |
| GET | `/movies/by-genre/:genreId` | Filmes por género |
| GET | `/movies/:id` | Detalhes do filme |
| GET | `/movies/:id/recommendations` | Recomendações da TMDB |

### Rotas Protegidas

Estas rotas precisam do header:

```text
Authorization: Bearer <token>
```

| Método | Rota | Descrição |
|---|---|---|
| GET | `/movies/for-you` | Recomendações personalizadas |
| GET | `/movies/:id/rating` | Ver avaliação pessoal |
| POST | `/movies/:id/rating` | Criar/atualizar avaliação |
| DELETE | `/movies/:id/rating` | Remover avaliação |
| POST | `/movies/:id/watchlist` | Adicionar à watchlist |
| DELETE | `/movies/:id/watchlist` | Remover da watchlist |
| PUT | `/movies/:id/watchlist/watched` | Marcar como assistido |
| POST | `/movies/:id/favorite` | Alternar favorito |
| DELETE | `/movies/:id/favorite` | Remover favorito |
| GET | `/user/profile` | Ver perfil |
| PUT | `/user/profile` | Atualizar perfil |
| PUT | `/user/password` | Alterar senha |
| GET | `/user/ratings` | Minhas avaliações |
| GET | `/user/watchlist` | Minha watchlist |
| GET | `/user/favorites` | Meus favoritos |

### Exportação

| Método | Rota | Descrição |
|---|---|---|
| GET | `/export/watchlist/csv` | Exportar watchlist em CSV |
| GET | `/export/ratings/csv` | Exportar avaliações em CSV |
| GET | `/export/favorites/csv` | Exportar favoritos em CSV |
| GET | `/export/ratings/pdf` | Exportar avaliações em PDF |

### Admin

| Método | Rota | Descrição |
|---|---|---|
| GET | `/admin/users` | Listar utilizadores |
| DELETE | `/admin/users/:id` | Eliminar utilizador |

## Exemplos de Teste

Registar:

```powershell
curl -X POST http://localhost:8000/auth/register `
  -H "Content-Type: application/json" `
  -d "{\"name\":\"Teste\",\"email\":\"teste@demo.com\",\"password\":\"Teste1234\"}"
```

Login:

```powershell
curl -X POST http://localhost:8000/auth/login `
  -H "Content-Type: application/json" `
  -d "{\"email\":\"admin@movies.ao\",\"password\":\"Admin@1234\"}"
```

Filmes populares:

```powershell
curl "http://localhost:8000/movies/popular?lang=pt-BR&page=1"
```

## Parâmetros Úteis

- `lang=pt-BR` ou `lang=en-US`
- `page=1`
- `watched=1` ou `watched=0` em `/user/watchlist`

## Problemas Comuns

### `php` não é reconhecido

Adiciona `C:\xampp\php` ao `PATH` do Windows ou executa:

```powershell
C:\xampp\php\php.exe -S localhost:8000
```

### MySQL não inicia no XAMPP

Verifica se a porta `3306` está ocupada. No PowerShell:

```powershell
Get-NetTCPConnection -LocalPort 3306
```

### Erro de ligação à base de dados

Confirma se:

- O MySQL está ligado.
- A base de dados `movie_recommendation` existe.
- As credenciais em `backend/config/database.php` estão corretas.

### API lenta na primeira chamada

É normal. A primeira chamada vai à TMDB. As chamadas seguintes usam cache local em `backend/cache/tmdb`.
