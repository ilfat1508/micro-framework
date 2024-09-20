<?php

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use mindplay\middleman\Dispatcher;
use mindplay\middleman\ContainerResolver;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Server\RequestHandlerInterface;

define('BASE_DIR', dirname(realpath(__FILE__), 2) . DIRECTORY_SEPARATOR);
define('APP_BASEPATH', '/' . basename(dirname(__DIR__)) . '/');

require BASE_DIR . 'app/bootstrap.php';

$container = new DI\Container();

$psr17Factory = new Psr17Factory();

$creator = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory
);

$serverRequest = $creator->fromGlobals();

$container->set(Psr17Factory::class, \DI\create(Psr17Factory::class));
$container->set('debug', true);

$routerDispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', App\Modules\Home\Home::class);
    $r->addRoute('GET', '/hello', ['App\Modules\Hello\Hello', 'run']);
    $r->addRoute('GET', '/hello/{slug}', ['App\Modules\Hello\Hello', 'run']);
});

$uri = $serverRequest->getUri()->getPath();
$uri = str_replace(APP_BASEPATH, '/', $uri);

$routeInfo = $routerDispatcher->dispatch($serverRequest->getMethod(), $uri);

$routerHandler = '';
$routerVars = '';
$routerResult = 'FOUND';

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $routerResult = 'Not Found';
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        $routerResult = 'Method Not Allowed';
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $routerHandler = $handler;
        $routerVars = $vars;
        break;
}

function runRouting (Psr\Http\Message\ResponseInterface $response, $routerHandler, $routerVars) {
    if (is_string($routerHandler)) {
        $class = $routerHandler;
        $method = false;
    } else {
        $class = $routerHandler[0];
        $method = $routerHandler[1];
    }
    
    if(!class_exists($class)) {
        return;
    }
    
    if ($method) {
        $obj = new $class($response, $routerVars);
        return $obj->$method();
    } else {
        $obj = new $class($response, $routerVars);
        
        if (method_exists($obj, '__invoke')) {
            return $obj($response, $routerVars);
        } else {
            return new $class($response, $routerVars);
        }
    }
}

runRouting($response, $routerHandler, $routerVars);
(new SapiEmitter())->emit($response);

$dispatcherMiddleware = new Dispatcher(
    [
        App\Middleware\AfterMiddleware::class,
        App\Middleware\DemoMiddleware::class,
        App\Middleware\BeforeMiddleware::class,
        
        function(ServerRequestInterface $request) use ($psr17Factory) {
            return $psr17Factory->createResponse();
        },
    ], new ContainerResolver($container)
);

$response = $dispatcherMiddleware->handle($serverRequest);
(new SapiEmitter())->emit($response);
$a = 0;
