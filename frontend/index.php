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
                    Lorem ipsum dolor sit amet, consectetur adipisicing 
                    elit. Corporis similique asperiores placeat veritatis, 
                    nam modi explicabo blanditiis aperiam ab dignissimos. 
                    Dolore quod expedita sit vitae. Temporibus expedita eius fuga quod!
                </p>
                <div class="hero-actions">
                </div>
            </div>
            <div class="hero-visual" role="presentation"></div>
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

        <section class="featured">
            <div class="featured-card">
                <p class="eyebrow">Edicion limitada</p>
                <h2>Kit de descanso natural</h2>
                <p>Algodon organico, aromas suaves y acabados mate para crear un rincon tranquilo.</p>
                <button class="btn-primary" onclick="location.href='seleccion-de-metodo-de-pago.php'">Comprar ahora</button>
            </div>
            <div class="featured-image"></div>
        </section>

        <section class="products">
            <div class="section-head">
                <h2>Lugares Turisticos</h2>
                <p>Lugares turisticos de Costa Rica para visitar.</p>
            </div>
            <div class="product-grid">
                <article class="product-card">
                    <div class="product-image img-01"></div>
                    <div class="product-body">
                        <h3>Volcan Poas</h3>
                        <p>Una de las mejores cosas que hacer en Costa Rica y
                             una de las mejores excursiones desde San José, 
                             es acercarte al volcán Poás, rodeado de un bosque nuboso y
                              una de las maravillas naturales del país de la «Pura Vida».</p>
                    </div>
                </article>
                <article class="product-card">
                    <div class="product-image img-02"></div>
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
                    <div class="product-image img-03"></div>
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
                    <div class="product-image img-04"></div>
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
