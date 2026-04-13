<?php
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controllers/AlunniController.php';

$app = AppFactory::create();

$app->get('/test', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Test page");
    return $response;
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$app->get('/alunni', "AlunniController:index");
$app->get("/alunni/{id}", "AlunniController:show");
$app->post("/alunni", "AlunniController:create");
$app->put("/alunni/{id}", "AlunniController:update");
$app->delete("/alunni/{id}", "AlunniController:destroy");

$notFoundHandler = function (Request $request, Response $response, array $args) {
    $response->getBody()->write('404 - Not Found');
    return $response->withStatus(404);
};

$app->any('/', $notFoundHandler);
$app->any('/{routes:.+}', $notFoundHandler);

$app->run();
