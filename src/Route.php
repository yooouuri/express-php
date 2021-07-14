<?php

namespace ExpressPHP;

use Closure;
use Exception;

class Route
{
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    private string $method;

    public function getMethod(): string
    {
        return $this->method;
    }

    private \Symfony\Component\Routing\Route $route;

    public function getRoute(): \Symfony\Component\Routing\Route
    {
        return $this->route;
    }

    private Closure $closure;

    public function getClosure(): Closure
    {
        return $this->closure;
    }

    public function __construct(string $url,
                                string $name,
                                string $method,
                                Closure $closure)
    {
        $route = new \Symfony\Component\Routing\Route($url);
        $route->setMethods($method);

        $this->route = $route;
        $this->name = $name;
        $this->method = $method;

        if (!$closure instanceof Closure) {
            throw new Exception('No callable given');
        }

        $this->closure = $closure;
    }
}
