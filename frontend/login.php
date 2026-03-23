<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

include('./includes/config.php');

function ensure_users_table(mysqli $conn): bool {
    $sql = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(100) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `fullname` VARCHAR(120) NOT NULL,
            `username` VARCHAR(50) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_users_email` (`email`),
            UNIQUE KEY `uniq_users_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    return (bool) $conn->query($sql);
}

function ensure_users_password_column(mysqli $conn): bool {
    $result = $conn->query("SHOW COLUMNS FROM `users` LIKE 'password'");
    if (!($result instanceof mysqli_result) || $result->num_rows === 0) {
        return false;
    }

    $column = $result->fetch_assoc();
    $type = isset($column['Type']) ? strtolower((string) $column['Type']) : '';

    if (preg_match('/^varchar\((\d+)\)$/', $type, $m)) {
        $length = (int) $m[1];
        if ($length >= 255) {
            return true;
        }
    } elseif ($type === 'text' || $type === 'longtext') {
        return true;
    }

    return (bool) $conn->query("ALTER TABLE `users` MODIFY `password` VARCHAR(255) NOT NULL");
}

function has_column(mysqli $conn, string $tableName, string $columnName): bool {
    $safeTable = str_replace('`', '``', $tableName);
    $safeColumn = $conn->real_escape_string($columnName);
    $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function ensure_access_logs_table(mysqli $conn): bool {
    $sql = "
        CREATE TABLE IF NOT EXISTS `access_logs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `evento` ENUM('registro', 'login') NOT NULL,
            `email` VARCHAR(100) NULL,
            `username` VARCHAR(50) NULL,
            `fullname` VARCHAR(120) NULL,
            `resultado` ENUM('ok', 'error') NOT NULL,
            `mensaje` VARCHAR(255) NULL,
            `ip` VARCHAR(45) NULL,
            `user_agent` VARCHAR(255) NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_access_logs_evento` (`evento`),
            KEY `idx_access_logs_email` (`email`),
            KEY `idx_access_logs_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    return (bool) $conn->query($sql);
}

function get_access_log_columns(mysqli $conn): array {
    $result = $conn->query("SHOW COLUMNS FROM `access_logs`");
    if (!($result instanceof mysqli_result)) {
        return [];
    }

    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $field = $row['Field'];
        $columns[$field] = [
            'nullable' => isset($row['Null']) ? ($row['Null'] === 'YES') : true,
            'default' => $row['Default'] ?? null,
        ];
    }
    return $columns;
}

function log_access(mysqli $conn, string $evento, ?string $email, ?string $username, ?string $fullname, string $resultado, string $mensaje, ?int $userId = null): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $available = get_access_log_columns($conn);
    if (empty($available)) {
        return;
    }

    $fields = [];
    $values = [];
    $types = '';

    $fields[] = 'evento';
    $values[] = $evento;
    $types .= 's';

    if (isset($available['user_id'])) {
        $requiresUserId = !$available['user_id']['nullable'] && $available['user_id']['default'] === null;
        if ($userId === null && $requiresUserId) {
            return;
        }
        $fields[] = 'user_id';
        $values[] = $userId ?? 0;
        $types .= 'i';
    }

    if (isset($available['email'])) {
        $fields[] = 'email';
        $values[] = $email;
        $types .= 's';
    }
    if (isset($available['username'])) {
        $fields[] = 'username';
        $values[] = $username;
        $types .= 's';
    }
    if (isset($available['fullname'])) {
        $fields[] = 'fullname';
        $values[] = $fullname;
        $types .= 's';
    }

    $fields[] = 'resultado';
    $values[] = $resultado;
    $types .= 's';

    $fields[] = 'mensaje';
    $values[] = $mensaje;
    $types .= 's';

    if (isset($available['ip'])) {
        $fields[] = 'ip';
        $values[] = $ip;
        $types .= 's';
    }
    if (isset($available['user_agent'])) {
        $fields[] = 'user_agent';
        $values[] = $userAgent;
        $types .= 's';
    }

    $placeholders = rtrim(str_repeat('?, ', count($fields)), ', ');
    $columnsSql = '`' . implode('`, `', $fields) . '`';

    $stmt = $conn->prepare("INSERT INTO access_logs ({$columnsSql}) VALUES ({$placeholders})");
    if (!$stmt) {
        return;
    }

    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $stmt->close();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["success" => false, "mensaje" => "Metodo no permitido"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $email = isset($_POST['login-email']) ? trim((string) $_POST['login-email']) : '';
    $password = isset($_POST['login-password']) ? (string) $_POST['login-password'] : '';

    $conn = new mysqli($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "mensaje" => "Error de conexion: " . $conn->connect_error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $conn->set_charset('utf8mb4');

    if (!ensure_users_table($conn)) {
        echo json_encode(["success" => false, "mensaje" => "No se pudo crear/verificar la tabla users: " . $conn->error], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    if (!ensure_users_password_column($conn)) {
        echo json_encode(["success" => false, "mensaje" => "No se pudo ajustar la columna password en users: " . $conn->error], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    if (!ensure_access_logs_table($conn)) {
        echo json_encode(["success" => false, "mensaje" => "No se pudo crear/verificar la tabla access_logs: " . $conn->error], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    if ($email === '' || $password === '') {
        $msg = 'Todos los campos son obligatorios';
        log_access($conn, 'login', $email !== '' ? $email : null, null, null, 'error', $msg);
        echo json_encode(["success" => false, "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Correo electronico invalido';
        log_access($conn, 'login', $email, null, null, 'error', $msg);
        echo json_encode(["success" => false, "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    $selectId = has_column($conn, 'users', 'id');
    $columns = $selectId
        ? 'id, email, username, fullname, password'
        : 'email, username, fullname, password';
    $stmt = $conn->prepare("SELECT {$columns} FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        $msg = 'Error al preparar consulta: ' . $conn->error;
        log_access($conn, 'login', $email, null, null, 'error', $msg);
        echo json_encode(["success" => false, "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!($result instanceof mysqli_result) || $result->num_rows === 0) {
        $msg = 'Credenciales invalidas';
        log_access($conn, 'login', $email, null, null, 'error', $msg);
        echo json_encode(["success" => false, "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
        $stmt->close();
        $conn->close();
        exit;
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, (string) $user['password'])) {
        $msg = 'Credenciales invalidas';
        log_access($conn, 'login', $email, (string) $user['username'], (string) $user['fullname'], 'error', $msg, $selectId ? (int) $user['id'] : null);
        echo json_encode(["success" => false, "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
        $stmt->close();
        $conn->close();
        exit;
    }

    $_SESSION['user_id'] = $selectId ? (int) $user['id'] : 0;
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['fullname'] = (string) $user['fullname'];

    $cookieExpire = time() + (60 * 60 * 24 * 30);
    setcookie('lh_user', (string) $user['username'], $cookieExpire, '/');
    setcookie('lh_email', (string) $user['email'], $cookieExpire, '/');

    log_access(
        $conn,
        'login',
        (string) $user['email'],
        (string) $user['username'],
        (string) $user['fullname'],
        'ok',
        'Inicio de sesion exitoso',
        $selectId ? (int) $user['id'] : null
    );

    echo json_encode([
        "success" => true,
        "mensaje" => "Inicio de sesion exitoso",
        "usuario" => [
            "id" => $selectId ? (int) $user['id'] : 0,
            "email" => (string) $user['email'],
            "username" => (string) $user['username'],
            "fullname" => (string) $user['fullname']
        ]
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();
    $conn->close();
} catch (Throwable $e) {
    echo json_encode(["success" => false, "mensaje" => "Error del servidor: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
