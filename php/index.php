<?php
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/controllers/AlunniController.php';
require_once __DIR__ . '/controllers/CertificazioniController.php';
require_once __DIR__ . '/controllers/CertificazioniNestedController.php';

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

// CRUD-style routes for certificazioni
$app->get("/certificazioni", "CertificazioniController:index");
$app->get("/certificazioni/{id}", "CertificazioniController:show");
$app->post("/certificazioni", "CertificazioniController:create");
$app->put("/certificazioni/{id}", "CertificazioniController:update");
$app->delete("/certificazioni/{id}", "CertificazioniController:destroy");

// Nested-style routes for certificazioni related to a specific alunno
$app->get("/alunni/{alunno_id}/cert", "CertificazioniNestedController:index");
$app->get("/alunni/{alunno_id}/cert/{id}", "CertificazioniNestedController:show");
$app->post("/alunni/{alunno_id}/cert", "CertificazioniNestedController:create");
$app->put("/alunni/{alunno_id}/cert/{id}", "CertificazioniNestedController:update");
$app->delete("/alunni/{alunno_id}/cert/{id}", "CertificazioniNestedController:destroy");




$notFoundHandler = function (Request $request, Response $response, array $args) {
    $response->getBody()->write('404 - Not Found');
    return $response->withStatus(404);
};

$app->any('/', $notFoundHandler);
$app->any('/{routes:.+}', $notFoundHandler);

$app->run();
