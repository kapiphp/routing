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
    private $routes = [];

    /**
     * @var array
     */
    private $namedRoutes = [];


    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    public function get($path, $callable, $name = null)
    {
        return $this->add($path, $callable, $name, 'GET');
    }

    private function add($path, $callable, $name, $method)
    {
        $route = new Route($path, $callable);
        $this->routes[$method][] = $route;
        if(is_string($callable) && $name === null){
            $name = $callable;
        }
        if($name){
            $this->namedRoutes[$name] = $route;
        }
        return $route;
    }

    public function run()
    {
        $url = $_SERVER['REQUEST_URI'];

        if(!isset($this->routes[$_SERVER['REQUEST_METHOD']])){
            throw new RoutingException('REQUEST_METHOD does not exist');
        }
        foreach($this->routes[$_SERVER['REQUEST_METHOD']] as $route){
            if($route->match($url)){
                return $route->call();
            }
        }
        throw new RoutingException('No matching routes');
    }

    public function url($name, $params = [])
    {
        if(!isset($this->namedRoutes[$name])){
            throw new RoutingException('No route matches this name');
        }
        return $this->namedRoutes[$name]->getUrl($params);
    }
}