<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// GET            /alunni           AlunniController:index
// GET            /alunni/{id}    AlunniController:show
// POST         /alunni           AlunniController:create
// PUT            /alunni/{id}    AlunniController:update
// DELETE     /alunni/{id}    AlunniController:destroy

require __DIR__ . '/../singleton/DbSingleton.php';

class AlunniController
{
  public function index(Request $request, Response $response, array $args){
    $mysqli_connection = DbSingleton::getInstance();
    $result = $mysqli_connection->query("SELECT * FROM alunni");
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }
  
  public function show(Request $request, Response $response, array $args){
    $mysqli_connection = DbSingleton::getInstance();
    $result = $mysqli_connection->prepare("SELECT * FROM alunni WHERE id = ?");
    $result->bind_param("s", $args["id"]);
    $result->execute();

    $query_result = $result->get_result();
    $data = $query_result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($data));
    return $response->withHeader("Content-Type", "application/json")->withStatus(200);
  }

  public function create(Request $request, Response $response, array $args){
    $data = json_decode($request->getBody(), true);

    if (!isset($data['nome']) || !isset($data['cognome'])) {
        // If data is missing, return an error response
        $response->getBody()->write(json_encode([
            'error' => 'Missing required fields: nome and/or cognome'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $mysqli_connection = DbSingleton::getInstance();

    $result = $mysqli_connection->prepare("INSERT INTO alunni (nome, cognome) VALUES (?, ?)");

    $result->bind_param("ss", $data['nome'], $data['cognome']);

    $execute_result = $result->execute();

    if (!$execute_result) {
      $response->getBody()->write(json_encode([
          'error' => 'Failed to insert record into database'
      ]));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
    
    $response->getBody()->write("CREATED");
    return $response->withStatus(201);
  }

  public function update(Request $request, Response $response, array $args){
    $data = json_decode($request->getBody(), true);

    if (!isset($data['nome']) || !isset($data['cognome'])) {
        $response->getBody()->write(json_encode([
            'error' => 'Missing required fields: nome and/or cognome'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $mysqli_connection = DbSingleton::getInstance();

    $result = $mysqli_connection->prepare("UPDATE alunni SET nome = ?, cognome = ? WHERE id = ?");
    $result->bind_param("ssi", $data['nome'], $data['cognome'], $args['id']);

    $execute_result = $result->execute();

    if (!$execute_result) {
      $response->getBody()->write(json_encode([
          'error' => 'Failed to update record in the database'
      ]));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $response->getBody()->write("UPDATED");
    return $response->withStatus(200);
  }

  public function destroy(Request $request, Response $response, array $args){
    $mysqli_connection = DbSingleton::getInstance();

    $result = $mysqli_connection->prepare("DELETE FROM alunni WHERE id = ?");
    $result->bind_param("s", $args['id']);
    $execute_result = $result->execute();

    if (!$execute_result) {
        $response->getBody()->write(json_encode([
            'error' => 'Failed to delete record from database'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $response->getBody()->write("DELETED");
    return $response->withStatus(200);
  }
}

