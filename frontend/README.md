# CineMatch — Frontend (Angular)

## Dependências a instalar

```bash
# Dentro da pasta do projecto Angular
npm install @ngx-translate/core @ngx-translate/http-loader
```

## Estrutura dos ficheiros de tradução

Copia os ficheiros `pt.json` e `en.json` para:
```
src/assets/i18n/pt.json
src/assets/i18n/en.json
```

## Configuração

Em `src/environments/environment.ts`, confirma que o URL aponta para o backend:
```typescript
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000'   // porta onde o PHP está a correr
};
```

## Como correr

```bash
ng serve
```
Acede em: **http://localhost:4200**

## Estrutura dos módulos

```
features/
├── auth/     → login, register, forgot-password, reset-password
├── home/     → página inicial com todas as categorias + hero
├── movies/   → detalhe, pesquisa, por género
├── profile/  → perfil, watchlist, favoritos, avaliações, exportação
└── admin/    → gestão de utilizadores (só admin)
```

## Funcionalidades implementadas

- ✅ Dark mode / Light mode com toggle na navbar
- ✅ Português / Inglês com troca instantânea
- ✅ Registo, Login, Logout, Recuperação de senha
- ✅ Recomendações personalizadas (baseadas nos géneros e avaliações)
- ✅ Pesquisa com debounce + filtro por género
- ✅ Watchlist com filtro (todos / para ver / assistidos)
- ✅ Favoritos com toggle
- ✅ Avaliação com estrelas (1-10) + crítica
- ✅ Exportação CSV e PDF
- ✅ Admin panel com listagem e eliminação de utilizadores
- ✅ Interface responsiva (mobile + desktop)
- ✅ Guards de rota (auth, admin, guest)
- ✅ JWT interceptor automático

## Conta admin padrão
- Email: `admin@movies.ao`
- Senha: `Admin@1234`
