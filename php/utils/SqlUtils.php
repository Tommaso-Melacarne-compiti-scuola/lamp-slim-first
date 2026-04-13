<?php

class SqlUtils
{
    public static function getAllowedColumns(mysqli $conn, string $table): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }

        $result = $conn->query("SHOW COLUMNS FROM `{$table}`");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        return $columns;
    }

    public static function filterPayload(array $data, array $allowedColumns, array $forbidden = []): array
    {
        foreach ($forbidden as $key) {
            unset($data[$key]);
        }

        $allowed = array_flip($allowedColumns);
        return array_intersect_key($data, $allowed);
    }

    public static function inferTypes(array $values): string
    {
        $types = '';
        foreach ($values as $v) {
            $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
        }
        return $types;
    }

    public static function bindDynamicParams(mysqli_stmt $stmt, string $types, array &$values): void
    {
        $bind = [$types];
        foreach ($values as $k => $v) {
            $bind[] = &$values[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
    }
}
