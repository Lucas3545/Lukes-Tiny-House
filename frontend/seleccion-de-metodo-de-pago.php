<?php
$pageTitle = 'Metodo de Pago';
$pageStyles = ['./css/seleccion-de-metodo-de-pago.css'];
$pageBodyAttrs = 'class="contenedor"';
include __DIR__ . '/includes/page-start.php';
?>

    <div class="header">
        <?php include './includes/navbar-simple.php'; ?>
    </div>

    <h3 class="titulo_principal">Nueva reserva</h3>

    <label for="slcPago">Metodo de pago:</label>
    <select id="slcPago">
        <option value="0">-- Elegi­ una opcion --</option>
        <option value="tarjeta">Tarjeta</option>
        <option value="paypal">PayPal</option>
    </select>

    <hr>

    <div id="blkTarjeta" class="panel oculto">
        <h4>Pago con tarjeta</h4>
        <ul>
            <li>Se acepta VISA/Mastercard.</li>
            <li>Comision 3.5%.</li>
            <li>Confirmacion inmediata.</li>
        </ul>
    </div>

    <div id="blkPaypal" class="panel oculto">
        <h4>Pago con PayPal</h4>
        <ul>
            <li>Comision 4%.</li>
            <li>Confirmacion inmediata.</li>
        </ul>
    </div>

    <div class="panel_interectivo_tarjeta" id="blkTarjetaPanel">
        <h4>Ingresa los datos de tu tarjeta</h4>
        <form id="formTarjeta" action="./procesar-tarjeta.php" method="POST">
            <br>
            <label for="txtNombre">Nombre en la tarjeta:</label>
            <input type="text" id="txtNombre" placeholder="Nombre completo" name="nombre_tarjeta" required>
            <br>
            <label for="txtNumero">Numero de tarjeta:</label>
            <input type="text" id="txtNumero" placeholder="XXXX-XXXX-XXXX-XXXX" name="numero" required>
            <br>
            <label for="txtVencimiento">Fecha de vencimiento:</label>
            <input type="text" id="txtVencimiento" placeholder="MM/YYYY" name="vencimiento" required>
            <br>
            <label for="txtCVV">CVV:</label>
            <input type="text" id="txtCVV" placeholder="XXX" name="cvv" required>
            <br><br>
            <button type="submit">Pagar</button>
        </form>
    </div>

    <div class="panel_interectivo_paypal" id="blkPaypalPanel">
        <h4>Instrucciones para pago con PayPal</h4>
        <p>Seras redirigido a la plataforma de PayPal para completar tu pago de manera segura.</p>
        <button onclick="window.location.href='https://www.paypal.com/us/home';">
            Ir a PayPal
        </button>

    </div>
    <script src="./js/seleccion-de-metodo-de-pago.js"></script>

    <?php include './includes/footer.php'; ?>
<?php include __DIR__ . '/includes/page-end.php'; ?>






