<?php
// ============================================================
//  Core — Router
// ============================================================

namespace Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $this->compilePattern($pattern),
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable $handler): void  { $this->add('GET',    $pattern, $handler); }
    public function post(string $pattern, callable $handler): void { $this->add('POST',   $pattern, $handler); }
    public function put(string $pattern, callable $handler): void  { $this->add('PUT',    $pattern, $handler); }
    public function delete(string $pattern, callable $handler): void { $this->add('DELETE', $pattern, $handler); }

    public function dispatch(string $method, string $uri): void
    {
        // Remove query string
        $uri = strtok($uri, '?');

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Apenas os grupos nomeados como parâmetros
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func($route['handler'], $params);
                return;
            }
        }

        Response::error('Rota não encontrada.', 404);
    }

    /** Converte /users/:id em regex */
    private function compilePattern(string $pattern): string
    {
        $pattern = preg_replace('/\/:([a-zA-Z_]+)/', '/(?P<$1>[^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }
}
