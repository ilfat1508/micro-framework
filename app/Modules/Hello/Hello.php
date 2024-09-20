<?php

namespace App\Modules\Hello;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Hello
{
    private $args;
    
    public function __construct($args = [])
    {
        $this->args = $args;
    }
    
    public function run(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $response->getBody()->write(' HELLO ');
        
        if ($this->args) $response->getBody()->write(implode(',', $this->args));
        
        return $response;
    }
}