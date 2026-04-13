<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/../singleton/DbSingleton.php';
require_once __DIR__ . '/../utils/ResponseUtils.php';

class CertificazioniController
{
    private static function getAllowedColumns(mysqli $conn): array
    {
        $result = $conn->query("SHOW COLUMNS FROM certificazioni");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        return $columns;
    }

    private static function filterPayload(array $data, array $allowedColumns, array $forbidden = []): array
    {
        foreach ($forbidden as $key) {
            unset($data[$key]);
        }

        $allowed = array_flip($allowedColumns);
        return array_intersect_key($data, $allowed);
    }

    private static function inferTypes(array $values): string
    {
        $types = '';
        foreach ($values as $v) {
            $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
        }
        return $types;
    }

    public static function index(Request $request, Response $response, array $args)
    {
        $conn = DbSingleton::getInstance();
        $stmt = $conn->prepare("SELECT * FROM certificazioni");
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        return ResponseUtils::json($response, $rows);
    }

    public static function show(Request $request, Response $response, array $args)
    {
        $id = (int)($args['id'] ?? 0);
        $conn = DbSingleton::getInstance();

        $stmt = $conn->prepare("SELECT * FROM certificazioni WHERE id = ?");
        $stmt->bind_param('i', $id);
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
        $conn = DbSingleton::getInstance();
        $allowedColumns = self::getAllowedColumns($conn);

        $data = json_decode((string)$request->getBody(), true) ?? [];
        $data = self::filterPayload($data, $allowedColumns, ['id']);

        if (empty($data)) {
            return ResponseUtils::json($response, ['message' => 'Payload vuoto o non valido'], 400);
        }

        $columns = array_keys($data);
        $quotedColumns = array_map(fn($c) => "`{$c}`", $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $values = array_values($data);
        $types = self::inferTypes($values);

        $sql = "INSERT INTO certificazioni (" . implode(', ', $quotedColumns) . ") VALUES ({$placeholders})";
        $stmt = $conn->prepare($sql);

        $bind = [$types];
        foreach ($values as $k => $v) {
            $bind[] = &$values[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);

        if (!$stmt->execute()) {
            return ResponseUtils::json($response, ['message' => 'Errore in inserimento'], 500);
        }

        return ResponseUtils::json($response, ['id' => $conn->insert_id, 'message' => 'Certificazione creata'], 201);
    }

    public static function update(Request $request, Response $response, array $args)
    {
        $id = (int)($args['id'] ?? 0);
        $conn = DbSingleton::getInstance();
        $allowedColumns = self::getAllowedColumns($conn);

        $data = json_decode((string)$request->getBody(), true) ?? [];
        $data = self::filterPayload($data, $allowedColumns, ['id']);

        if (empty($data)) {
            return ResponseUtils::json($response, ['message' => 'Nessun campo da aggiornare'], 400);
        }

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "`{$column}` = ?";
        }

        $values = array_values($data);
        $values[] = $id;
        $types = self::inferTypes(array_values($data)) . 'i';

        $sql = "UPDATE certificazioni SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);

        $bind = [$types];
        foreach ($values as $k => $v) {
            $bind[] = &$values[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);

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
        $id = (int)($args['id'] ?? 0);
        $conn = DbSingleton::getInstance();

        $stmt = $conn->prepare("DELETE FROM certificazioni WHERE id = ?");
        $stmt->bind_param('i', $id);

        if (!$stmt->execute()) {
            return ResponseUtils::json($response, ['message' => 'Errore in eliminazione'], 500);
        }

        if ($stmt->affected_rows === 0) {
            return ResponseUtils::json($response, ['message' => 'Certificazione non trovata'], 404);
        }

        return ResponseUtils::json($response, ['message' => 'Certificazione eliminata']);
    }
}

