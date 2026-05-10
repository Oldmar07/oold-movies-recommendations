<?php
// ============================================================
//  Configuração Global da Aplicação
// ============================================================

define('APP_NAME',    'OOLD');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development');

// --- JWT ---
define('JWT_SECRET',     getenv('JWT_SECRET')     ?: 'CHANGE_ME_IN_PRODUCTION_use_a_long_random_string');
define('JWT_EXPIRY',     60 * 60 * 24);        // 24 horas em segundos
define('JWT_REFRESH',    60 * 60 * 24 * 7);    // 7 dias

// --- TMDB ---
define('TMDB_API_KEY',   getenv('TMDB_API_KEY')   ?: 'ec867bc3df0201ff0291f204980a8ba1');
define('TMDB_BASE_URL',  'https://api.themoviedb.org/3');
define('TMDB_IMAGE_URL', 'https://image.tmdb.org/t/p');
define('TMDB_CACHE_TTL', 60 * 60 * 6);  // 6 horas

// --- Email (recuperação de senha) ---
define('MAIL_HOST',     getenv('MAIL_HOST')     ?: 'smtp.gmail.com');
define('MAIL_PORT',     getenv('MAIL_PORT')     ?: 587);
define('MAIL_USER',     getenv('MAIL_USER')     ?: 'no-reply@movies.ao');
define('MAIL_PASS',     getenv('MAIL_PASS')     ?: '');
define('MAIL_FROM',     getenv('MAIL_FROM')     ?: 'OOLD <no-reply@movies.ao>');

// --- CORS ---
define('ALLOWED_ORIGINS', ['http://localhost:4200', 'http://localhost:3000']);

// --- Paginação padrão ---
define('DEFAULT_PAGE_SIZE', 20);
