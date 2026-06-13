<?php
require_once __DIR__ . '/includes/user-context.php';

$pageTitle = "Luke's House";
$pageStyles = [
    'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap',
    './css/estilos-tienda.css',
    './css/ai-chatbot.css',
    './css/ai-recommendations.css',
];
$pageBodyAttrs = 'class="store-page"';

include __DIR__ . '/includes/page-start.php';
?>
    <header class="store-hero">
        <?php include './includes/navbar-main.php'; ?>
        <div class="hero-content">
            <div class="hero-text">
                <p class="eyebrow">Informacion</p>
                <h1>Luke's House</h1>
                <p class="lead">
                    Luke’s House es un alojamiento minimalista y acogedor,
                     diseñado con materiales naturales y detalles sencillos que transmiten calma y comodidad.
                      El espacio combina funcionalidad con estética limpia, ideal para quienes buscan descansar
                       rodeados de un ambiente sereno. Además, se inspira en la filosofía de la “Pura Vida”,
                        ofreciendo recomendaciones prácticas para el viaje y destacando atractivos
                         turísticos cercanos como volcanes, playas y parques nacionales de Costa Rica.
                </p>
                <div class="hero-actions">
                    <img src="../frontend/img/qrcode.jpg" alt="QR Code" class="qr-code">
                </div>
            </div>
            <div class="hero-visual" role="presentation">
                <img src="../frontend/img/cabanita/cabana.webp" alt="Luke's House">
            </div>
        </div>
    </header>
    <main class="store-main">
        <section class="category-strip">
            <article>
                <h3>Hospedaje</h3>
                <p>
                    Lleva lo necesario para tu hospedaje y 
                    para tu comodidad.
                </p>
            </article>
            <article>
                <h3>Viaje</h3>
                <p>
                    Equipaje ligero y resistente.
                </p>
            </article>
            <article>
                <h3>Bosque</h3>
                <p>
                    Lleva repelente para mosquitos.
                </p>
            </article>
            <article>
                <h3>Oficina</h3>
                <p>Herramientas limpias para enfocarte.</p>
            </article>
        </section>

        <section class="products">
            <div class="section-head">
                <h2>Lugares Turisticos</h2>
                <p>Lugares turisticos de Costa Rica para visitar.</p>
            </div>
            <div class="product-grid">
                <article class="product-card">
                    <div class="product-image img-01">
                        <img class="product-image" src="../frontend/img/volcan_poas.webp" alt="Volcan Poas">
                    </div>
                    <div class="product-body">
                        <h3>Volcan Poas</h3>
                        <p>Una de las mejores cosas que hacer en Costa Rica y
                             una de las mejores excursiones desde San José, 
                             es acercarte al volcán Poás, rodeado de un bosque nuboso y
                              una de las maravillas naturales del país de la «Pura Vida».</p>
                    </div>
                </article>
                <article class="product-card">
                    <div class="product-image img-02">
                        <img class="product-image" src="../frontend/img/Manuel_antonio.webp" alt="Parque Nacional Manuel Antonio">
                    </div>
                    <div class="product-body">
                        <h3>Parque Nacional Manuel Antonio</h3>
                        <p>
                            Ubicado en la costa del Pacífico, combina playas de arena blanca 
                            con selva tropical, ideal para avistamiento de monos, perezosos y 
                            aves exóticas, además de senderismo y snorkel.
                        </p>
                    </div>
                </article>
                <article class="product-card">
                    <div class="product-image img-03">
                        <img class="product-image" src="../frontend/img/tortuguero.webp" alt="Parque Nacional Tortuguero">
                    </div>
                    <div class="product-body">
                        <h3>Parque Nacional Tortuguero</h3>
                        <p>
                            Conocido como la "pequeña Amazonia de Costa Rica", 
                            es un lugar clave para el desove de tortugas verdes y 
                            la observación de fauna en canales navegables. 
                        </p>
                    </div>
                </article>
                <article class="product-card">
                    <div class="product-image img-04">
                        <img class="product-image" src="../frontend/img/corcovado.jpg" alt="Parque Nacional Corcobado  ">
                    </div>
                    <div class="product-body">
                        <h3>parque Nacional Corcobado</h3>
                        <p>
                            Considerado uno de los lugares más biodiversos del 
                            planeta, ideal para caminatas guiadas y avistamiento 
                            de jaguares, tapires y aves exóticas.
                        </p>
                    </div>
                </article>
            </div>
            <div class="view-more">
                <button class="btn-secondary" onclick="location.href='lugares-turisticos.php'">Ver mas</button>
            </div>
        </section>
        <section class="ai-recommendations">
            <div class="section-head">
                <h2>Recomendaciones Personalizadas</h2>
                <p>Recibe recomendaciones personalizadas para tu viaje a Costa Rica.</p>
            </div>
           <a><button class="btn-primary" id="get-recommendations-btn" onclick="location.href='recomendaciones.php'">Obtener Recomendaciones</button></a>
        </section>
    </main>
    <?php include './includes/footer.php'; ?>

    <script>
        window.LH_CHATBOT_ENABLED = true;
        window.LH_AUTH = {
            loggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
            userKey: <?php echo json_encode($userKey, JSON_UNESCAPED_UNICODE); ?>
        };
    </script>
    <script src="./js/ai-chatbot.js"></script>
    <script src="./js/ai-recommendations.js"></script>
    <script src="./js/ai-config.js"></script>
    <script src="./js/index.js"></script>
<?php include __DIR__ . '/includes/page-end.php'; ?>
