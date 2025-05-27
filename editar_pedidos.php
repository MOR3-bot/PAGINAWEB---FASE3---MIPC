<?php
session_start();

if (!isset($_SESSION['UsuarioID'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=MiPC5;charset=utf8mb4", "root", "1234", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $mensaje = "";

   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['PedidoID'], $_POST['EstadoID'])) {
    $pedidoID = (int)$_POST['PedidoID'];
    $nuevoEstado = (int)$_POST['EstadoID'];

    $pdo->beginTransaction();

    // Obtener estado actual
    $stmtEstadoActual = $pdo->prepare("SELECT EstadoID FROM Pedidos WHERE PedidoID = ?");
    $stmtEstadoActual->execute([$pedidoID]);
    $estadoActual = $stmtEstadoActual->fetchColumn();

    if ($estadoActual === false) {
        throw new Exception("Pedido no encontrado.");
    }

    // Actualizar estado del pedido
    $stmt = $pdo->prepare("UPDATE Pedidos SET EstadoID = ? WHERE PedidoID = ?");
    $stmt->execute([$nuevoEstado, $pedidoID]);

    // Si nuevoEstado es 4 (cancelado) y antes no estaba cancelado
    if ($nuevoEstado === 4 && $estadoActual != 4) {
        // Actualizar stock como haces...
    }

    // Si nuevoEstado es 5 y antes no era 5, también actualizar stock
    if ($nuevoEstado === 5 && $estadoActual != 5) {
        $stmtDetalles = $pdo->prepare("SELECT ProductoID, Cantidad FROM DetallesPedido WHERE PedidoID = ?");
        $stmtDetalles->execute([$pedidoID]);
        $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

        if (!$detalles) {
            throw new Exception("No hay detalles para pedido $pedidoID");
        }

        $stmtUpdateStock = $pdo->prepare("UPDATE Productos SET Stock = Stock + ? WHERE ProductoID = ?");
        $stmtCheckStock = $pdo->prepare("SELECT Stock FROM Productos WHERE ProductoID = ?");

        foreach ($detalles as $detalle) {
            $cantidad = $detalle['Cantidad'];
            $productoID = $detalle['ProductoID'];

            if (!$stmtUpdateStock->execute([$cantidad, $productoID])) {
                throw new Exception("Error actualizando stock para producto $productoID");
            }
            if ($stmtUpdateStock->rowCount() === 0) {
                throw new Exception("No se actualizó ningún registro para producto $productoID");
            }

            $stmtCheckStock->execute([$productoID]);
            $stockActual = $stmtCheckStock->fetchColumn();
            error_log("Producto $productoID stock actualizado a: $stockActual");
        }
    }

    // Notificación si está finalizado (3)
    if ($nuevoEstado === 3) {
    }

    $pdo->commit();

    $mensaje = "✅ Estado del pedido #$pedidoID actualizado correctamente.";
}

$estadoFiltro = isset($_GET['estado_filtro']) ? (int)$_GET['estado_filtro'] : 0;

    // Obtener pedidos para mostrar
$sqlPedidos = "
    SELECT p.PedidoID, u.NombreUsuario, p.FechaPedido, p.Total, e.Nombre AS Estado, p.EstadoID
    FROM Pedidos p
    JOIN Usuarios u ON p.UsuarioID = u.UsuarioID
    JOIN Estados e ON p.EstadoID = e.EstadoID
";

if ($estadoFiltro > 0) {
    $sqlPedidos .= " WHERE p.EstadoID = :estadoFiltro";
}

$sqlPedidos .= " ORDER BY p.FechaPedido DESC";

$stmtPedidos = $pdo->prepare($sqlPedidos);

if ($estadoFiltro > 0) {
    $stmtPedidos->execute(['estadoFiltro' => $estadoFiltro]);
} else {
    $stmtPedidos->execute();
}

$pedidos = $stmtPedidos->fetchAll();


    // Obtener lista de estados
    $estados = $pdo->query("SELECT * FROM Estados")->fetchAll();

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $mensaje = "Error: " . $e->getMessage();
}



?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrar Pedidos</title>
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
  <h2>Gestión de Pedidos</h2>

  <?php if ($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= $mensaje ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  <?php endif; ?>

  <form method="GET" class="row g-3 mb-4">
  <div class="col-auto">
    <label for="estado_filtro" class="form-label">Filtrar por estado:</label>
  </div>
  <div class="col-auto">
    <select name="estado_filtro" id="estado_filtro" class="form-select" onchange="this.form.submit()">
      <option value="0">Todos</option>
      <?php foreach ($estados as $estado): ?>
        <option value="<?= $estado['EstadoID'] ?>" <?= $estadoFiltro == $estado['EstadoID'] ? 'selected' : '' ?>>
          <?= $estado['Nombre'] ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<table class="table table-bordered">
  <thead class="table-dark">
    <tr>
      <th>#</th>
      <th>Usuario</th>
      <th>Fecha</th>
      <th>Total</th>
      <th>Estado</th>
      <th>Cambiar Estado</th>
      <th>Ver detalles</th> 
    </tr>
  </thead>
  <tbody>
    <?php foreach ($pedidos as $pedido): ?>
    <tr>
      <td><?= $pedido['PedidoID'] ?></td>
      <td><?= htmlspecialchars($pedido['NombreUsuario']) ?></td>
      <td><?= $pedido['FechaPedido'] ?></td>
      <td>$<?= number_format($pedido['Total'], 2) ?></td>
      <td><?= $pedido['Estado'] ?></td>
      <td>
        <form method="POST" class="d-flex">
          <input type="hidden" name="PedidoID" value="<?= $pedido['PedidoID'] ?>">
          <select name="EstadoID" class="form-select me-2">
            <?php foreach ($estados as $estado): ?>
              <option value="<?= $estado['EstadoID'] ?>" <?= $estado['EstadoID'] == $pedido['EstadoID'] ? 'selected' : '' ?>>
                <?= $estado['Nombre'] ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primary btn-sm">Actualizar</button>
        </form>
      </td>
      <td>
        <a href="detalle_pedido_admin.php?PedidoID=<?= $pedido['PedidoID'] ?>" class="btn btn-info btn-sm">Ver detalles</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
