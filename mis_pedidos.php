<?php
session_start();
if (!isset($_SESSION['UsuarioID'])) {
    header("Location: login.php");
    exit;
}

$usuarioID = $_SESSION['UsuarioID'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=MiPC5;charset=utf8mb4", "root", "1234", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Marcar notificación como leída si se envió el formulario
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['notificacion_id'])) {
        $id = $_POST['notificacion_id'];
        $stmt = $pdo->prepare("UPDATE Notificaciones SET Leido = 1 WHERE NotificacionID = ? AND UsuarioID = ?");
        $stmt->execute([$id, $usuarioID]);
    }

    // Consulta de pedidos
    $stmt = $pdo->prepare("
        SELECT p.PedidoID, p.FechaPedido, p.Total, e.Nombre AS Estado
        FROM Pedidos p
        JOIN Estados e ON p.EstadoID = e.EstadoID
        WHERE p.UsuarioID = ?
        ORDER BY p.FechaPedido DESC
    ");
    $stmt->execute([$usuarioID]);
    $pedidos = $stmt->fetchAll();

    // Consulta de notificaciones no leídas
    $stmt = $pdo->prepare("SELECT NotificacionID, Mensaje FROM Notificaciones WHERE UsuarioID = ? AND Leido = 0 ORDER BY Fecha DESC");
    $stmt->execute([$usuarioID]);
    $notificaciones = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis Pedidos</title>
  <link rel="stylesheet" href="index.css" />
  <link rel="stylesheet" href="dashboard.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg shadow bg-body-tertiary rounded">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div class="dropdown">
      <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <img src="images/17654.png" class="barras rounded" alt="Menú" style="width: 30px; height: 30px;">
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="index.php">inicio</a></li>
        <li><a class="dropdown-item" href="mis_pedidos.php">mis pedidos</a></li>
      </ul>
    </div>
      <div class="mx-auto text-center">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
          <img src="images/mipc.png" alt="MiPC" style="height: 50px; margin-right: 10px;">
          <h4 class="mb-0 fw-bold" style="color: #000;">MIPC</h4>
        </a>
      </div>
  <div class="d-flex align-items-center">
      <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
        aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNavDropdown">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link active mx-2" href="Gestion de Usuario.php">
              <img src="images/6063673.png" class="rounded" alt="" >
              <h6>cuenta</h6>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link active mx-2" href="lista_de_productos.php">
              <img src="images/3144456.png" class="rounded" alt="">
              <h6>compra</h6>
            </a>
          </li>
          <?php if (isset($_SESSION['RolNombre']) && 
                    ($_SESSION['RolNombre'] === 'Administrador' || $_SESSION['RolNombre'] === 'Moderador')): ?>
            <li class="nav-item">
              <a class="nav-link active mx-2" href="dashboard.php">
                <img src="images/30240.png" class="rounded" alt="" >
                <h6>admin</h6>
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
 </div>
</nav>

<div class="container mt-4">
  <h2>Mis Pedidos</h2>

  <?php foreach ($pedidos as $pedido): ?>
    <?php if ($pedido['Estado'] === 'Finalizado'): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✅ Tu pedido con ID <strong><?= $pedido['PedidoID'] ?></strong> ha sido finalizado. ¡Gracias por tu compra!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php foreach ($notificaciones as $notif): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?= $notif['Mensaje'] ?>
      <form method="post" style="display:inline;">
        <input type="hidden" name="notificacion_id" value="<?= $notif['NotificacionID'] ?>">
        <button type="submit" class="btn-close" aria-label="Cerrar"></button>
      </form>
    </div>
  <?php endforeach; ?>

  <table class="table table-bordered mt-3">
    <thead class="table-dark">
  <tr>
    <th># Pedido</th>
    <th>Fecha</th>
    <th>Total</th>
    <th>Estado</th>
    <th>Detalles</th> 
  </tr>
</thead>
<tbody>
  <?php if (count($pedidos) > 0): ?>
    <?php foreach ($pedidos as $pedido): ?>
      <tr>
        <td><?= htmlspecialchars($pedido['PedidoID']) ?></td>
        <td><?= htmlspecialchars($pedido['FechaPedido']) ?></td>
        <td>$<?= number_format($pedido['Total'], 2) ?></td>
        <td><?= htmlspecialchars($pedido['Estado']) ?></td>
        <td>
          <a href="detalle_pedido.php?pedido_id=<?= urlencode($pedido['PedidoID']) ?>" class="btn btn-primary btn-sm">
            Ver detalles
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr><td colspan="5" class="text-center">No tienes pedidos realizados.</td></tr>
  <?php endif; ?>
</tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
