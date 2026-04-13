<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/../singleton/DbSingleton.php';
require_once __DIR__ . '/../utils/ResponseUtils.php';
require_once __DIR__ . '/../utils/SqlUtils.php';

class CertificazioniNestedController
{
    public static function index(Request $request, Response $response, array $args)
    {
        $alunnoId = (int)($args['alunno_id'] ?? 0);
        $conn = DbSingleton::getInstance();

        $stmt = $conn->prepare("SELECT * FROM certificazioni WHERE alunno_id = ?");
        $stmt->bind_param('i', $alunnoId);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        return ResponseUtils::json($response, $rows);
    }

    public static function show(Request $request, Response $response, array $args)
    {
        $alunnoId = (int)($args['alunno_id'] ?? 0);
        $id = (int)($args['id'] ?? 0);
        $conn = DbSingleton::getInstance();

        $stmt = $conn->prepare("SELECT * FROM certificazioni WHERE id = ? AND alunno_id = ?");
        $stmt->bind_param('ii', $id, $alunnoId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            return ResponseUtils::json($response, ['message' => 'Certificazione non trovata'], 404);
        }

        return ResponseUtils::json($response, $row);
    }

    public static function create(Request $request, Response $response, array $args)
    {
        $alunnoId = (int)($args['alunno_id'] ?? 0);
        $conn = DbSingleton::getInstance();
        $allowedColumns = SqlUtils::getAllowedColumns($conn, 'certificazioni');

        $data = json_decode((string)$request->getBody(), true) ?? [];
        $data = SqlUtils::filterPayload($data, $allowedColumns, ['id', 'alunno_id']);
        $data['alunno_id'] = $alunnoId;

        if (empty($data)) {
            return ResponseUtils::json($response, ['message' => 'Payload vuoto o non valido'], 400);
        }

        $columns = array_keys($data);
        $quotedColumns = array_map(fn($c) => "`{$c}`", $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $values = array_values($data);
        $types = SqlUtils::inferTypes($values);

        $sql = "INSERT INTO certificazioni (" . implode(', ', $quotedColumns) . ") VALUES ({$placeholders})";
        $stmt = $conn->prepare($sql);

        SqlUtils::bindDynamicParams($stmt, $types, $values);

        if (!$stmt->execute()) {
            return ResponseUtils::json($response, ['message' => 'Errore in inserimento'], 500);
        }

        return ResponseUtils::json($response, ['id' => $conn->insert_id, 'message' => 'Certificazione creata'], 201);
    }

    public static function update(Request $request, Response $response, array $args)
    {
        $alunnoId = (int)($args['alunno_id'] ?? 0);
        $id = (int)($args['id'] ?? 0);
        $conn = DbSingleton::getInstance();
        $allowedColumns = SqlUtils::getAllowedColumns($conn, 'certificazioni');

        $data = json_decode((string)$request->getBody(), true) ?? [];
        $data = SqlUtils::filterPayload($data, $allowedColumns, ['id', 'alunno_id']);

        if (empty($data)) {
            return ResponseUtils::json($response, ['message' => 'Nessun campo da aggiornare'], 400);
        }

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "`{$column}` = ?";
        }

        $values = array_values($data);
        $values[] = $id;
        $values[] = $alunnoId;
        $types = SqlUtils::inferTypes(array_values($data)) . 'ii';

        $sql = "UPDATE certificazioni SET " . implode(', ', $sets) . " WHERE id = ? AND alunno_id = ?";
        $stmt = $conn->prepare($sql);

        SqlUtils::bindDynamicParams($stmt, $types, $values);

        if (!$stmt->execute()) {
            return ResponseUtils::json($response, ['message' => 'Errore in aggiornamento'], 500);
        }

        if ($stmt->affected_rows === 0) {
            return ResponseUtils::json($response, ['message' => 'Certificazione non trovata o nessuna modifica'], 404);
        }

        return ResponseUtils::json($response, ['message' => 'Certificazione aggiornata']);
    }

    public static function destroy(Request $request, Response $response, array $args)
    {
        $alunnoId = (int)($args['alunno_id'] ?? 0);
        $id = (int)($args['id'] ?? 0);
        $conn = DbSingleton::getInstance();

        $stmt = $conn->prepare("DELETE FROM certificazioni WHERE id = ? AND alunno_id = ?");
        $stmt->bind_param('ii', $id, $alunnoId);

        if (!$stmt->execute()) {
            return ResponseUtils::json($response, ['message' => 'Errore in eliminazione'], 500);
        }

        if ($stmt->affected_rows === 0) {
            return ResponseUtils::json($response, ['message' => 'Certificazione non trovata'], 404);
        }

        return ResponseUtils::json($response, ['message' => 'Certificazione eliminata']);
    }
}

