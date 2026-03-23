<?php
header('Content-Type: application/json');

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
        echo json_encode(["success" => false, "mensaje" => "Metodo no permitido"]);
        exit();
    }

    $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $email = isset($_POST['register-email']) ? trim((string) $_POST['register-email']) : '';
    $fullName = isset($_POST['register-name']) ? trim((string) $_POST['register-name']) : '';
    $plainPassword = isset($_POST['register-password']) ? (string) $_POST['register-password'] : '';

    $conn = new mysqli($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);

    if ($conn->connect_error) {
        echo json_encode(["success" => false, "mensaje" => "Error de conexion: " . $conn->connect_error]);
        exit();
    }

    $conn->set_charset('utf8mb4');

    if (!ensure_users_table($conn)) {
        echo json_encode(["success" => false, "mensaje" => "No se pudo crear/verificar la tabla users: " . $conn->error]);
        exit();
    }

    if (!ensure_users_password_column($conn)) {
        echo json_encode(["success" => false, "mensaje" => "No se pudo ajustar la columna password en users: " . $conn->error]);
        exit();
    }

    if (!ensure_access_logs_table($conn)) {
        echo json_encode(["success" => false, "mensaje" => "No se pudo crear/verificar la tabla access_logs: " . $conn->error]);
        exit();
    }

    if ($username === '' || $email === '' || $plainPassword === '' || $fullName === '') {
        $msg = 'Todos los campos son requeridos';
        log_access($conn, 'registro', $email !== '' ? $email : null, $username !== '' ? $username : null, $fullName !== '' ? $fullName : null, 'error', $msg);
        echo json_encode(["success" => false, "mensaje" => $msg]);
        exit();
    }

    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (email, password, fullname, username) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $hashedPassword, $fullName, $username);

    if ($stmt->execute()) {
        $newUserId = (int) $conn->insert_id;
        log_access($conn, 'registro', $email, $username, $fullName, 'ok', 'Registro exitoso', $newUserId > 0 ? $newUserId : null);
        echo json_encode(["success" => true, "mensaje" => "Registro exitoso"]);
    } else {
        if ($conn->errno == 1062) {
            $msg = 'El email o usuario ya esta registrado';
            log_access($conn, 'registro', $email, $username, $fullName, 'error', $msg);
            echo json_encode(["success" => false, "mensaje" => $msg]);
        } else {
            $msg = 'Error al guardar: ' . $stmt->error;
            log_access($conn, 'registro', $email, $username, $fullName, 'error', $msg);
            echo json_encode(["success" => false, "mensaje" => $msg]);
        }
    }

    $stmt->close();
    $conn->close();
} catch (Throwable $e) {
    echo json_encode(["success" => false, "mensaje" => "Error del servidor: " . $e->getMessage()]);
}
