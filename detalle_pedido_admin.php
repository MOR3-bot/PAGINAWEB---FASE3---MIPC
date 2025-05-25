<?php
session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['UsuarioID'])) {
    header('Location: login.php');
    exit;
}

// 2. Verificar rol del usuario
$roles_permitidos = ['Administrador', 'Moderador'];

if (!isset($_SESSION['RolNombre']) || !in_array($_SESSION['RolNombre'], $roles_permitidos)) {
    // Usuario sin permiso para ver el dashboard
    // Puedes redirigir a una página de error o al index normal
    header("Location: index.php");
    exit;
}

if (!isset($_GET['PedidoID']) || !is_numeric($_GET['PedidoID'])) {
    die("Pedido inválido.");
}

$pedidoID = intval($_GET['PedidoID']);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=MiPC5;charset=utf8mb4", "root", "1234", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Obtener pedido
    $stmt = $pdo->prepare("SELECT p.*, u.NombreUsuario, e.Nombre AS EstadoNombre FROM Pedidos p
                           JOIN Usuarios u ON p.UsuarioID = u.UsuarioID
                           JOIN Estados e ON p.EstadoID = e.EstadoID
                           WHERE p.PedidoID = ?");
    $stmt->execute([$pedidoID]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        die("Pedido no encontrado.");
    }

    // Obtener detalles del pedido
    $stmt = $pdo->prepare("SELECT dp.Cantidad, dp.PrecioUnitario, pr.Nombre FROM DetallesPedido dp
                           JOIN Productos pr ON dp.ProductoID = pr.ProductoID
                           WHERE dp.PedidoID = ?");
    $stmt->execute([$pedidoID]);
    $detalles = $stmt->fetchAll();

    // Obtener estados para select
    $estados = $pdo->query("SELECT * FROM Estados")->fetchAll();

    $mensaje = "";

    // Actualizar estado si viene POST
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['EstadoID'])) {
        $nuevoEstado = (int)$_POST['EstadoID'];

        $stmtUpdate = $pdo->prepare("UPDATE Pedidos SET EstadoID = ? WHERE PedidoID = ?");
        $stmtUpdate->execute([$nuevoEstado, $pedidoID]);

        $mensaje = "Estado del pedido actualizado correctamente.";

        // Recargar pedido para mostrar el nuevo estado
        $stmt->execute([$pedidoID]);
        $pedido = $stmt->fetch();
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Detalle y Edición Pedido #<?= htmlspecialchars($pedidoID) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h2>Detalle y Edición Pedido #<?= htmlspecialchars($pedidoID) ?></h2>

  <?php if ($mensaje): ?>
    <div class="alert alert-success"><?= $mensaje ?></div>
  <?php endif; ?>

  <p><strong>Usuario:</strong> <?= htmlspecialchars($pedido['NombreUsuario']) ?></p>
  <p><strong>Fecha del pedido:</strong> <?= htmlspecialchars($pedido['FechaPedido']) ?></p>
  <p><strong>Total:</strong> $<?= number_format($pedido['Total'], 2) ?></p>
  <p><strong>Estado actual:</strong> <?= htmlspecialchars($pedido['EstadoNombre']) ?></p>

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

  <form method="POST" class="mt-3">
    <label for="EstadoID" class="form-label">Cambiar estado del pedido:</label>
    <select name="EstadoID" id="EstadoID" class="form-select w-auto d-inline-block me-2">
      <?php foreach ($estados as $estado): ?>
        <option value="<?= $estado['EstadoID'] ?>" <?= $estado['EstadoID'] == $pedido['EstadoID'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($estado['Nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Actualizar estado</button>
  </form>

  <a href="editar_pedidos.php" class="btn btn-secondary mt-3">Volver a administración de pedidos</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
