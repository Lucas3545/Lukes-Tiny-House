<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/config.php';

$response = [
    'success' => false,
    'stats' => [
        'users' => 0,
        'reservations' => 0,
        'cards' => 0,
    ],
    'nextReservations' => [],
    'warnings' => [],
    'error' => null,
];

function table_exists(mysqli $conn, string $tableName): bool {
    $safeName = $conn->real_escape_string($tableName);
    $result = $conn->query("SHOW TABLES LIKE '{$safeName}'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function first_existing_table(mysqli $conn, array $tableNames): ?string {
    foreach ($tableNames as $tableName) {
        if (table_exists($conn, $tableName)) {
            return $tableName;
        }
    }
    return null;
}

function has_columns(mysqli $conn, string $tableName, array $columns): bool {
    $safeTable = str_replace('`', '``', $tableName);
    $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}`");
    if (!($result instanceof mysqli_result)) {
        return false;
    }

    $available = [];
    while ($row = $result->fetch_assoc()) {
        $available[$row['Field']] = true;
    }

    foreach ($columns as $column) {
        if (!isset($available[$column])) {
            return false;
        }
    }

    return true;
}

function create_cards_table(mysqli $conn): bool {
    $sql = "
        CREATE TABLE IF NOT EXISTS `cards` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `numero` VARCHAR(20) NOT NULL,
            `nombre_tarjeta` VARCHAR(100) NOT NULL,
            `vencimiento` VARCHAR(7) NOT NULL,
            `cvv` VARCHAR(4) NOT NULL,
            `banco` VARCHAR(50) NULL,
            `red_de_pago` VARCHAR(20) NULL,
            `email_usuario` VARCHAR(100) NULL,
            `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_cards_email` (`email_usuario`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    return (bool) $conn->query($sql);
}

try {
    $dbHost = $DB_CONSUSLT ?? $DB_CONSULT ?? $DB_HOSTNAME;
    $dbName = $DB_TEXT ?? $DB_NAME;
    $dbPass = $D_ANSWER ?? $DB_ANSWER ?? $DB_PASSWORD;
    $dbUser = $DB_USERNAME ?? 'root';

    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        throw new RuntimeException('Error de conexion: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    $usersTable = first_existing_table($conn, ['users']);
    $reservationsTable = first_existing_table($conn, ['reservations', 'reservas']);
    $cardsTable = first_existing_table($conn, ['cards', 'ValidacionTarjetas', 'tarjetas']);
    if ($cardsTable === null && create_cards_table($conn)) {
        $cardsTable = 'cards';
    }

    if ($usersTable !== null) {
        $usersResult = $conn->query("SELECT COUNT(*) AS total FROM `{$usersTable}`");
        if ($usersResult && $row = $usersResult->fetch_assoc()) {
            $response['stats']['users'] = (int) $row['total'];
        }
    } else {
        $response['warnings'][] = "La tabla 'users' no existe.";
    }

    if ($reservationsTable !== null) {
        $reservationsCountQuery = has_columns($conn, $reservationsTable, ['estado'])
            ? "SELECT COUNT(*) AS total FROM `{$reservationsTable}` WHERE estado = 'confirmada'"
            : "SELECT COUNT(*) AS total FROM `{$reservationsTable}`";
        $reservationsResult = $conn->query($reservationsCountQuery);
        if ($reservationsResult && $row = $reservationsResult->fetch_assoc()) {
            $response['stats']['reservations'] = (int) $row['total'];
        }
    } else {
        $response['warnings'][] = "No existe una tabla de reservas (reservations/reservas).";
    }

    if ($cardsTable !== null) {
        $cardsResult = $conn->query("SELECT COUNT(*) AS total FROM `{$cardsTable}`");
        if ($cardsResult && $row = $cardsResult->fetch_assoc()) {
            $response['stats']['cards'] = (int) $row['total'];
        }
    } else {
        $response['warnings'][] = "No existe una tabla de tarjetas (cards/ValidacionTarjetas/tarjetas) y no se pudo crear automaticamente.";
    }

    if ($reservationsTable !== null) {
        $hasNombre = has_columns($conn, $reservationsTable, ['nombre']);
        $hasFecha = has_columns($conn, $reservationsTable, ['fecha']);
        $hasUserId = has_columns($conn, $reservationsTable, ['user_id']);
        $hasEstado = has_columns($conn, $reservationsTable, ['estado']);

        if ($hasNombre && $hasFecha) {
            $statusColumn = $hasEstado ? 'estado' : "'confirmada' AS estado";
            $nextReservationsQuery = "
                SELECT nombre, fecha, estado
                FROM (
                    SELECT nombre, fecha, {$statusColumn}
                    FROM `{$reservationsTable}`
                ) AS r
                WHERE fecha >= CURDATE() AND estado = 'confirmada'
                ORDER BY fecha ASC
                LIMIT 5
            ";
        } elseif ($hasUserId && $hasFecha && $usersTable !== null) {
            $userNameParts = [];
            if (has_columns($conn, $usersTable, ['fullname'])) {
                $userNameParts[] = 'u.fullname';
            }
            if (has_columns($conn, $usersTable, ['username'])) {
                $userNameParts[] = 'u.username';
            }

            $nameExpr = count($userNameParts) > 0
                ? 'COALESCE(' . implode(', ', $userNameParts) . ')'
                : "CONCAT('Usuario #', r.user_id)";
            $statusExpr = $hasEstado ? 'r.estado' : "'confirmada'";
            $statusFilter = $hasEstado ? " AND r.estado = 'confirmada'" : '';
            $nextReservationsQuery = "
                SELECT {$nameExpr} AS nombre, r.fecha, {$statusExpr} AS estado
                FROM `{$reservationsTable}` r
                LEFT JOIN `{$usersTable}` u ON u.id = r.user_id
                WHERE r.fecha >= CURDATE(){$statusFilter}
                ORDER BY r.fecha ASC
                LIMIT 5
            ";
        } else {
            $nextReservationsQuery = null;
            $response['warnings'][] = "La tabla '{$reservationsTable}' no tiene columnas compatibles (nombre/fecha o user_id/fecha).";
        }

        if (!empty($nextReservationsQuery)) {
            $nextReservationsResult = $conn->query($nextReservationsQuery);
            if ($nextReservationsResult) {
                while ($reservation = $nextReservationsResult->fetch_assoc()) {
                    $response['nextReservations'][] = [
                        'nombre' => $reservation['nombre'],
                        'fecha' => $reservation['fecha'],
                        'estado' => $reservation['estado'],
                    ];
                }
            }
        }
    }

    $conn->close();
    $response['success'] = true;
} catch (Throwable $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
