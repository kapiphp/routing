<?php

namespace Kapi\Routing;

use Kapi\Http\Request;
use Kapi\Http\Response;
use Kapi\Routing\Exception\RoutingException;
use Kapi\Routing\Route\Route;

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


    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    /**
     * @inheritDoc
     */
    public static function __callStatic($name, $arguments)
    {
        list($path, $callable) = $arguments;
        return static::route($name, $path, $callable);
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