<?php

namespace Kapi\Routing\Route;

use Psr\Http\Message\ServerRequestInterface;

class Route
{
    private $method;
    private $path;
    private $callable;
    private $matches = [];

    /**
     * @var array
     */
    private $pattern;

    public function __construct($method, $path, $callable)
    {
        $this->method = $method;
        $this->path = trim($path, '/');
        $this->callable = $callable;
    }

    public function match(ServerRequestInterface $request)
    {
        $url = $request->getUri()->__toString();
        $url = trim($url, '/');

        $path = preg_replace_callback('#:([\w]+)#', [$this, 'paramMatch'], $this->path);
        $regex = "#^$path$#i";
        if(!preg_match($regex, $url, $matches)){
            return false;
        }
        array_shift($matches);
        $this->matches = $matches;
        return true;
    }

    private function paramMatch($match)
    {
        if(isset($this->pattern[$match[1]])){
            return '(' . $this->pattern[$match[1]] . ')';
        }
        return '([^/]+)';
    }

    public function call()
    {
        if(is_string($this->callable)){
            $params = explode('#', $this->callable);
            $controller = "App\\Controller\\" . $params[0] . "Controller";
            $controller = new $controller();
            return call_user_func_array([$controller, $params[1]], $this->matches);
        } else {
            return call_user_func_array($this->callable, $this->matches);
        }
    }

    public function getPattern($key)
    {
        return $this->pattern[$key] ?? null;
    }

    public function setPattern($key, $regex)
    {
        $this->pattern[$key] = $regex;
    }

    public function getUrl($params)
    {
        $path = $this->path;
        foreach($params as $k => $v){
            $path = str_replace(":$k", $v, $path);
        }
        return $path;
    }
}