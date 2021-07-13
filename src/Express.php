<?php

namespace ExpressPHP;

use Closure;
use React\EventLoop\LoopInterface;

/**
 * Class Express
 *
 * @method Route get(string $uri, Closure $closure, string $name = '')
 * @method Route post(string $uri, Closure $closure, string $name = '')
 * @method Route put(string $uri, Closure $closure, string $name = '')
 * @method Route delete(string $uri, Closure $closure, string $name = '')
 * @method Route patch(string $uri, Closure $closure, string $name = '')
 */
final class Express
{
    const METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH'
    ];

    private LoopInterface $loop;

    private Routes $routes;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;

        $this->routes = new Routes();
    }

    public function __call(string $name, array $arguments): void
    {
        if (in_array(strtoupper($name), self::METHODS)) {
            call_user_func_array([ $this, 'createRoute' ], array_merge([ $name ], $arguments ));
        }
    }

    public function listen(int $port = 3000, ?Closure $closure = null): void
    {
        $host = '127.0.0.1';
        $socket = new \React\Socket\Server("$host:$port", $this->loop);

        $reactServerClass = '\React\Http\Server';
        $server = new $reactServerClass(new RequestHandler($this->routes));
        $server->listen($socket);

        if ($closure !== null && $closure instanceof Closure) {
            $closure($host, $port);
        }
    }

    private function createRoute(string $method,
                                 string $path,
                                 Closure $handler,
                                 string $name = ''): Route
    {
        if ($name === '') {
            $name = $path;
        }

        $route = new Route($path, $name, $method, $handler);

        $this->routes->add($route);

        return $route;
    }
}
