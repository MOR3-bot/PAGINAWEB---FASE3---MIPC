<?php
session_start();
if (!isset($_SESSION['UsuarioID'])) {
    header("Location: login.php");
    exit;
}

$usuarioID = $_SESSION['UsuarioID'];

if (!isset($_GET['pedido_id']) || !is_numeric($_GET['pedido_id'])) {
    die("Pedido inválido.");
}

$pedidoID = intval($_GET['pedido_id']);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=MiPC5;charset=utf8mb4", "root", "1234", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Verificar que el pedido pertenezca al usuario
    $stmt = $pdo->prepare("SELECT * FROM Pedidos WHERE PedidoID = ? AND UsuarioID = ?");
    $stmt->execute([$pedidoID, $usuarioID]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        die("No tienes permiso para ver este pedido o no existe.");
    }

    // Obtener detalles del pedido
    $stmt = $pdo->prepare("
        SELECT dp.Cantidad, dp.PrecioUnitario, pr.Nombre
        FROM DetallesPedido dp
        JOIN Productos pr ON dp.ProductoID = pr.ProductoID
        WHERE dp.PedidoID = ?
    ");
    $stmt->execute([$pedidoID]);
    $detalles = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Detalle del Pedido #<?= htmlspecialchars($pedidoID) ?></title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h2>Detalle del Pedido #<?= htmlspecialchars($pedidoID) ?></h2>
  <p><strong>Fecha del pedido:</strong> <?= htmlspecialchars($pedido['FechaPedido']) ?></p>
  <p><strong>Total:</strong> $<?= number_format($pedido['Total'], 2) ?></p>

  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio Unitario</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($detalles as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['Nombre']) ?></td>
        <td><?= htmlspecialchars($item['Cantidad']) ?></td>
        <td>$<?= number_format($item['PrecioUnitario'], 2) ?></td>
        <td>$<?= number_format($item['Cantidad'] * $item['PrecioUnitario'], 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <a href="mis_pedidos.php" class="btn btn-secondary mt-3">Volver a mis pedidos</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
