<?php
session_start();

// Validar sesión
if (!isset($_SESSION['UsuarioID'])) {
    header("Location: login.php");
    exit();
}

$usuarioID = $_SESSION['UsuarioID'];

// Configuración DB
$host = 'localhost';
$db = 'MiPC5';
$user = 'root';
$pass = '1234'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ELIMINAR producto del carrito
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_producto'])) {
        $productoID = (int)$_POST['producto_id'];
        $stmt = $pdo->prepare("DELETE FROM Carrito WHERE UsuarioID = ? AND ProductoID = ?");
        $stmt->execute([$usuarioID, $productoID]);
        header("Location: carrito.php");
        exit();
    }

    // PROCESAR pago
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'pagar') {
        if (empty($_POST['DireccionID'])) {
            throw new Exception("Debes seleccionar una dirección para el envío.");
        }
        $direccionID = (int)$_POST['DireccionID'];

        // Calcular total
        $stmtTotal = $pdo->prepare("
            SELECT SUM(p.Precio * c.Cantidad) as total
            FROM Carrito c
            JOIN Productos p ON c.ProductoID = p.ProductoID
            WHERE c.UsuarioID = ?
        ");
        $stmtTotal->execute([$usuarioID]);
        $total = $stmtTotal->fetchColumn();

        if (!$total || $total <= 0) {
            throw new Exception("Tu carrito está vacío o el total es inválido.");
        }

        $estadoID = 1; // Estado 'Pendiente'

        $pdo->beginTransaction();

        // Insertar pedido
        $stmtInsertPedido = $pdo->prepare("INSERT INTO Pedidos (UsuarioID, DireccionID, FechaPedido, Total, EstadoID) VALUES (?, ?, NOW(), ?, ?)");
        $stmtInsertPedido->execute([$usuarioID, $direccionID, $total, $estadoID]);
        $pedidoID = $pdo->lastInsertId();

        // Obtener productos en carrito
        $stmtProductosCarrito = $pdo->prepare("SELECT ProductoID, Cantidad FROM Carrito WHERE UsuarioID = ?");
        $stmtProductosCarrito->execute([$usuarioID]);
        $productosCarrito = $stmtProductosCarrito->fetchAll(PDO::FETCH_ASSOC);

        $stmtInsertDetalle = $pdo->prepare("INSERT INTO DetallesPedido (PedidoID, ProductoID, Cantidad, PrecioUnitario) VALUES (?, ?, ?, ?)");

        // Dentro del foreach que inserta detalles del pedido, después de obtener el precio
        foreach ($productosCarrito as $prod) {
        $stmtPrecio = $pdo->prepare("SELECT Precio, Stock FROM Productos WHERE ProductoID = ?");
        $stmtPrecio->execute([$prod['ProductoID']]);
        $productoDatos = $stmtPrecio->fetch(PDO::FETCH_ASSOC);
        $precioUnitario = $productoDatos['Precio'];
        $stockActual = $productoDatos['Stock'];

    if ($prod['Cantidad'] > $stockActual) {
        throw new Exception("No hay suficiente stock para el producto ID " . $prod['ProductoID']);
    }

    // Insertar detalle del pedido
    $stmtInsertDetalle->execute([$pedidoID, $prod['ProductoID'], $prod['Cantidad'], $precioUnitario]);

    // Actualizar stock
    $nuevoStock = $stockActual - $prod['Cantidad'];
    $stmtUpdateStock = $pdo->prepare("UPDATE Productos SET Stock = ? WHERE ProductoID = ?");
    $stmtUpdateStock->execute([$nuevoStock, $prod['ProductoID']]);
}


        // Vaciar carrito
        $stmtVaciarCarrito = $pdo->prepare("DELETE FROM Carrito WHERE UsuarioID = ?");
        $stmtVaciarCarrito->execute([$usuarioID]);

        $pdo->commit();

        $mensajePago = "Pago realizado con éxito. Pedido #$pedidoID registrado.";
    }

    // Obtener productos del carrito para mostrar
    $stmt = $pdo->prepare("
        SELECT p.ProductoID, p.Nombre, p.Precio, c.Cantidad
        FROM Carrito c
        JOIN Productos p ON c.ProductoID = p.ProductoID
        WHERE c.UsuarioID = ?
    ");
    $stmt->execute([$usuarioID]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cargar direcciones del usuario
    $stmtDir = $pdo->prepare("SELECT DireccionID, Calle, Numero, Ciudad FROM Direcciones WHERE UsuarioID = ?");
    $stmtDir->execute([$usuarioID]);
    $direcciones = $stmtDir->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Carrito</title>
      <link rel="stylesheet" href="index.css" />
    <link rel="stylesheet" href="dashboard.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    table {
      width: 90%;
      margin: 20px auto;
      border-collapse: collapse;
    }
    th, td {
      border: 1px solid #000;
      padding: 12px;
      text-align: center;
    }
    th {
      background-color: #50a5e7;
      color: white;
    }
    .btn-eliminar {
      color: red;
      font-weight: bold;
      border: none;
      background: none;
      cursor: pointer;
    }
    #total {
      font-size: 24px;
      text-align: right;
      margin-right: 10%;
    }
    #pagar {
      display: block;
      color: white;
      margin: 20px auto;
      padding: 10px 30px;
      font-size: 16px;
      background-color: green;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg shadow bg-body-tertiary rounded">
  <div class="container-fluid">
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
         <img src="images/17654.png" class="barras rounded" alt="">
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="index.php">inicio</a></li>
            <li><a class="dropdown-item" href="mis_pedidos.php">mis pedidos</a></li>
        </ul>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNavDropdown">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link active mx-3" aria-current="page" href="Gestion de Usuario.php"> 
            <img src="images/6063673.png" class="rounded" alt=""> 
            <h6>cuenta</h6>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link active mx-3" aria-current="page" href="lista_de_productos.php"> 
            <img src="images/3144456.png" class="rounded" alt=""> 
            <h6>compra</h6>
          </a>
        </li>

        <?php if (isset($_SESSION['RolNombre']) && 
                  ($_SESSION['RolNombre'] === 'Administrador' || $_SESSION['RolNombre'] === 'Moderador')): ?>
          <li class="nav-item">
            <a class="nav-link active mx-3" aria-current="page" href="dashboard.php"> 
              <img src="images/30240.png" class="rounded" alt=""> 
              <h6>admin</h6>
            </a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<h2 class="text-center my-4">Mi Carrito</h2>

<?php if (isset($error)): ?>
  <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($mensajePago)): ?>
  <div class="alert alert-success text-center"><?= htmlspecialchars($mensajePago) ?></div>
  <p class="text-center"><a href="index.php" class="btn btn-primary">Volver al inicio</a></p>
<?php endif; ?>

<?php if (empty($productos)): ?>
  <p class="text-center">Tu carrito está vacío.</p>
<?php else: ?>
  <form method="post" action="carrito.php">
    <table>
      <thead>
        <tr>
          <th>Cantidad</th>
          <th>Nombre del Producto</th>
          <th>Precio Unitario</th>
          <th>Subtotal</th>
          <th>Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php $total = 0; ?>
        <?php foreach ($productos as $producto): 
          $subtotal = $producto['Cantidad'] * $producto['Precio'];
          $total += $subtotal;
        ?>
          <tr>
            <td><?= $producto['Cantidad'] ?></td>
            <td><?= htmlspecialchars($producto['Nombre']) ?></td>
            <td>$<?= number_format($producto['Precio'], 2) ?></td>
            <td>$<?= number_format($subtotal, 2) ?></td>
            <td>
              <form method="post" action="carrito.php" style="display:inline;">
                <input type="hidden" name="producto_id" value="<?= $producto['ProductoID'] ?>">
                <button type="submit" name="eliminar_producto" class="btn-eliminar" title="Eliminar producto">🗑️</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div id="total" class="text-end me-5">Total: $<?= number_format($total, 2) ?></div>

    <div class="mb-3 me-5 text-end">
      <label for="DireccionID" class="form-label">Selecciona Dirección de Envío:</label>
      <select name="DireccionID" id="DireccionID" class="form-select" required>
        <option value="" disabled selected>-- Selecciona una dirección --</option>

        <?php if (count($direcciones) === 0): ?>
          <option value="" disabled>No tienes direcciones registradas</option>
        <?php else: ?>
          <?php foreach ($direcciones as $dir): ?>
            <option value="<?= $dir['DireccionID'] ?>">
              <?= htmlspecialchars($dir['Calle']) ?> #<?= htmlspecialchars($dir['Numero']) ?>, <?= htmlspecialchars($dir['Ciudad']) ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>

      <?php if (count($direcciones) === 0): ?>
        <small class="text-danger">
          No tienes direcciones registradas. 
          <a href="Gestion de Usuario.php">Agrega una dirección aquí</a>.
        </small>
      <?php endif; ?>
    </div>

    <input type="hidden" name="accion" value="pagar">
    <button type="submit" id="pagar" <?= (count($direcciones) === 0) ? 'disabled' : '' ?>>Pagar</button>
  </form>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
