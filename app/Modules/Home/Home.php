<?php

namespace App\Modules\Home;

use Psr\Http\Message\ResponseInterface;

class Home
{
    public function __invoke(ResponseInterface $response, $routerVars)
    {
        $response->getBody()->write(' Home ');
    }
}