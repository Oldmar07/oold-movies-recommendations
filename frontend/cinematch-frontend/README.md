# OOLD / OldFlix - Frontend

Frontend Angular da aplicação OOLD / OldFlix. A interface permite pesquisar filmes, filtrar por género, ver detalhes com trailer, gerir favoritos, watchlist, avaliações, perfil e área de administração.

## Requisitos

- Node.js 16 ou 18 recomendado
- npm
- Angular CLI 15, opcional globalmente
- Backend a correr em `http://localhost:8000`

## Instalação

Entra na pasta do frontend:

```powershell
cd C:\xampp\htdocs\OldFlix\frontend\cinematch-frontend
```

Instala as dependências:

```powershell
npm install
```

Se o PowerShell bloquear `npm.ps1`, usa:

```powershell
npm.cmd install
```

## Como Correr em Desenvolvimento

1. Garante que o backend está ligado:

```powershell
cd C:\xampp\htdocs\OldFlix\backend
php -S localhost:8000
```

2. Noutro terminal, arranca o frontend:

```powershell
cd C:\xampp\htdocs\OldFlix\frontend\cinematch-frontend
npm.cmd start
```

3. Abre no browser:

```text
http://localhost:4200
```

## Configuração da API

O URL do backend está em:

```text
src/environments/environment.ts
```

Valor usado em desenvolvimento:

```ts
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000'
};
```

Se o backend estiver noutra porta ou computador, altera `apiUrl`.

## Scripts Disponíveis

```powershell
npm.cmd start
```

Arranca o servidor Angular em `http://localhost:4200`.

```powershell
npm.cmd run build
```

Gera a versão de produção em `dist/`.

```powershell
npm.cmd test
```

Executa os testes unitários com Karma.

## Funcionalidades

- Login e registo
- Pesquisa por texto na navbar
- Filtro por género na navbar
- Home com secções de filmes
- Detalhe de filme com trailer em background quando disponível
- Favoritos
- Watchlist
- Avaliações
- Perfil do utilizador
- Exportação de dados
- Área admin
- Idiomas PT/EN
- Tema claro/escuro
- Skeleton loading nas páginas principais

## Estrutura Principal

```text
src/app/
├── core/             Guards, interceptors, modelos e serviços
├── features/         Páginas por domínio: home, auth, movies, profile, admin
├── shared/           Navbar, cards, skeleton, spinner, pipes e componentes comuns
└── app-routing.module.ts

src/assets/
├── i18n/             Traduções PT/EN
├── no-avatar.png
├── no-poster.png
└── oold_navbar_logo_red.png
```

## Conta Admin Padrão

Usa esta conta depois de importar o schema do backend:

```text
Email: admin@movies.ao
Senha: Admin@1234
```

## Ordem Recomendada Para Arrancar o Projeto

1. Abrir XAMPP.
2. Iniciar MySQL.
3. Importar `backend/database/schema.sql`, se ainda não foi importado.
4. Arrancar o backend em `localhost:8000`.
5. Arrancar o frontend em `localhost:4200`.
6. Abrir `http://localhost:4200`.

## Problemas Comuns

### `npm` bloqueado no PowerShell

Usa `npm.cmd` em vez de `npm`:

```powershell
npm.cmd start
```

### A página abre, mas não carrega filmes

Confirma se o backend está ligado:

```text
http://localhost:8000/movies/popular
```

Se esta rota não responder, corrige primeiro o backend.

### Erro de CORS

Confirma se o frontend está em `http://localhost:4200`. O backend permite essa origem por padrão em `backend/config/app.php`.

### Login não funciona

Verifica:

- O MySQL está ligado.
- A base de dados foi importada.
- O backend está em `http://localhost:8000`.
- O `apiUrl` em `environment.ts` aponta para a porta correta.

### Build com warnings de CSS budget

O comando `npm.cmd run build` pode mostrar warnings de orçamento CSS em alguns componentes. Isso não impede a aplicação de funcionar.

## Notas Para Desenvolvimento

- Não edites ficheiros gerados em `dist/`.
- As traduções ficam em `src/assets/i18n/pt.json` e `src/assets/i18n/en.json`.
- O interceptor JWT adiciona automaticamente o token nas chamadas protegidas.
- Os trailers dependem dos dados da TMDB. Se um filme não tiver trailer, a página usa imagem de fundo.
