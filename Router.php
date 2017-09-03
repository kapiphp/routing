<?php

namespace Kapi\Routing;

use Kapi\Http\Response;
use Kapi\Http\ServerRequest;
use Kapi\Routing\Exception\RoutingException;
use Kapi\Routing\Route\Route;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A router that contains many instances of routes.
 *
 * @method Route get(string|string[] $path, $callable)
 * @method Route post(string|string[] $path, $callable)
 * @method Route put(string|string[] $path, $callable)
 * @method Route patch(string|string[] $path, $callable)
 * @method Route delete(string|string[] $path, $callable)
 * @method Route head(string|string[] $path, $callable)
 * @method Route options(string|string[] $path, $callable)
 * @method Route trace(string|string[] $path, $callable)
 * @method Route any(string|string[] $path, $callable)
 */
class Router
{
    /**
     * @var Response
     */
    private static $response;

    /**
     * @var Route[]
     */
    private static $routes = [];

    /**
     * @var array
     */
    private static $namedRoutes = [];

    /**
     * Router constructor.
     *
     * Block the constructor to avoid instantiation and force the static use
     * of the router.
     */
    final private function __construct(){}

    /**
     * Builds and appends many kinds of routes magically.
     *
     * @param string $method The HTTP method for the new route
     * @param $arguments
     * @return Route
     */
    public static function __callStatic($method, $arguments)
    {
        list($path, $callable) = $arguments;
        return static::route($method, $path, $callable);
    }

    public static function route($method, $path, $callable)
    {
        if (is_array($path)) {
            foreach ($path as $_path) {
                static::route($method, $_path, $callable);
            }
        }

        $route = new Route($method, $path, $callable);
        static::$routes[] = $route;
        if(is_string($callable)){
            static::$namedRoutes[$callable] = $route;
        }

        return $route;
    }

    public static function run(ServerRequestInterface $request = null)
    {
        return static::parse($request ?? static::request());
    }

    private static function request()
    {
        return new ServerRequest($_SERVER, $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
    }

    /**
     * @param ServerRequestInterface $request
     * @return mixed
     * @throws RoutingException
     */
    public static function parse(ServerRequestInterface $request)
    {
        foreach (static::$routes as $route) {
            if($route->match($request)){
                return $route->call();
            }
        }
        throw new RoutingException('No matching routes');
    }

    private static function response()
    {
        $response = new Response();
        static::$response = $response;
    }

    public function url($name, $params = [])
    {
        if (!isset(static::$namedRoutes[$name])) {
            throw new RoutingException('No route matches this name');
        }
        return static::$namedRoutes[$name]->getUrl($params);
    }
}