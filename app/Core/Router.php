<?php
class Router {
  private array $routes = ['GET'=>[], 'POST'=>[]];

  public function get(string $pattern, $handler) { $this->routes['GET'][$pattern] = $handler; }
  public function post(string $pattern, $handler) { $this->routes['POST'][$pattern] = $handler; }

  private function match(string $pattern, string $uri) {
    $regex = preg_replace('#\{([^}/]+)\}#', '(?P<$1>[^/]+)', $pattern);
    $regex = "#^" . rtrim($regex, '/') . "/?$#";
    if (preg_match($regex, $uri, $m)) {
      return array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
    }
    return false;
  }

  public function dispatch($method, $uri) {
    $uri = rtrim($uri, '/') ?: '/';

    foreach ($this->routes[$method] as $pattern => $handler) {
      if (($params = $this->match($pattern, $uri)) !== false) {
        // handler "Controller@metodo"
        if (is_string($handler) && strpos($handler, '@') !== false) {
          [$class, $method] = explode('@', $handler, 2);

          // mapeia para arquivo em app/controllers/Nome.php
          $file = __DIR__ . '/../controllers/' . $class . '.php';
          if (!file_exists($file)) {
            http_response_code(500);
            echo "Controller file não encontrado: app/controllers/{$class}.php";
            return;
          }
          require_once $file;

          if (!class_exists($class)) {
            http_response_code(500);
            echo "Classe do controller não encontrada: {$class}";
            return;
          }
          $obj = new $class();
          return $obj->$method($params);
        }
        // handler callable
        if (is_callable($handler)) return call_user_func($handler, $params);
      }
    }

    http_response_code(404);
    echo "404";
  }
}
