<?php

namespace App\Api;

/**
 *
 * <pre>
 * Старт
 * $router = new Router();
 * $router->setBasePath('/api'); // если находимся в папке api
 *
 * Группа запросов
 * $router->group('/project', function() use ($router) {
 *     $router->get('/create', function() {}); GET запрос
 *     $router->post('/create', function() {}); POST запрос
 *     $router->all('/create', function() {}); Все запросы
 * })
 *
 * Запуск роутинга
 * $router->run();
 *
 * ! Метод mount() переименован в group()
 * ! Данные $_REQUEST не хранит. Обеспечивает только маршрутизацию.
 * </pre>
 *
 * @link https://github.com/bramus/router#subrouting--mounting-routes
 *
 */
class Router
{
    /* @var array The route patterns and their handling functions */
    private array $routeList = [];

    /* @var array The before middleware route patterns and their handling functions */
    private array $beforeRoutes = [];

    /* @var callback|array|string  The function to be executed when no route has been matched */
    protected $notFoundCallback;

    /* @var string Current base route, used for (sub)route mounting */
    private string $baseRoute = '';

    /* @var string The Request Method that needs to be handled */
    private string $requestedMethod = '';

    /* @var string The Server Base Path for Router Execution */
    private string $serverBasePath;

    /* @var string Default Controllers Namespace */
    private string $namespace = '';

    /* @var string[] разделители для методов, переданных строкой namespace@method */
    private array $methodSeparatorList = ['@', '#', '::'];

    /**
     * Установка middleware для определённых маршрутов
     *
     * @param string $methods список методов, разделённый символом "|"
     * @param string $pattern маршрут
     * @param object|callable|array|string $fn callback
     */
    public function before(string $methods, string $pattern, $fn) : void
    {
        $pattern = $this->baseRoute . '/' . trim($pattern, '/');
        $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;

        foreach(explode('|', $methods) as $method) {
            $this->beforeRoutes[$method][] = [
                'pattern' => $pattern,
                'fn' => $fn,
            ];
        }
    }

    /**
     * Устанавливает функции-обработчики для переданных типов запросов по переданному шаблону
     *
     * @param string $methods список методов, разделённый символом "|"
     * @param string $pattern маршрут
     * @param callable|array|string $fn callback
     */
    public function match(string $methods, string $pattern, callable|array|string $fn) : void
    {
        $pattern = $this->baseRoute . '/' . trim($pattern, '/');
        $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;

        foreach(explode('|', $methods) as $method) {
            $this->routeList[$method][] = [
                'pattern' => $pattern,
                'fn' => $fn,
            ];
        }
    }

    /**
     * Установка обработчика для любого метода
     *
     * @param string $pattern маршрут
     * @param callable|array|string $fn callback
     */
    public function all(string $pattern, callable|array|string $fn) : void
    {
        $this->match('GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD', $pattern, $fn);
    }

    /**
     * Установка обработчика для метода GET
     *
     * @param string $pattern маршрут
     * @param callable|array|string $fn callback
     */
    public function get(string $pattern, callable|array|string $fn) : void
    {
        $this->match('GET', $pattern, $fn);
    }

    /**
     * Установка обработчика для метода POST
     *
     * @param string $pattern маршрут
     * @param callable|array|string $fn callback
     */
    public function post(string $pattern, callable|array|string $fn) : void
    {
        $this->match('POST', $pattern, $fn);
    }

    /**
     * Установка обработчика для метода PATCH
     *
     * @param string $pattern маршрут
     * @param callable|array|string $fn callback
     */
    public function patch(string $pattern, callable|array|string $fn) : void
    {
        $this->match('PATCH', $pattern, $fn);
    }

    /**
     * Установка обработчика для метода DELETE
     *
     * @param string $pattern маршрут
     * @param callable|array|string $fn callback
     */
    public function delete(string $pattern, callable|array|string $fn) : void
    {
        $this->match('DELETE', $pattern, $fn);
    }

    /**
     * Установка обработчика для метода PUT
     *
     * @param string $pattern маршрут
     * @param callable|array|string $fn callback
     */
    public function put(string $pattern, callable|array|string $fn) : void
    {
        $this->match('PUT', $pattern, $fn);
    }

    /**
     * Установка обработчика для метода OPTIONS
     *
     * @param string $pattern маршрут
     * @param callable|array|string $fn callback
     */
    public function options(string $pattern, callable|array|string $fn) : void
    {
        $this->match('OPTIONS', $pattern, $fn);
    }

    /**
     * Дополнение базового маршрута и вызов функций, переданных в $fn с учётом дополненного базового пути
     *
     * @param string $baseRoute путь, которой будет добавлен к базовому
     * @param callable $fn callback
     */
    public function group(string $baseRoute, callable $fn) : void
    {
        // Track current base route
        $curBaseRoute = $this->baseRoute;

        // Build new base route string
        $this->baseRoute .= $baseRoute;

        // Call the callable
        call_user_func($fn);

        // Restore original base route
        $this->baseRoute = $curBaseRoute;
    }

    /**
     * Получение заголовков запроса
     *
     * @return array
     */
    public function getRequestHeaders() : array
    {
        $headers = [];

        if(function_exists('getallheaders')) {
            $headers = getallheaders();
            if($headers !== false) {
                return $headers;
            }
        }

        foreach($_SERVER as $name => $value) {
            if((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
                $headers[str_replace([' ', 'Http'], ['-', 'HTTP'], ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }

    /**
     * Получение типа запроса
     *
     * @return string
     */
    public function getRequestMethod() : string
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
        // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        if($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_start();
            $method = 'GET';
        } // If it's a POST request, check for a method override header
        elseif($_SERVER['REQUEST_METHOD'] == 'POST') {
            $headers = $this->getRequestHeaders();
            if(isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'])) {
                $method = $headers['X-HTTP-Method-Override'];
            }
        }

        return $method;
    }

    /**
     * Установка пространства имён по-умолчанию
     *
     * @param string $namespace простарнство имён
     */
    public function setNamespace(string $namespace) : void
    {
        $this->namespace = $namespace;
    }

    /**
     * Получение текущего пространства исён
     *
     * @return string
     */
    public function getNamespace() : string
    {
        return $this->namespace;
    }

    /**
     * Запуск роутера: сначала запуск middleware, потом поиск совпадающего маршрута
     *
     * @param callable|null $callback Function to be executed after a matching route was handled (= after router middleware)
     *
     * @return bool
     */
    public function run(callable $callback = null)
    {
        // Define which method we need to handle
        $this->requestedMethod = $this->getRequestMethod();

        // Handle all before middlewares
        if(isset($this->beforeRoutes[$this->requestedMethod])) {
            $this->handle($this->beforeRoutes[$this->requestedMethod]);
        }

        // Handle all routes
        $numHandled = 0;
        if(isset($this->routeList[$this->requestedMethod])) {
            $numHandled = $this->handle($this->routeList[$this->requestedMethod], true);
        }

        // If no route was handled, trigger the 404 (if any)
        if($numHandled === 0) {
            $this->trigger404();
        } // If a route was handled, perform the finish callback (if any)
        elseif($callback && is_callable($callback)) {
            $callback();
        }

        // If it originally was a HEAD request, clean up after ourselves by emptying the output buffer
        if($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_end_clean();
        }

        // Return true if a route was handled, false otherwise
        return $numHandled !== 0;
    }

    /**
     * Установка обработчика для ошибки 404
     *
     * @param callable|array|string $fn callback
     */
    public function set404Handler($fn) : void
    {
        $this->notFoundCallback = $fn;
    }

    /**
     * Запуск обработчика 404-й ошибки
     */
    public function trigger404()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        if($this->notFoundCallback) {
            $this->invoke($this->notFoundCallback);
        }
    }

    /**
     * Проверка: совпадает ли маршрут с шаблоном
     *
     * @param string $pattern шаблон, по которому определяется совпадение
     * @param string $uri ссылка, для которой ищется совпадение
     * @param mixed $matches хз
     *
     * @return bool
     */
    private function patternMatches(string $pattern, string $uri, &$matches) : bool
    {
        // Replace all curly braces matches {} into word patterns (like Laravel)
        $pattern = preg_replace('/\/{(.*?)}/', '/(.*?)', $pattern);

        // we may have a match!
        return boolval(preg_match_all('#^' . $pattern . '$#', $uri, $matches, PREG_OFFSET_CAPTURE));
    }

    /**
     * Обработка добавленных маршрутов
     *
     * @param array $routes массив с маршрутами и их обработчиками ['patter' => 'string', 'fn' => callback]
     * @param bool $quitAfterRun прекращать ли обработку при первом совпадении
     *
     * @return int количество обработанных маршрутов
     */
    private function handle(array $routes, bool $quitAfterRun = false) : int
    {
        $numHandled = 0;
        $uri = $this->getCurrentUri();
        foreach($routes as $route) {
            $doesMatch = $this->patternMatches($route['pattern'], $uri, $matches);
            if(!$doesMatch) {
                continue;
            }

            // Rework matches to only contain the matches, not the orig string
            $matches = array_slice($matches, 1);

            // Extract the matched URL parameters (and only the parameters)
            $params = array_map(function ($match, $index) use ($matches) {

                // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
                if(isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                    if($matches[$index + 1][0][1] > -1) {
                        return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                    }
                } // We have no following parameters: return the whole lot

                return isset($match[0][0]) && $match[0][1] != -1 ? trim($match[0][0], '/') : null;
            }, $matches, array_keys($matches));

            $this->invoke($route['fn'], $params);
            $numHandled++;
            if($quitAfterRun) {
                break;
            }
        }

        return $numHandled;
    }

    /**
     * Запуск callback функции
     *
     * @param callable|array|string $fn callback, массив в виде [Class, 'method'] или строка путь к методу (namespace + separator + method)
     * @param array $params параметры, передаваемые функции
     *
     * @throws \Exception
     */
    private function invoke($fn, array $params = []) : void
    {
        // Массив в виде [Class, 'method'] проходит эту проверку
        if(is_callable($fn)) {
            call_user_func_array($fn, $params);
            return;
        }

        if(!is_string($fn)) {
            throw new \Exception("Can' call $fn");
        }

        $separator = '';
        foreach($this->methodSeparatorList as $separatorChar) {
            if(stripos($fn, $separatorChar) !== false) {
                $separator = $separatorChar;
                break;
            }
        }

        [$controller, $method] = explode($separator, $fn);

        if($this->getNamespace() !== '') {
            $controller = $this->getNamespace() . '\\' . $controller;
        }

        $reflectedMethod = new \ReflectionMethod($controller, $method);
        if(!$reflectedMethod->isPublic() || $reflectedMethod->isAbstract()) {
            throw new \Exception("Cant call \"$method\" of \"$controller\" because it's private or abstract");
        }

        if($reflectedMethod->isStatic()) {
            forward_static_call_array([$controller, $method], $params);
            return;
        }

        if(is_string($controller)) {
            $controller = new $controller();
        }

        call_user_func_array([$controller, $method], $params);
    }

    /**
     * Получение текущего URI, относительно базового маршрута
     *
     * @return string
     */
    public function getCurrentUri() : string
    {
        $uri = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($this->getBasePath()));
        if(strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return '/' . trim($uri, '/');
    }

    /**
     * Получение базового пути
     *
     * @return string
     */
    public function getBasePath() : string
    {
        if($this->serverBasePath === null) {
            $this->serverBasePath = $this->getDefaultPath();
        }

        return $this->serverBasePath;
    }

    /**
     * Установка базового пути, отосительно которого будут строиться остальные маршруты
     *
     * @param string $serverBasePath путь
     */
    public function setBasePath(string $serverBasePath) : void
    {
        $this->serverBasePath = $serverBasePath;
    }

    /**
     * Получение списка маршрутов и их обработчиков
     *
     * @return array
     */
    public function getRouteList() : array
    {
        return $this->routeList;
    }

    /**
     * Получение HTML списка маршрутов
     *
     * @param array $getParams GET parameters
     *
     * @return string
     */
    public function getRouteListHtml(array $getParams = []) : string
    {
        $routeList = $this->getRouteList();
        $basePath = $this->getBasePath();

        $params = '';
        if($getParams) {
            $params = '?';
            foreach($getParams as $key => $value) {
                $params .= $key . '=' . $value . '&';
            }
            $params = trim($params, '&');
        }

        ob_start();
        ?>
        <?php foreach($routeList as $method => $routes): ?>
            <h1><?=$method?></h1>
            <?php foreach($routes as $route): ?>
                <?php if(is_string($route['fn'])): ?>
                    <h2><a href='<?=$basePath?><?=$route['pattern']?><?=$params?>'><?=$route['pattern']?></a> Метод: <?=$route['fn']?></h2>
                <?php elseif(is_array($route['fn'])): ?>
                    <h2><a href='<?=$basePath?><?=$route['pattern']?><?=$params?>'><?=$route['pattern']?></a> <?=implode('::', $route['fn'])?></h2>
                <?php elseif(is_callable($route['fn'])): ?>
                    <h2><a href='<?=$basePath?><?=$route['pattern']?><?=$params?>'><?=$route['pattern']?></a> CustomClosure</h2>
                <?php endif ?>
            <?php endforeach ?>
        <?php endforeach ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Формирование стандартного пути
     *
     * @return string
     */
    private function getDefaultPath() : string
    {
        return implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
    }
}