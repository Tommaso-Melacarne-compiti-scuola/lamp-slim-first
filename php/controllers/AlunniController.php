<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/../singleton/DbSingleton.php';
require_once __DIR__ . '/../utils/ResponseUtils.php';

class AlunniController
{
  public function index(Request $request, Response $response, array $args){
    $mysqli_connection = DbSingleton::getInstance();
    $result = $mysqli_connection->query("SELECT * FROM alunni");
    $results = $result->fetch_all(MYSQLI_ASSOC);

    return ResponseUtils::json($response, $results, 200);
  }
  
  public function show(Request $request, Response $response, array $args){
    $mysqli_connection = DbSingleton::getInstance();
    $result = $mysqli_connection->prepare("SELECT * FROM alunni WHERE id = ?");
    $result->bind_param("i", $args["id"]);
    $result->execute();

    $query_result = $result->get_result();
    $data = $query_result->fetch_all(MYSQLI_ASSOC);

    return ResponseUtils::json($response, $data, 200);
  }

  public function create(Request $request, Response $response, array $args){
    $data = json_decode($request->getBody(), true);

    if (!isset($data['nome']) || !isset($data['cognome'])) {
        return ResponseUtils::json($response, [
            'error' => 'Missing required fields: nome and/or cognome'
        ], 400);
    }

    $mysqli_connection = DbSingleton::getInstance();
    $result = $mysqli_connection->prepare("INSERT INTO alunni (nome, cognome) VALUES (?, ?)");
    $result->bind_param("ss", $data['nome'], $data['cognome']);
    $execute_result = $result->execute();

    if (!$execute_result) {
      return ResponseUtils::json($response, [
          'error' => 'Failed to insert record into database'
      ], 500);
    }
    
    return ResponseUtils::json($response, ['message' => 'CREATED'], 201);
  }

  public function update(Request $request, Response $response, array $args){
    $data = json_decode($request->getBody(), true);

    if (!isset($data['nome']) || !isset($data['cognome'])) {
        return ResponseUtils::json($response, [
            'error' => 'Missing required fields: nome and/or cognome'
        ], 400);
    }

    $mysqli_connection = DbSingleton::getInstance();
    $result = $mysqli_connection->prepare("UPDATE alunni SET nome = ?, cognome = ? WHERE id = ?");
    $result->bind_param("ssi", $data['nome'], $data['cognome'], $args['id']);
    $execute_result = $result->execute();

    if (!$execute_result) {
      return ResponseUtils::json($response, [
          'error' => 'Failed to update record in the database'
      ], 500);
    }

    return ResponseUtils::json($response, ['message' => 'UPDATED'], 200);
  }

  public function destroy(Request $request, Response $response, array $args){
    $mysqli_connection = DbSingleton::getInstance();

    $result = $mysqli_connection->prepare("DELETE FROM alunni WHERE id = ?");
    $result->bind_param("i", $args['id']);
    $execute_result = $result->execute();

    if (!$execute_result) {
        return ResponseUtils::json($response, [
            'error' => 'Failed to delete record from database'
        ], 500);
    }

    return ResponseUtils::json($response, ['message' => 'DELETED'], 200);
  }
}

