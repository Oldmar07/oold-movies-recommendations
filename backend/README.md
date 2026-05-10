# CineMatch — Backend (PHP Puro)

## Estrutura
```
backend/
├── config/         # Configurações (DB, app)
├── core/           # Database, Router, Response, Middleware
├── models/         # Acesso a dados (User, Rating, Watchlist, Favorite)
├── controllers/    # Lógica HTTP (Auth, Movie, User, Export)
├── services/       # Lógica de negócio (TMDB, Auth/JWT, Mail, Export)
├── database/       # schema.sql
├── .htaccess       # URL rewriting
└── index.php       # Entry point + rotas
```

## Configuração

### 1. Base de dados
```sql
-- No MySQL/phpMyAdmin, executa:
source backend/database/schema.sql
```

### 2. Configurar credenciais
Edita `config/database.php` com os teus dados MySQL.

Edita `config/app.php` e substitui:
- `YOUR_TMDB_API_KEY_HERE` pela tua chave da TMDB  
  (Obtém em: https://www.themoviedb.org/settings/api)

### 3. Servidor
Coloca a pasta `backend/` no teu servidor Apache (ex: `htdocs/cinematch/api/`).

Ou usa o servidor embutido do PHP para desenvolvimento:
```bash
cd backend
php -S localhost:8000
```

### 4. Testar
```bash
curl http://localhost:8000/movies/popular
curl http://localhost:8000/auth/register \
  -X POST -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@test.com","password":"Test1234"}'
```

## Rotas da API

### Autenticação
| Método | Rota | Descrição |
|--------|------|-----------|
| POST | /auth/register | Registo |
| POST | /auth/login | Login |
| GET  | /auth/me | Utilizador autenticado |
| POST | /auth/forgot-password | Pedir reset de senha |
| POST | /auth/reset-password | Confirmar nova senha |

### Filmes (públicas)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | /movies/popular | Filmes populares |
| GET | /movies/top-rated | Mais bem avaliados |
| GET | /movies/now-playing | Em exibição |
| GET | /movies/upcoming | Em breve |
| GET | /movies/search?q=... | Pesquisa |
| GET | /movies/genres | Lista de géneros |
| GET | /movies/:id | Detalhes de um filme |
| GET | /movies/:id/recommendations | Recomendações TMDB |
| GET | /movies/by-genre/:genreId | Por género |

### Filmes (requerem `Authorization: Bearer <token>`)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | /movies/for-you | Recomendações personalizadas |
| GET/POST/DELETE | /movies/:id/rating | Avaliações |
| POST/DELETE | /movies/:id/watchlist | Watchlist |
| PUT | /movies/:id/watchlist/watched | Marcar como assistido |
| POST | /movies/:id/favorite | Toggle favorito |

### Utilizador
| Método | Rota | Descrição |
|--------|------|-----------|
| GET/PUT | /user/profile | Perfil |
| PUT | /user/password | Alterar senha |
| GET | /user/ratings | Minhas avaliações |
| GET | /user/watchlist | Minha watchlist |
| GET | /user/favorites | Meus favoritos |

### Exportação
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | /export/watchlist/csv | Watchlist em CSV |
| GET | /export/ratings/csv | Avaliações em CSV |
| GET | /export/favorites/csv | Favoritos em CSV |
| GET | /export/ratings/pdf | Avaliações em PDF |

### Admin
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | /admin/users | Lista utilizadores |
| DELETE | /admin/users/:id | Elimina utilizador |

## Parâmetros de query
- `lang=pt-BR` ou `lang=en-US` → idioma dos dados TMDB  
- `page=1` → paginação  
- `watched=1` ou `watched=0` → filtrar watchlist

## Conta admin padrão
- Email: `admin@movies.ao`  
- Senha: `Admin@1234`
