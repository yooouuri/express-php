<?php

namespace ExpressPHP;

use Closure;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response as ReactResponse;
use ReflectionFunction;
use ReflectionParameter;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

final class RequestHandler
{
    private Routes $routes;

    public function __construct(Routes $routes)
    {
        $this->routes = $routes;
    }

    public function __invoke(ServerRequestInterface $request): ReactResponse
    {
        try {
            $matched = $this->matchRouteFromRequest($request);
        } catch (MethodNotAllowedException | ResourceNotFoundException) {
            return $this->convertResponse(
                (new Response())
                    ->setStatus(404)
                    ->json(['error' => 'Not found'])
            );
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

        return $this->createResponse($route->getClosure(), $methodParams);
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

    private function createResponse(Closure $closure, array $params): ReactResponse
    {
        $callable = call_user_func_array($closure, $params);

        if (gettype($callable) === 'string') {
            $response = (new Response())->setBody($callable);

            return $this->convertResponse($response);
        }

        if ($callable instanceof Response) {
            return $this->convertResponse($callable);
        }

        throw new Exception('Callable should return instance of /ExpressPHP/Response or a string');
    }

    private function convertResponse(Response $response): ReactResponse
    {
        return new ReactResponse(
            $response->getStatus(),
            $response->getHeaders(),
            $response->getBody()
        );
    }
}
