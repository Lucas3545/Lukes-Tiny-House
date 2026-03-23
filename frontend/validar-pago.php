<?php 
  include __DIR__ . '/includes/config.php';
?>

<?php
$pageTitle = 'Tarjeta de Credito';
$pageStyles = ['./css/pago.css'];
include __DIR__ . '/includes/page-start.php';
?>
  <div class="card-form">
    <h2>Datos de Tarjeta</h2>
    <form method="post" action="procesar-tarjeta.php">
      <label for="numero">NÃºmero de Tarjeta</label>
      <input type="text" id="numero" name="numero" value="<?php $numero ?>">

      <label for="nombre">Nombre del Titular</label>
      <input type="text" id="nombre" name="nombre" value="<?php $Nombre_tarjeta ?>">

      <label for="vencimiento">Fecha de Vencimiento</label>
      <input type="text" id="vencimiento" name="Vencimiento" value="<?php $Vencimiento ?>">

      <label for="cvv">CVV</label>
      <input type="text" id="cvv" name="cvv" value="<?php $cvv ?>">

      <label for="banco">Banco Emisor</label>
      <input type="text" id="banco" name="banco" value="<?php $banco ?>">

      <label for="red_de_pago">Red de Pago</label>
      <input type="text" id="red_de_pago" name="red_de_pago" value="<?php $red_de_pago ?>">

      <button type="submit" class="submit-btn">Enviar</button>
    </form>
  </div>
<?php include __DIR__ . '/includes/page-end.php'; ?>





