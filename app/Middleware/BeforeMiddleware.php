<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

use Nyholm\Psr7\Factory\Psr17Factory;
use DI\Container;

class BeforeMiddleware
{
    private $container;
    private $psr17Factory;
    
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->psr17Factory = $container->get(Psr17Factory::class);
    }
    
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $existingContent = (string) $response->getBody();
        
        //Новый response
        $response = $this->psr17Factory->createResponse();
        $response->getBody()->write('BEFORE ' . $existingContent);
        
        return $response;
    }
}