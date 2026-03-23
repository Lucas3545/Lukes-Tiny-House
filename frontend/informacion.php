<?php
$pageLang = 'en';
$pageTitle = 'Información';
$pageStyles = ['./css/estilos-info.css'];
include __DIR__ . '/includes/page-start.php';
?>
    <header>
        <h1 class="titulo-principal"><strong>Bienvenido a la zona en la que se encuentra la información general del
                lugar</strong></h1>
    </header>
    <main>
        <div class="header">
            <?php include './includes/navbar-simple.php'; ?>
        </div>
        <div class="card" id="montanas">
            <div class="card-image"
                style="background-image: url('https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=800&q=80');">
            </div>
            <div class="card-content">
                <h2 class="card-title">Información del Airbnb y también general</h2>
                <p class="card-description">Disfruta una estancia única; patio de 1 acre visitado por perezosos,
                    tucanes, ranas, colibríes, polinizadores, etc. En las tardes y mañanas talvez escuches monos
                    aulladores.
                    Si caminas un poco, es posible que veas perezosos y algunos animales silvestres, ¡nunca alimentes a
                    ninguno!
                    La casa es ideal para compartir, pasear, hacer ejercicio, leer, pintar, acampar en verano ya que
                    cuenta con amplio terreno y darte unos días de descanso en este espacio rural.
                    Necesitas 30 minutos para ir a Fortuna.
                    El espacio
                    Búscate un entorno tranquilo donde puedas conectarte con la naturaleza; ver cultivos y obtener la
                    tranquilidad que solo ofrece el campo. El clima húmedo, cálido y tropical nos regala lluvia a
                    cualquier hora. Si va a caminar se recomienda traer repelente de insectos, zapatos para caminar y
                    poncho para la lluvia, bloqueador solar y un sombrero. La habitación tiene un aire acondicionado
                    portátil, para que puedan descansar durante las noches húmedas.

                    "Dentro de 20 años estarás más decepcionado por las cosas que no hiciste que por las que hiciste.
                    Así que suelta amarras, navega lejos de puertos seguros, coge los vientos alisios. Explora". Sueña.
                    Descubre
                    -Mark Twain<br>
                    <strong>Acceso de los huéspedes</strong><br>
                    Puede caminar libremente por toda la propiedad, las personas que viven cerca de la casa son mi
                    familia, quienes estarán felices de recibirlos como vecinos si alguna vez se ven.
                    Otros aspectos a destacar
                    Se trata de una casa de madera, montañas cercanas lo que nos permite tener de cerca maravillosos
                    animales: tejones, perezosos, monos, insectos y pájaros de colores, reptiles, etc.

                    IMPORTANTE:<br>
                    - 700 meters from paved road, the main highway (702 route)<br>
                    - 850 meters to super market<br>
                    - 1.3 km to La Lucha gas station<br>
                    - 20.5 km to La Fortuna downtown<br>
                    - 25.1 km to Ciudad Quesada bussiness city (in the other direction than Fortuna)<br>
                </p>
                <button>
                    <a
                        href="https://www.getyourguide.es/san-carlos-l157979/tours-privados-tc221/?cmp=bing&ad_id=77653208003841&adgroup_id=1242448762072016&bid_match_type=bb&campaign_id=434111614&device=c&feed_item_id=&keyword=www.getyourguide.es&loc_interest_ms=142152&loc_physical_ms=142152&match_type=b&msclkid=193d865144bd1e8eb955ee6a1823dccf&network=o&partner_id=CD951&target_id=dat-2329452822651422:aud-806259112:loc-48&utm_adgroup=ct%3Ddsa%7Cfn%3Df1&utm_campaign=ct%3Ddsa%7Cln%3D109%3Aes%7Ctc%3Dall&utm_keyword=www.getyourguide.es&utm_medium=paid_search&utm_query=tours%20de%20la%20zona%20de%20san%20carlos&utm_source=bing">more
                        information</a>
                </button>
            </div>

            <section class="review-section">
                <h2>Deja tu reseña</h2>
                <form id="reviewForm" autocomplete="off">
                    <textarea id="reviewInput" placeholder="Escribe tu reseña aquí..." required></textarea>
                    <button type="submit">Enviar</button>
                </form>
            </section>


            <section class="reviews-list-section">
                <h2>Reseñas de clientes</h2>
                <ul id="reviewsList"></ul>
            </section>
            <hr>
            <section class="infor">
                    <h1>Información de la Casa</h1>
                    <p>Esta casa es un lugar ideal para disfrutar de la naturaleza y desconectar del estrés diario. Está
                        ubicada en una zona tranquila, rodeada de montañas y vegetación exuberante. Aquí podrás
                        relajarte, explorar la fauna local y disfrutar de un ambiente acogedor.</p>
                    <h2>Características Principales</h2>
                    <ol class="features">
                        <li>Habitaciones cómodas y bien equipadas.</li>
                        <li>espacio exterior para actividades al aire libre.</li>
                        <li>Cerca de rutas de senderismo y atracciones naturales.</li>
                        <li>Acceso a servicios básicos como supermercados y gasolineras.</li>
                        <li>Ideal para parejas.</li>
                        <li>Conexión a internet disponible.</li>
                        <li>Política de no fumar y no mascotas</li>
                        <li>Check-in a partir de las 3:00 PM y check-out antes de las 11:00 AM.</li>
                        <li>Estacionamiento gratuito en el lugar</li>
                    </ol>
                    <h2>Recomendaciones</h2>
                    <p>Para disfrutar al máximo de tu estancia, te recomendamos:</p>
                    <ol>
                        <li>Traer ropa cómoda y adecuada para el clima</li>
                        <li>Utilizar repelente de insectos si planeas explorar la naturaleza</li>
                        <li>Respetar las normas de convivencia y cuidar el entorno natural</li>
                        <li>Disfrutar de las vistas y la tranquilidad del lugar</li>
                        <li>Probar la gastronomía local en los restaurantes cercanos</li>
                        <li>Informarte sobre las actividades disponibles en la zona, como paseos a caballo, visitas a
                            cascadas y tours guiados</li>
                    </ol>
            </section>
            <fieldset class="ubicacion">
                <legend id="ex">Ubicación</legend>
                <p>La casa está ubicada a 700 metros de la carretera pavimentada, a 850 metros de un supermercado y a
                    1.3 km de una estación de servicio. La Fortuna está a 20.5 km y Ciudad Quesada a 25.1 km.</p>
                <p>Para llegar, sigue las indicaciones desde la carretera principal y disfruta del paisaje durante el
                    trayecto. La zona es segura y tranquila, ideal para desconectar y disfrutar de la naturaleza.</p>
                <p>Si necesitas ayuda para encontrar la ubicación exacta, no dudes en contactarnos.</p>
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3648.8137390698903!2d-84.5893926253092!3d10.359911889764646!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8fa06d22cf41ff97%3A0x795a799aa3c52c84!2sLuke%C2%B4s%20Tiny%20House!5e1!3m2!1ses-419!2scr!4v1752942575385!5m2!1ses-419!2scr"
                    width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </fieldset>
    </main>
    <?php include './includes/footer.php'; ?>
    <script src="./js/informacion.js"></script>
<?php include __DIR__ . '/includes/page-end.php'; ?>







