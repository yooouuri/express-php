<?php

namespace ExpressPHP;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Symfony\Component\Routing\RouteCollection;

class Routes implements IteratorAggregate, Countable
{
    /**
     * @var Route[] $routes
     */
    private array $routes;

    public function __construct()
    {
        $this->routes = [];
    }

    public function getIterator(): array|\Traversable
    {
        return new ArrayIterator($this->routes);
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function add(Route $route): void
    {
        $this->routes[$route->getName()] = $route;
    }

    public function get(string $name): Route
    {
        return $this->routes[$name];
    }

    public function convertToRouteCollection(): RouteCollection
    {
        $collection = new RouteCollection();

        foreach ($this->routes as $route) {
            $collection->add($route->getName(), $route->getRoute());
        }

        return $collection;
    }
}
