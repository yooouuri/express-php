<?php

namespace ExpressPHP;

use Closure;
use Exception;
use ReflectionFunction;
use ReflectionParameter;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Throwable;

/**
 * Class Express
 * @package ExpressPHP
 *
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

        $socket = new \React\Socket\Server('127.0.0.1:3000', $this->loop);
        $handler = function (ServerRequestInterface $request) {
            try {
                $matched = $this->matchRouteFromRequest($request);
            } catch (MethodNotAllowedException | ResourceNotFoundException) {
                return (new Response())
                    ->status(404)
                    ->json([ 'error' => 'Not found' ])
                    ->getResponse();
            }

            $routeName = $matched['_route'];
            unset($matched['_route']);

            $route = $this->routes->get($routeName);

            $parameters = (new ReflectionFunction($route->getClosure()))->getParameters();

            $methodParams = $this->matchRouteParamsToMethodParams($matched, $parameters);

            foreach ($parameters as $reflectionParameter) {
                if ($reflectionParameter->getName() === 'request') {
                    $methodParams = array_merge([ 'request' => $request ], $methodParams);

                    break;
                }
            }

            foreach ($parameters as $reflectionParameter) {
                if ($reflectionParameter->getName() === 'response') {
                    $response = new Response();
                    $methodParams = array_merge([ $response ], $methodParams);
                }
            }

            return $this
                ->createResponse($route->getClosure(), $methodParams)
                ->getResponse();
        };

        $server = new \React\Http\Server($handler);
        $server->on('error', function (Throwable $e) {
            echo $e . PHP_EOL;
        });
        $server->listen($socket);
    }

    /**
     * @param array $matched
     * @param ReflectionParameter[] $params
     * @return array
     */
    private function matchRouteParamsToMethodParams(array $matched, array $params): array
    {
        $filtered = [];

        foreach ($params as $param) {
            $paramName = $param->getName();

            if (array_key_exists($paramName, $matched)) {
                $filtered[$paramName] = $matched[$paramName];
            }
        }

        return $filtered;
    }

    private function matchRouteFromRequest(ServerRequestInterface $request): array
    {
        $httpFoundationFactory = new HttpFoundationFactory();

        $symfonyRequest = $httpFoundationFactory->createRequest($request);

        $context = new RequestContext();
        $context->fromRequest($symfonyRequest);

        $routeCollection = ($this->routes)->convertToRouteCollection();

        $matcher = new UrlMatcher($routeCollection, $context);
        return $matcher->match($request->getUri()->getPath());
    }

    private function createResponse(Closure $closure, array $params): Response
    {
        $callable = call_user_func_array($closure, $params);

        if (gettype($callable) === 'string') {
            $response = new Response();
            $response->body($callable);

            return $response;
        }

        if ($callable instanceof Response) {
            return $callable->getResponse();
        }

        throw new Exception('Callable should return Response or a string');
    }

    public function __call(string $name, array $arguments): void
    {
        if (in_array(strtoupper($name), self::METHODS)) {
            call_user_func_array([ $this, 'createRoute' ], array_merge([ $name ], $arguments ));
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
