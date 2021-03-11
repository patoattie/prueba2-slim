<?php

// use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;

// Captura de Errores
use App\Clases\HttpErrorHandler;
use App\Clases\ShutdownHandler;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Factory\ServerRequestCreatorFactory;
//-------------------

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Captura de Errores
$callableResolver = $app->getCallableResolver();
$responseFactory = $app->getResponseFactory();

$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);
$shutdownHandler = new ShutdownHandler($request, $errorHandler, true);
register_shutdown_function($shutdownHandler);
//-------------------

/**
 * Example middleware closure
 *
 * @param  ServerRequest  $request PSR-7 request
 * @param  RequestHandler $handler PSR-15 request handler
 *
 * @return Response
 */
$beforeMiddleware = function (Request $request, RequestHandler $handler) {
    $request = $request->withAttribute('b', 'b');
    $response = $handler->handle($request);
    $existingContent = (string) $response->getBody();

    $response = new Response();
    $response->getBody()->write('BEFORE' . $existingContent);
    
    return $response;
};

$afterMiddleware = function (Request $request, RequestHandler $handler) {
    $request = $request->withAttribute('a', 'a');
    $response = $handler->handle($request);
    $response->getBody()->write('AFTER');

    return $response;
};

$app->get('[/]', function(Request $request, Response $response, array $args) {
    $response->getBody()->write('Hola Mundo!');
    return $response;
});

$app->get('/foo[/]', function(Request $request, Response $response, array $args) {
    $payload = json_encode(['hola' => 'mundo'], JSON_PRETTY_PRINT);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Handling Middleware
$errorMiddleware = $app->addErrorMiddleware(true, false, false);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

$app->add($beforeMiddleware)->add($afterMiddleware);

$app->run();
?>