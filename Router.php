<?php

namespace Kapi\Routing;

use Kapi\Http\Request;
use Kapi\Http\Response;
use Kapi\Routing\Exception\RoutingException;
use Kapi\Routing\Route\Route;

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
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Route[][]
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

        $route = new Route($path, $callable);
        static::$routes[strtoupper($method)][] = $route;
        if(is_string($callable)){
            static::$namedRoutes[$callable] = $route;
        }

        return $route;
    }

    public static function run()
    {
        $url = $_SERVER['REQUEST_URI'];

        if(!isset(static::$routes[$_SERVER['REQUEST_METHOD']])){
            throw new RoutingException('REQUEST_METHOD does not exist');
        }
        foreach(static::$routes[$_SERVER['REQUEST_METHOD']] as $route){
            if($route->match($url)){
                return $route->call();
            }
        }
        throw new RoutingException('No matching routes');
    }

    public function url($name, $params = [])
    {
        if(!isset(static::$namedRoutes[$name])){
            throw new RoutingException('No route matches this name');
        }
        return static::$namedRoutes[$name]->getUrl($params);
    }
}