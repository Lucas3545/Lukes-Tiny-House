<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/admin-common.php';

$showAdminDbPanel = admin_is_owner();

$dbError = null;
$dbWarnings = [];
$dbStats = [
    'users' => 0,
    'reservations' => 0,
    'cards' => 0,
];
$nextReservations = [];

if ($showAdminDbPanel) {
    try {
        $conn = admin_db_connect();

        $usersTable = admin_first_table($conn, ['users']);
        $reservationsTable = admin_first_table($conn, ['reservations', 'reservas']);
        $cardsTable = admin_first_table($conn, ['cards', 'ValidacionTarjetas', 'tarjetas']);

        if ($usersTable !== null) {
            $usersResult = $conn->query("SELECT COUNT(*) AS total FROM `{$usersTable}`");
            if ($usersResult && $row = $usersResult->fetch_assoc()) {
                $dbStats['users'] = (int) $row['total'];
            }
        } else {
            $dbWarnings[] = "La tabla 'users' no existe.";
        }

        if ($reservationsTable !== null) {
            $reservationsCountQuery = admin_has_column($conn, $reservationsTable, 'estado')
                ? "SELECT COUNT(*) AS total FROM `{$reservationsTable}` WHERE estado = 'confirmada'"
                : "SELECT COUNT(*) AS total FROM `{$reservationsTable}`";
            $reservationsResult = $conn->query($reservationsCountQuery);
            if ($reservationsResult && $row = $reservationsResult->fetch_assoc()) {
                $dbStats['reservations'] = (int) $row['total'];
            }
        } else {
            $dbWarnings[] = "No existe una tabla de reservas (reservations/reservas).";
        }

        if ($cardsTable !== null) {
            $cardsResult = $conn->query("SELECT COUNT(*) AS total FROM `{$cardsTable}`");
            if ($cardsResult && $row = $cardsResult->fetch_assoc()) {
                $dbStats['cards'] = (int) $row['total'];
            }
        } else {
            $dbWarnings[] = "No existe una tabla de tarjetas (cards/ValidacionTarjetas/tarjetas).";
        }

        if ($reservationsTable !== null) {
            $hasNombre = admin_has_column($conn, $reservationsTable, 'nombre');
            $hasFecha = admin_has_column($conn, $reservationsTable, 'fecha');
            $hasUserId = admin_has_column($conn, $reservationsTable, 'user_id');
            $hasEstado = admin_has_column($conn, $reservationsTable, 'estado');

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
                if (admin_has_column($conn, $usersTable, 'fullname')) {
                    $userNameParts[] = 'u.fullname';
                }
                if (admin_has_column($conn, $usersTable, 'username')) {
                    $userNameParts[] = 'u.username';
                }
                if (admin_has_column($conn, $usersTable, 'email')) {
                    $userNameParts[] = 'u.email';
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
                $dbWarnings[] = "La tabla '{$reservationsTable}' no tiene columnas compatibles (nombre/fecha o user_id/fecha).";
            }

            if (!empty($nextReservationsQuery)) {
                $nextReservationsResult = $conn->query($nextReservationsQuery);
                if ($nextReservationsResult) {
                    while ($reservation = $nextReservationsResult->fetch_assoc()) {
                        $nextReservations[] = $reservation;
                    }
                }
            }
        }

        $conn->close();
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

?>
<?php
$pageTitle = "Recomendaciones Personalizadas - Luke''s House";
$pageStyles = [
    'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    './css/estilos-index.css',
    './css/ai-chatbot.css',
    './css/ai-recommendations.css'
];
$pageExtraHead = <<<'HTML'
<style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
        }

        .hero-section h1 {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .hero-section p {
            font-size: 20px;
            opacity: 0.9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin-bottom: 32px;
        }

        .back-button:hover {
            background: #667eea;
            color: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin: 40px 0;
        }

        .feature-card {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .feature-card h3 {
            color: #2d3748;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: #718096;
            line-height: 1.6;
        }

        .db-panel {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 28px;
        }

        .db-panel h2 {
            color: #2d3748;
            margin-top: 0;
            margin-bottom: 16px;
        }

        .db-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .db-stat {
            background: white;
            border-radius: 10px;
            padding: 16px;
            border: 1px solid #edf2f7;
        }

        .db-stat strong {
            color: #4a5568;
            display: block;
            margin-bottom: 6px;
        }

        .db-stat span {
            font-size: 26px;
            font-weight: 700;
            color: #667eea;
        }

        .reservation-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }

        .reservation-list li {
            background: white;
            border: 1px solid #edf2f7;
            border-radius: 8px;
            padding: 12px;
            color: #4a5568;
        }

        .db-error {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 10px;
            color: #c53030;
            padding: 12px 14px;
            margin-bottom: 20px;
        }

        .db-context-inline {
            background: #edf2f7;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #2d3748;
            font-size: 14px;
            margin: 0 0 16px 0;
            padding: 10px 12px;
        }

        .db-context-inline p {
            margin: 4px 0;
        }

        .db-context-error {
            color: #c53030;
            margin: 0;
        }
    </style>
HTML;
include __DIR__ . '/includes/page-start.php';
?>
    <header>
        <nav class="navbar">
            <div class="logo">Luke's House</div>
            <ul class="nav-links">
                <li><a class="navbar-link" href="index.php">Inicio</a></li>
                <li><a class="navbar-link" href="informacion.php">Informacion</a></li>
                <li><a class="navbar-link" href="galeria.php">Galeria</a></li>
                <li><a class="navbar-link" href="calendario.php">Reservar</a></li>
                <li><a class="navbar-link" href="recomendaciones.php" id="chatbot-shortcut" title="Chat de Ayuda"><i class="fas fa-comments"></i></a></li>
            </ul>
        </nav>
    </header>

    <div class="hero-section">
        <h1>Encuentra tu Aventura Perfecta</h1>
        <p>Recomendaciones personalizadas con Inteligencia Artificial</p>
    </div>

    <div class="container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Volver al Inicio
        </a>
        <?php if ($showAdminDbPanel): ?>
        <section class="db-panel">
            <h2>Datos en tiempo real desde MySQL</h2>
            <div id="db-status" class="db-context-inline">
                <?php if ($dbError !== null): ?>
                <p class="db-context-error">Error de conexion a BD: <?php echo h($dbError); ?></p>
                <?php elseif (count($dbWarnings) > 0): ?>
                <p class="db-context-error"><?php echo h(implode(' ', $dbWarnings)); ?></p>
                <?php else: ?>
                <p>Conectado a MySQL en tiempo real.</p>
                <?php endif; ?>
            </div>
            <div class="db-stats">
                <article class="db-stat">
                    <strong>Usuarios registrados</strong>
                    <span id="db-users"><?php echo (int) $dbStats['users']; ?></span>
                </article>
                <article class="db-stat">
                    <strong>Reservas totales</strong>
                    <span id="db-reservations"><?php echo (int) $dbStats['reservations']; ?></span>
                </article>
                <article class="db-stat">
                    <strong>Tarjetas guardadas</strong>
                    <span id="db-cards"><?php echo (int) $dbStats['cards']; ?></span>
                </article>
            </div>

            <h3>Proximas reservas</h3>
            <ul id="db-next-reservations" class="reservation-list">
                <?php if (count($nextReservations) === 0): ?>
                <li>No hay reservas futuras registradas.</li>
                <?php else: ?>
                <?php foreach ($nextReservations as $reservation): ?>
                <li>
                    <strong><?php echo h($reservation['nombre'] ?? ''); ?></strong>
                    - <?php echo h($reservation['fecha'] ?? ''); ?>
                    (<?php echo h($reservation['estado'] ?? 'confirmada'); ?>)
                </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>
        <?php endif; ?>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>IA Personalizada</h3>
                <p>Nuestro sistema de IA analiza tus preferencias para sugerirte las mejores actividades</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>Actividades Variadas</h3>
                <p>Desde aventuras extremas hasta experiencias relajantes en la naturaleza</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>Itinerarios Completos</h3>
                <p>Planifica tu viaje completo con recomendaciones dia por dia</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>Consejos Locales</h3>
                <p>Informacion actualizada sobre las mejores atracciones de La Fortuna</p>
            </div>
        </div>

        <div id="recommendations-widget"></div>
    </div>

    <?php include './includes/footer.php'; ?>

    <script>
        window.RECOMMENDATIONS_API_ENDPOINT = './recomendaciones-data.php';
    </script>
    <script src="./js/ai-chatbot.js"></script>
    <script src="./js/ai-recommendations.js"></script>
    <script src="./js/ai-config.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.aiRecommendations) {
                window.aiRecommendations.createRecommendationWidget('recommendations-widget');
            } else {
                console.error('Sistema de recomendaciones no disponible');
            }

            const chatbotShortcut = document.getElementById('chatbot-shortcut');
            if (chatbotShortcut) {
                chatbotShortcut.addEventListener('click', function(e) {
                    e.preventDefault();
                    const chatbotToggle = document.getElementById('chatbot-toggle');
                    if (chatbotToggle) {
                        chatbotToggle.click();
                    }
                });
            }
        });
    </script>
<?php include __DIR__ . '/includes/page-end.php'; ?>




