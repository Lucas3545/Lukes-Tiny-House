<?php
require_once __DIR__ . '/admin-common.php';

$isOwner = admin_is_owner();
$actionMessage = null;
$actionError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    admin_require_owner();
    $action = trim((string) $_POST['action']);

    try {
        $conn = admin_db_connect();

        switch ($action) {
        case 'delete_user':
            $usersTable = admin_first_table($conn, ['users']);
            if ($usersTable === null) {
                throw new RuntimeException('No existe la tabla users.');
            }

            $hasId = admin_has_column($conn, $usersTable, 'id');
            $hasEmail = admin_has_column($conn, $usersTable, 'email');
            $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
            $email = trim((string) ($_POST['email'] ?? ''));

            if ($hasEmail && $email !== '' && strcasecmp($email, admin_owner_email()) === 0) {
                throw new RuntimeException('No se puede eliminar el usuario propietario.');
            }

            if ($hasId && $userId > 0) {
                $stmt = $conn->prepare("DELETE FROM `{$usersTable}` WHERE id = ? LIMIT 1");
                if (!$stmt) {
                    throw new RuntimeException('Error al preparar eliminacion de usuario: ' . $conn->error);
                }
                $stmt->bind_param('i', $userId);
            } elseif ($hasEmail && $email !== '') {
                $stmt = $conn->prepare("DELETE FROM `{$usersTable}` WHERE email = ? LIMIT 1");
                if (!$stmt) {
                    throw new RuntimeException('Error al preparar eliminacion de usuario: ' . $conn->error);
                }
                $stmt->bind_param('s', $email);
            } else {
                throw new RuntimeException('No se pudo identificar al usuario a eliminar.');
            }

            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $actionMessage = 'Usuario eliminado con exito.';
            } else {
                $actionError = 'No se encontro el usuario para eliminar.';
            }
            $stmt->close();
            break;
        case 'delete_reservation':
            $reservationsTable = admin_first_table($conn, ['reservations', 'reservas']);
            if ($reservationsTable === null) {
                throw new RuntimeException('No existe la tabla de reservas.');
            }

            $usersTable = admin_first_table($conn, ['users']);
            $hasId = admin_has_column($conn, $reservationsTable, 'id');
            $hasFecha = admin_has_column($conn, $reservationsTable, 'fecha');
            $hasEmail = admin_has_column($conn, $reservationsTable, 'email');
            $hasUserId = admin_has_column($conn, $reservationsTable, 'user_id');
            $hasUsersEmail = $usersTable !== null && admin_has_column($conn, $usersTable, 'email');
            $reservationId = isset($_POST['reservation_id']) ? (int) $_POST['reservation_id'] : 0;
            $fecha = trim((string) ($_POST['fecha'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));

            if ($hasId && $reservationId > 0) {
                $stmt = $conn->prepare("DELETE FROM `{$reservationsTable}` WHERE id = ? LIMIT 1");
                if (!$stmt) {
                    throw new RuntimeException('Error al preparar eliminacion de reserva: ' . $conn->error);
                }
                $stmt->bind_param('i', $reservationId);
            } elseif ($hasFecha && $fecha !== '') {
                if ($hasEmail && $email !== '') {
                    $stmt = $conn->prepare("DELETE FROM `{$reservationsTable}` WHERE fecha = ? AND email = ? LIMIT 1");
                    if (!$stmt) {
                        throw new RuntimeException('Error al preparar eliminacion de reserva: ' . $conn->error);
                    }
                    $stmt->bind_param('ss', $fecha, $email);
                } elseif ($hasUserId && $hasUsersEmail && $email !== '') {
                    $stmt = $conn->prepare(
                        "DELETE r FROM `{$reservationsTable}` AS r JOIN `{$usersTable}` AS u ON r.user_id = u.id WHERE r.fecha = ? AND u.email = ? LIMIT 1"
                    );
                    if (!$stmt) {
                        throw new RuntimeException('Error al preparar eliminacion de reserva: ' . $conn->error);
                    }
                    $stmt->bind_param('ss', $fecha, $email);
                } else {
                    $stmt = $conn->prepare("DELETE FROM `{$reservationsTable}` WHERE fecha = ? LIMIT 1");
                    if (!$stmt) {
                        throw new RuntimeException('Error al preparar eliminacion de reserva: ' . $conn->error);
                    }
                    $stmt->bind_param('s', $fecha);
                }
            } else {
                throw new RuntimeException('No se pudo identificar la reserva a eliminar.');
            }

            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $actionMessage = 'Reserva eliminada con exito.';
            } else {
                $actionError = 'No se encontro la reserva para eliminar.';
            }
            $stmt->close();
            break;
        default:
            throw new RuntimeException('Accion no valida.');
        }

        $conn->close();
    } catch (Throwable $e) {
        $actionError = $e->getMessage();
    }
}

function panel_admin_get_stats() {
    $stats = [
        'active_users' => 0,
        'failed_logins' => 0,
        'registrations_total' => 0,
        'registrations_today' => 0,
        'reservations_total' => 0,
        'reservations_confirmed' => 0,
        'reservations_canceled' => 0,
        'cards_total' => 0,
        'validations_total' => 0,
    ];

    $lists = [
        'latest_registrations' => [],
        'latest_logins' => [],
        'latest_reservations' => [],
        'latest_cancellations' => [],
    ];

    $warnings = [];
    $meta = [
        'can_delete_users' => false,
        'can_delete_reservations' => false,
        'users_has_id' => false,
        'users_has_email' => false,
        'reservations_has_id' => false,
        'reservations_has_email' => false,
        'reservations_has_fecha' => false,
    ];

    $conn = admin_db_connect();

    $usersTable = admin_first_table($conn, ['users']);
    $logsTable = admin_first_table($conn, ['access_logs']);
    $reservationsTable = admin_first_table($conn, ['reservations', 'reservas']);
    $cardsTable = admin_first_table($conn, ['cards']);
    $validationsTable = admin_first_table($conn, ['ValidacionTarjetas']);

    if ($logsTable !== null) {
        $activeQuery = "
            SELECT COUNT(DISTINCT email) AS total
            FROM `{$logsTable}`
            WHERE resultado='ok'
              AND evento='login'
              AND email IS NOT NULL
              AND email <> ''
              AND created_at >= (NOW() - INTERVAL 15 MINUTE)
        ";
        $activeResult = $conn->query($activeQuery);
        if ($activeResult && ($row = $activeResult->fetch_assoc())) {
            $stats['active_users'] = (int) ($row['total'] ?? 0);
        }

        $failedResult = $conn->query("
            SELECT COUNT(*) AS total
            FROM `{$logsTable}`
            WHERE resultado='error'
              AND evento='login'
              AND created_at >= (NOW() - INTERVAL 7 DAY)
        ");
        if ($failedResult && ($row = $failedResult->fetch_assoc())) {
            $stats['failed_logins'] = (int) ($row['total'] ?? 0);
        }

        $latestLoginsResult = $conn->query("
            SELECT email, username, resultado, created_at
            FROM `{$logsTable}`
            WHERE evento='login'
            ORDER BY created_at DESC
            LIMIT 5
        ");
        if ($latestLoginsResult) {
            while ($row = $latestLoginsResult->fetch_assoc()) {
                $lists['latest_logins'][] = $row;
            }
        }
    } else {
        $warnings[] = "No existe la tabla access_logs.";
    }

    if ($usersTable !== null) {
        $meta['can_delete_users'] = true;
        $meta['users_has_id'] = admin_has_column($conn, $usersTable, 'id');
        $meta['users_has_email'] = admin_has_column($conn, $usersTable, 'email');

        $usersTotalResult = $conn->query("SELECT COUNT(*) AS total FROM `{$usersTable}`");
        if ($usersTotalResult && ($row = $usersTotalResult->fetch_assoc())) {
            $stats['registrations_total'] = (int) ($row['total'] ?? 0);
        }

        $hasUsersCreatedAt = admin_has_column($conn, $usersTable, 'created_at');
        if ($hasUsersCreatedAt) {
            $usersTodayResult = $conn->query("
                SELECT COUNT(*) AS total
                FROM `{$usersTable}`
                WHERE created_at >= CURDATE()
            ");
            if ($usersTodayResult && ($row = $usersTodayResult->fetch_assoc())) {
                $stats['registrations_today'] = (int) ($row['total'] ?? 0);
            }

            $userSelectParts = [];
            if ($meta['users_has_id']) {
                $userSelectParts[] = 'id';
            }
            $userSelectParts[] = admin_has_column($conn, $usersTable, 'email') ? 'email' : "'' AS email";
            $userSelectParts[] = admin_has_column($conn, $usersTable, 'username') ? 'username' : "'' AS username";
            $userSelectParts[] = admin_has_column($conn, $usersTable, 'fullname') ? 'fullname' : "'' AS fullname";
            $userSelectParts[] = 'created_at';

            $latestUsersResult = $conn->query("
                SELECT " . implode(', ', $userSelectParts) . "
                FROM `{$usersTable}`
                ORDER BY created_at DESC
                LIMIT 5
            ");
            if ($latestUsersResult) {
                while ($row = $latestUsersResult->fetch_assoc()) {
                    $lists['latest_registrations'][] = $row;
                }
            }
        } elseif ($logsTable !== null) {
            $registrationsTodayResult = $conn->query("
                SELECT COUNT(*) AS total
                FROM `{$logsTable}`
                WHERE evento='registro'
                  AND resultado='ok'
                  AND created_at >= CURDATE()
            ");
            if ($registrationsTodayResult && ($row = $registrationsTodayResult->fetch_assoc())) {
                $stats['registrations_today'] = (int) ($row['total'] ?? 0);
            }

            $latestRegsResult = $conn->query("
                SELECT email, username, fullname, created_at
                FROM `{$logsTable}`
                WHERE evento='registro'
                ORDER BY created_at DESC
                LIMIT 5
            ");
            if ($latestRegsResult) {
                while ($row = $latestRegsResult->fetch_assoc()) {
                    $lists['latest_registrations'][] = $row;
                }
            }
        } else {
            $warnings[] = "La tabla '{$usersTable}' no tiene columna created_at y no hay access_logs para registros.";
        }
    } else {
        $warnings[] = "No existe la tabla users.";
    }

    if ($reservationsTable !== null) {
        $meta['can_delete_reservations'] = true;
        $meta['reservations_has_id'] = admin_has_column($conn, $reservationsTable, 'id');
        $meta['reservations_has_user_id'] = admin_has_column($conn, $reservationsTable, 'user_id');
        $meta['reservations_has_email'] = admin_has_column($conn, $reservationsTable, 'email');
        $meta['reservations_has_fecha'] = admin_has_column($conn, $reservationsTable, 'fecha');

        $reservationsTotalResult = $conn->query("SELECT COUNT(*) AS total FROM `{$reservationsTable}`");
        if ($reservationsTotalResult && ($row = $reservationsTotalResult->fetch_assoc())) {
            $stats['reservations_total'] = (int) ($row['total'] ?? 0);
        }

        if (admin_has_column($conn, $reservationsTable, 'estado')) {
            $confirmedResult = $conn->query("
                SELECT COUNT(*) AS total
                FROM `{$reservationsTable}`
                WHERE estado='confirmada'
            ");
            if ($confirmedResult && ($row = $confirmedResult->fetch_assoc())) {
                $stats['reservations_confirmed'] = (int) ($row['total'] ?? 0);
            }

            $cancelledResult = $conn->query("
                SELECT COUNT(*) AS total
                FROM `{$reservationsTable}`
                WHERE estado='cancelada'
            ");
            if ($cancelledResult && ($row = $cancelledResult->fetch_assoc())) {
                $stats['reservations_canceled'] = (int) ($row['total'] ?? 0);
            }
        }

        $reservationSelect = [];
        $reservationFrom = "`{$reservationsTable}`";
        $joinUsersForReservations = false;

        if ($meta['reservations_has_id']) {
            $reservationSelect[] = 'r.id';
        }
        $reservationSelect[] = 'r.nombre';

        if ($meta['reservations_has_email']) {
            $reservationSelect[] = 'r.email';
        } elseif ($meta['reservations_has_user_id'] && $usersTable !== null && admin_has_column($conn, $usersTable, 'email')) {
            $reservationSelect[] = 'u.email AS email';
            $joinUsersForReservations = true;
        } else {
            $reservationSelect[] = "'' AS email";
        }

        $reservationSelect[] = 'r.fecha';
        if (admin_has_column($conn, $reservationsTable, 'estado')) {
            $reservationSelect[] = 'r.estado';
        } else {
            $reservationSelect[] = "'' AS estado";
        }
        $reservationSelect[] = 'r.fecha_registro';

        if ($joinUsersForReservations) {
            $reservationFrom = "`{$reservationsTable}` AS r LEFT JOIN `{$usersTable}` AS u ON r.user_id = u.id";
        } else {
            $reservationFrom = "`{$reservationsTable}` AS r";
        }

        $latestReservationsResult = $conn->query("
            SELECT " . implode(', ', $reservationSelect) . "
            FROM " . $reservationFrom . "
            ORDER BY r.fecha_registro DESC
            LIMIT 5
        ");
        if ($latestReservationsResult) {
            while ($row = $latestReservationsResult->fetch_assoc()) {
                $lists['latest_reservations'][] = $row;
            }
        }

        if (admin_has_column($conn, $reservationsTable, 'estado')) {
            $cancellationsSelect = [];
            if ($meta['reservations_has_id']) {
                $cancellationsSelect[] = 'r.id';
            }
            $cancellationsSelect[] = 'r.nombre';

            if ($meta['reservations_has_email']) {
                $cancellationsSelect[] = 'r.email';
            } elseif ($meta['reservations_has_user_id'] && $usersTable !== null && admin_has_column($conn, $usersTable, 'email')) {
                $cancellationsSelect[] = 'u.email AS email';
                $joinUsersForReservations = true;
            } else {
                $cancellationsSelect[] = "'' AS email";
            }

            $cancellationsSelect[] = 'r.fecha';
            $cancellationsSelect[] = 'r.fecha_registro';

            $cancellationsFrom = $joinUsersForReservations
                ? "`{$reservationsTable}` AS r LEFT JOIN `{$usersTable}` AS u ON r.user_id = u.id"
                : "`{$reservationsTable}` AS r";

            $latestCancellationsResult = $conn->query("
                SELECT " . implode(', ', $cancellationsSelect) . "
                FROM " . $cancellationsFrom . "
                WHERE r.estado='cancelada'
                ORDER BY r.fecha_registro DESC
                LIMIT 5
            ");
            if ($latestCancellationsResult) {
                while ($row = $latestCancellationsResult->fetch_assoc()) {
                    $lists['latest_cancellations'][] = $row;
                }
            }
        }
    } else {
        $warnings[] = "No existe la tabla de reservas (reservations/reservas).";
    }

    if ($cardsTable !== null) {
        $cardsResult = $conn->query("SELECT COUNT(*) AS total FROM `{$cardsTable}`");
        if ($cardsResult && ($row = $cardsResult->fetch_assoc())) {
            $stats['cards_total'] = (int) ($row['total'] ?? 0);
        }
    } else {
        $warnings[] = "No existe la tabla cards.";
    }

    if ($validationsTable !== null) {
        $validationsResult = $conn->query("SELECT COUNT(*) AS total FROM `{$validationsTable}`");
        if ($validationsResult && ($row = $validationsResult->fetch_assoc())) {
            $stats['validations_total'] = (int) ($row['total'] ?? 0);
        }
    }

    $conn->close();
    return [
        'stats' => $stats,
        'lists' => $lists,
        'warnings' => $warnings,
        'meta' => $meta,
    ];
}

if (isset($_GET['stats']) && $_GET['stats'] === '1') {
    header('Content-Type: application/json; charset=utf-8');

    if (!$isOwner) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Solo el propietario puede ver las estadisticas.']);
        exit;
    }

    try {
        $payload = panel_admin_get_stats();
        echo json_encode(array_merge(['ok' => true], $payload));
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

$statsError = null;
$stats = [
    'active_users' => 0,
    'failed_logins' => 0,
    'registrations_total' => 0,
    'registrations_today' => 0,
    'reservations_total' => 0,
    'reservations_confirmed' => 0,
    'reservations_canceled' => 0,
    'cards_total' => 0,
    'validations_total' => 0,
];
$lists = [
    'latest_registrations' => [],
    'latest_logins' => [],
    'latest_reservations' => [],
    'latest_cancellations' => [],
];
$dbWarnings = [];
$meta = [
    'can_delete_users' => false,
    'can_delete_reservations' => false,
    'users_has_id' => false,
    'users_has_email' => false,
    'reservations_has_id' => false,
    'reservations_has_email' => false,
    'reservations_has_fecha' => false,
];

if ($isOwner) {
    try {
        $payload = panel_admin_get_stats();
        $stats = $payload['stats'] ?? $stats;
        $lists = $payload['lists'] ?? $lists;
        $dbWarnings = $payload['warnings'] ?? [];
        $meta = $payload['meta'] ?? $meta;
    } catch (Throwable $e) {
        $statsError = $e->getMessage();
    }
}
?>
<?php
$pageTitle = 'Panel de Administrador';
$pageExtraHead = <<<'HTML'
<style>
  body {
    margin: 0;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f3f4f6;
    color: #111827;
  }
  header {
    background: #0f172a;
    color: #fff;
    padding: 1.1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }
  header h1 {
    margin: 0;
    font-size: 1.2rem;
  }
  .meta {
    font-size: 0.85rem;
    color: #cbd5f5;
  }
  nav {
    width: 220px;
    background-color: #fff;
    border-right: 1px solid #e5e7eb;
    position: fixed;
    top: 64px;
    bottom: 0;
    padding: 1rem;
  }
  nav a {
    display: block;
    padding: 0.6rem 0.7rem;
    margin-bottom: 0.5rem;
    text-decoration: none;
    color: #1f2937;
    border-radius: 8px;
    transition: background 0.2s ease;
  }
  nav a:hover {
    background-color: #f1f5f9;
  }
  main {
    margin-left: 240px;
    padding: 1.6rem;
  }
  .card {
    background-color: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
  }
  .card h2 {
    margin-top: 0;
    font-size: 1rem;
  }
  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 12px;
  }
  .stat {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.8rem;
  }
  .stat span {
    display: block;
    font-size: 1.4rem;
    font-weight: 700;
    margin-top: 0.3rem;
  }
  .alert {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    color: #9a3412;
    border-radius: 10px;
    padding: 0.8rem;
    margin-bottom: 1rem;
  }
  .alert.ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #065f46;
  }
  .btn-danger {
    background: #ef4444;
    border: none;
    color: #fff;
    padding: 0.35rem 0.6rem;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
  }
  .btn-danger:hover {
    background: #dc2626;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
  }
  th, td {
    padding: 0.55rem 0.6rem;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
  }
  th {
    color: #64748b;
    font-weight: 600;
  }
  @media (max-width: 900px) {
    nav {
      position: static;
      width: auto;
      border-right: none;
      border-bottom: 1px solid #e5e7eb;
    }
    main {
      margin-left: 0;
    }
  }
</style>
HTML;
include __DIR__ . '/includes/page-start.php';
?>
<header>
  <h1>Panel de Administrador</h1>
  <div class="meta">Estado: <?php echo $isOwner ? 'Owner' : 'Acceso limitado'; ?></div>
</header>
<nav>
  <a href="./Panel-admin.php">Panel</a>
  <a href="./dashboard.php">Dashboard</a>
  <a href="./usuarios.php">Usuarios</a>
  <a href="./configuracion.php">Configuracion</a>
  <a href="./reportes.php">Reportes</a>
  <a href="./logout.php">Cerrar Sesion</a>
</nav>
<main>
  <?php if (!$isOwner): ?>
    <div class="alert">
      Solo el propietario puede ver datos en tiempo real.
    </div>
  <?php endif; ?>

  <?php if ($statsError !== null): ?>
    <div class="alert">Error de base de datos: <?php echo h($statsError); ?></div>
  <?php endif; ?>

  <?php if (count($dbWarnings) > 0): ?>
    <div class="alert"><?php echo h(implode(' ', $dbWarnings)); ?></div>
  <?php endif; ?>

  <?php if ($actionMessage !== null): ?>
    <div class="alert ok"><?php echo h($actionMessage); ?></div>
  <?php endif; ?>

  <?php if ($actionError !== null): ?>
    <div class="alert"><?php echo h($actionError); ?></div>
  <?php endif; ?>

  <section class="card">
    <h2>Resumen en tiempo real</h2>
    <div class="grid" id="stats-grid">
      <div class="stat">
        Usuarios activos (15m)
        <span id="stat-active-users"><?php echo (int) $stats['active_users']; ?></span>
      </div>
      <div class="stat">
        Registros totales
        <span id="stat-registrations-total"><?php echo (int) $stats['registrations_total']; ?></span>
      </div>
      <div class="stat">
        Registros hoy
        <span id="stat-registrations-today"><?php echo (int) $stats['registrations_today']; ?></span>
      </div>
      <div class="stat">
        Reservas totales
        <span id="stat-reservations-total"><?php echo (int) $stats['reservations_total']; ?></span>
      </div>
      <div class="stat">
        Reservas confirmadas
        <span id="stat-reservations-confirmed"><?php echo (int) $stats['reservations_confirmed']; ?></span>
      </div>
      <div class="stat">
        Cancelaciones
        <span id="stat-reservations-canceled"><?php echo (int) $stats['reservations_canceled']; ?></span>
      </div>
      <div class="stat">
        Logins fallidos (7d)
        <span id="stat-failed-logins"><?php echo (int) $stats['failed_logins']; ?></span>
      </div>
      <div class="stat">
        Tarjetas guardadas
        <span id="stat-cards-total"><?php echo (int) $stats['cards_total']; ?></span>
      </div>
      <div class="stat">
        Validaciones de tarjeta
        <span id="stat-validations-total"><?php echo (int) $stats['validations_total']; ?></span>
      </div>
    </div>
  </section>

  <section class="card">
    <h2>Ultimos registros</h2>
    <table id="table-registrations">
      <thead>
        <tr>
          <th>Email</th>
          <th>Usuario</th>
          <th>Nombre</th>
          <th>Fecha</th>
          <?php if ($isOwner && $meta['can_delete_users']): ?>
            <th>Acciones</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (count($lists['latest_registrations']) === 0): ?>
          <tr><td colspan="<?php echo $isOwner && $meta['can_delete_users'] ? 5 : 4; ?>">Sin registros recientes.</td></tr>
        <?php else: ?>
          <?php foreach ($lists['latest_registrations'] as $row): ?>
            <tr>
              <td><?php echo h($row['email'] ?? ''); ?></td>
              <td><?php echo h($row['username'] ?? ''); ?></td>
              <td><?php echo h($row['fullname'] ?? ''); ?></td>
              <td><?php echo h($row['created_at'] ?? ''); ?></td>
              <?php if ($isOwner && $meta['can_delete_users']): ?>
                <td>
                  <?php if (!empty($row['id']) || !empty($row['email'])): ?>
                    <form method="post" onsubmit="return confirm('¿Eliminar este usuario? Esta accion no se puede deshacer.');">
                      <input type="hidden" name="action" value="delete_user" />
                      <input type="hidden" name="user_id" value="<?php echo (int) ($row['id'] ?? 0); ?>" />
                      <input type="hidden" name="email" value="<?php echo h($row['email'] ?? ''); ?>" />
                      <button class="btn-danger" type="submit">Eliminar</button>
                    </form>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="card">
    <h2>Ultimos logins</h2>
    <table id="table-logins">
      <thead>
        <tr>
          <th>Email</th>
          <th>Usuario</th>
          <th>Resultado</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($lists['latest_logins']) === 0): ?>
          <tr><td colspan="4">Sin logins recientes.</td></tr>
        <?php else: ?>
          <?php foreach ($lists['latest_logins'] as $row): ?>
            <tr>
              <td><?php echo h($row['email'] ?? ''); ?></td>
              <td><?php echo h($row['username'] ?? ''); ?></td>
              <td><?php echo h($row['resultado'] ?? ''); ?></td>
              <td><?php echo h($row['created_at'] ?? ''); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="card">
    <h2>Ultimas reservas</h2>
    <table id="table-reservations">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th>Registro</th>
          <?php if ($isOwner && $meta['can_delete_reservations']): ?>
            <th>Acciones</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (count($lists['latest_reservations']) === 0): ?>
          <tr><td colspan="<?php echo $isOwner && $meta['can_delete_reservations'] ? 6 : 5; ?>">Sin reservas recientes.</td></tr>
        <?php else: ?>
          <?php foreach ($lists['latest_reservations'] as $row): ?>
            <tr>
              <td><?php echo h($row['nombre'] ?? ''); ?></td>
              <td><?php echo h($row['email'] ?? ''); ?></td>
              <td><?php echo h($row['fecha'] ?? ''); ?></td>
              <td><?php echo h($row['estado'] ?? ''); ?></td>
              <td><?php echo h($row['fecha_registro'] ?? ''); ?></td>
              <?php if ($isOwner && $meta['can_delete_reservations']): ?>
                <td>
                  <?php if (!empty($row['id']) || !empty($row['fecha'])): ?>
                    <form method="post" onsubmit="return confirm('¿Eliminar esta reserva? Esta accion no se puede deshacer.');">
                      <input type="hidden" name="action" value="delete_reservation" />
                      <input type="hidden" name="reservation_id" value="<?php echo (int) ($row['id'] ?? 0); ?>" />
                      <input type="hidden" name="fecha" value="<?php echo h($row['fecha'] ?? ''); ?>" />
                      <input type="hidden" name="email" value="<?php echo h($row['email'] ?? ''); ?>" />
                      <button class="btn-danger" type="submit">Eliminar</button>
                    </form>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="card">
    <h2>Ultimas cancelaciones</h2>
    <table id="table-cancellations">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Fecha</th>
          <th>Registro</th>
          <?php if ($isOwner && $meta['can_delete_reservations']): ?>
            <th>Acciones</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (count($lists['latest_cancellations']) === 0): ?>
          <tr><td colspan="<?php echo $isOwner && $meta['can_delete_reservations'] ? 5 : 4; ?>">Sin cancelaciones recientes.</td></tr>
        <?php else: ?>
          <?php foreach ($lists['latest_cancellations'] as $row): ?>
            <tr>
              <td><?php echo h($row['nombre'] ?? ''); ?></td>
              <td><?php echo h($row['email'] ?? ''); ?></td>
              <td><?php echo h($row['fecha'] ?? ''); ?></td>
              <td><?php echo h($row['fecha_registro'] ?? ''); ?></td>
              <?php if ($isOwner && $meta['can_delete_reservations']): ?>
                <td>
                  <?php if (!empty($row['id']) || !empty($row['fecha'])): ?>
                    <form method="post" onsubmit="return confirm('¿Eliminar esta reserva? Esta accion no se puede deshacer.');">
                      <input type="hidden" name="action" value="delete_reservation" />
                      <input type="hidden" name="reservation_id" value="<?php echo (int) ($row['id'] ?? 0); ?>" />
                      <input type="hidden" name="fecha" value="<?php echo h($row['fecha'] ?? ''); ?>" />
                      <input type="hidden" name="email" value="<?php echo h($row['email'] ?? ''); ?>" />
                      <button class="btn-danger" type="submit">Eliminar</button>
                    </form>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </section>
</main>

<?php if ($isOwner): ?>
  <script>
    (function () {
      var refreshMs = 5000;
      var canDeleteUsers = <?php echo $meta['can_delete_users'] ? 'true' : 'false'; ?>;
      var canDeleteReservations = <?php echo $meta['can_delete_reservations'] ? 'true' : 'false'; ?>;
      var endpoints = {
        activeUsers: document.getElementById('stat-active-users'),
        registrationsTotal: document.getElementById('stat-registrations-total'),
        registrationsToday: document.getElementById('stat-registrations-today'),
        reservationsTotal: document.getElementById('stat-reservations-total'),
        reservationsConfirmed: document.getElementById('stat-reservations-confirmed'),
        reservationsCanceled: document.getElementById('stat-reservations-canceled'),
        failedLogins: document.getElementById('stat-failed-logins'),
        cardsTotal: document.getElementById('stat-cards-total'),
        validationsTotal: document.getElementById('stat-validations-total'),
        registrationsTable: document.querySelector('#table-registrations tbody'),
        loginsTable: document.querySelector('#table-logins tbody'),
        reservationsTable: document.querySelector('#table-reservations tbody'),
        cancellationsTable: document.querySelector('#table-cancellations tbody')
      };

      function escapeHtml(value) {
        return String(value == null ? '' : value)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#39;');
      }

      function renderRows(rows, cols) {
        if (!rows || rows.length === 0) {
          return '<tr><td colspan="' + cols + '">Sin datos recientes.</td></tr>';
        }
        return rows.map(function (row) {
          return '<tr>' + row.map(function (cell) {
            return '<td>' + (cell || '') + '</td>';
          }).join('') + '</tr>';
        }).join('');
      }

      function renderRegistrations(rows) {
        if (!rows || rows.length === 0) {
          return '<tr><td colspan="' + (canDeleteUsers ? 5 : 4) + '">Sin registros recientes.</td></tr>';
        }

        return rows.map(function (row) {
          var email = escapeHtml(row.email || '');
          var username = escapeHtml(row.username || '');
          var fullname = escapeHtml(row.fullname || '');
          var createdAt = escapeHtml(row.created_at || '');
          var userId = Number(row.id || 0);
          var actionCell = '';

          if (canDeleteUsers) {
            if (userId > 0 || email !== '') {
              actionCell = '<td><form method="post" onsubmit="return confirm(\'¿Eliminar este usuario? Esta accion no se puede deshacer.\');">'
                + '<input type="hidden" name="action" value="delete_user" />'
                + '<input type="hidden" name="user_id" value="' + userId + '" />'
                + '<input type="hidden" name="email" value="' + email + '" />'
                + '<button class="btn-danger" type="submit">Eliminar</button>'
                + '</form></td>';
            } else {
              actionCell = '<td>-</td>';
            }
          }

          return '<tr>'
            + '<td>' + email + '</td>'
            + '<td>' + username + '</td>'
            + '<td>' + fullname + '</td>'
            + '<td>' + createdAt + '</td>'
            + (canDeleteUsers ? actionCell : '')
            + '</tr>';
        }).join('');
      }

      function renderReservations(rows, includeEstado) {
        if (!rows || rows.length === 0) {
          return '<tr><td colspan="' + (canDeleteReservations ? (includeEstado ? 6 : 5) : (includeEstado ? 5 : 4)) + '">Sin reservas recientes.</td></tr>';
        }

        return rows.map(function (row) {
          var nombre = escapeHtml(row.nombre || '');
          var email = escapeHtml(row.email || '');
          var fecha = escapeHtml(row.fecha || '');
          var estado = escapeHtml(row.estado || '');
          var registro = escapeHtml(row.fecha_registro || '');
          var reservationId = Number(row.id || 0);
          var actionCell = '';

          if (canDeleteReservations) {
            if (reservationId > 0 || fecha !== '') {
              actionCell = '<td><form method="post" onsubmit="return confirm(\'¿Eliminar esta reserva? Esta accion no se puede deshacer.\');">'
                + '<input type="hidden" name="action" value="delete_reservation" />'
                + '<input type="hidden" name="reservation_id" value="' + reservationId + '" />'
                + '<input type="hidden" name="fecha" value="' + fecha + '" />'
                + '<input type="hidden" name="email" value="' + email + '" />'
                + '<button class="btn-danger" type="submit">Eliminar</button>'
                + '</form></td>';
            } else {
              actionCell = '<td>-</td>';
            }
          }

          return '<tr>'
            + '<td>' + nombre + '</td>'
            + '<td>' + email + '</td>'
            + '<td>' + fecha + '</td>'
            + (includeEstado ? '<td>' + estado + '</td>' : '')
            + '<td>' + registro + '</td>'
            + (canDeleteReservations ? actionCell : '')
            + '</tr>';
        }).join('');
      }

      async function refreshStats() {
        try {
          var response = await fetch('Panel-admin.php?stats=1', { cache: 'no-store' });
          var data = await response.json();

          if (!response.ok || !data.ok) {
            throw new Error(data.message || 'No se pudieron actualizar las estadisticas');
          }

          var stats = data.stats || {};
          endpoints.activeUsers.textContent = Number(stats.active_users || 0);
          endpoints.registrationsTotal.textContent = Number(stats.registrations_total || 0);
          endpoints.registrationsToday.textContent = Number(stats.registrations_today || 0);
          endpoints.reservationsTotal.textContent = Number(stats.reservations_total || 0);
          endpoints.reservationsConfirmed.textContent = Number(stats.reservations_confirmed || 0);
          endpoints.reservationsCanceled.textContent = Number(stats.reservations_canceled || 0);
          endpoints.failedLogins.textContent = Number(stats.failed_logins || 0);
          endpoints.cardsTotal.textContent = Number(stats.cards_total || 0);
          endpoints.validationsTotal.textContent = Number(stats.validations_total || 0);

          var lists = data.lists || {};
          endpoints.registrationsTable.innerHTML = renderRegistrations(lists.latest_registrations || []);
          endpoints.loginsTable.innerHTML = renderRows(
            (lists.latest_logins || []).map(function (row) {
              return [escapeHtml(row.email), escapeHtml(row.username), escapeHtml(row.resultado), escapeHtml(row.created_at)];
            }),
            4
          );
          endpoints.reservationsTable.innerHTML = renderReservations(lists.latest_reservations || [], true);
          endpoints.cancellationsTable.innerHTML = renderReservations(lists.latest_cancellations || [], false);
        } catch (err) {
          console.error(err);
        }
      }

      setInterval(refreshStats, refreshMs);
    })();
  </script>
<?php endif; ?>

<?php include __DIR__ . '/includes/page-end.php'; ?>
