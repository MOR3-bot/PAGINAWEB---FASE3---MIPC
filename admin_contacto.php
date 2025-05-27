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

try {
    $pdo = new PDO("mysql:host=localhost;dbname=MiPC5", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM Contacto ORDER BY Fecha DESC");
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mensajes de Contacto</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css">
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

<div class="container mt-5">
    <h1 class="mb-4">Mensajes de Contacto</h1>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Mensaje</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mensajes as $mensaje): ?>
            <tr>
                <td><?= htmlspecialchars($mensaje['ContactoID']) ?></td>
                <td><?= htmlspecialchars($mensaje['Nombre']) ?></td>
                <td><?= htmlspecialchars($mensaje['Email']) ?></td>
                <td><?= nl2br(htmlspecialchars($mensaje['Mensaje'])) ?></td>
                <td><?= htmlspecialchars($mensaje['Fecha']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</html>
