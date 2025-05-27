<?php
session_start();
// Conexión a la base de datos
$host = 'localhost';
$db = 'MiPC5';
$user = 'root';
$pass = '1234';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$error = null;

// Agregar categoría sin duplicados
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nueva_categoria"])) {
    $nombre = trim($_POST["nueva_categoria"]);
    $descripcion = $_POST["descripcion_categoria"] ?? '';

    // Verificar si ya existe una categoría con ese nombre (ignorando mayúsculas/minúsculas)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Categorias WHERE LOWER(Nombre) = LOWER(?)");
    $stmt->execute([$nombre]);
    $existe = $stmt->fetchColumn();

    if ($existe) {
        $error = "La categoría ya existe y no se puede duplicar.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO Categorias (Nombre, Descripcion) VALUES (?, ?)");
        $stmt->execute([$nombre, $descripcion]);
        header("Location: categorias.php");
        exit;
    }
}

// Agregar subcategoría sin duplicados en la misma categoría
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nueva_subcategoria"])) {
    $nombre = trim($_POST["nueva_subcategoria"]);
    $descripcion = $_POST["descripcion_subcategoria"] ?? '';
    $categoriaID = $_POST["categoria_id"];

    // Verificar si ya existe una subcategoría con ese nombre dentro de la misma categoría
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Subcategorias WHERE CategoriaID = ? AND LOWER(Nombre) = LOWER(?)");
    $stmt->execute([$categoriaID, $nombre]);
    $existe = $stmt->fetchColumn();

    if ($existe) {
        $error = "La subcategoría ya existe en esta categoría y no se puede duplicar.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO Subcategorias (CategoriaID, Nombre, Descripcion) VALUES (?, ?, ?)");
        $stmt->execute([$categoriaID, $nombre, $descripcion]);
        header("Location: categorias.php");
        exit;
    }
}

// Eliminar categoría
if (isset($_GET["eliminar_categoria"])) {
    $id = $_GET["eliminar_categoria"];
    $stmt = $pdo->prepare("DELETE FROM Categorias WHERE CategoriaID = ?");
    $stmt->execute([$id]);
    header("Location: categorias.php");
    exit;
}

// Eliminar subcategoría
if (isset($_GET["eliminar_subcategoria"])) {
    $id = $_GET["eliminar_subcategoria"];
    $stmt = $pdo->prepare("DELETE FROM Subcategorias WHERE SubcategoriaID = ?");
    $stmt->execute([$id]);
    header("Location: categorias.php");
    exit;
}

// Obtener categorías y subcategorías
$categorias = $pdo->query("SELECT * FROM Categorias")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Categorías</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="dashboard.css">
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

<div class="container my-5">

  <?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="row">
    
    <div class="col-lg-6 mb-4">
      <div class="card border border-dark text-dark rounded p-4">
        <h2 class="text-center mb-4">Lista de categorías</h2>
        <ul class="list-group">
          <?php foreach ($categorias as $categoria): ?>
            <li class="list-group-item">
              <strong><?= htmlspecialchars($categoria['Nombre']) ?></strong>
              <a href="?eliminar_categoria=<?= $categoria['CategoriaID'] ?>" class="btn btn-sm btn-danger float-end">Eliminar</a>
              <br><small><?= htmlspecialchars($categoria['Descripcion']) ?></small>

              <?php
                $stmt = $pdo->prepare("SELECT * FROM Subcategorias WHERE CategoriaID = ?");
                $stmt->execute([$categoria['CategoriaID']]);
                $subcategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
              ?>
              <?php if ($subcategorias): ?>
                <ul class="mt-2">
                  <?php foreach ($subcategorias as $sub): ?>
                    <li>
                      - <?= htmlspecialchars($sub['Nombre']) ?>
                      <a href="?eliminar_subcategoria=<?= $sub['SubcategoriaID'] ?>" class="btn btn-sm btn-outline-danger">x</a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>

              <!-- Formulario agregar subcategoría -->
              <form method="post" class="mt-2">
                <input type="hidden" name="categoria_id" value="<?= $categoria['CategoriaID'] ?>">
                <input type="text" class="form-control mb-1" name="nueva_subcategoria" placeholder="Nueva subcategoría" required>
                <input type="text" class="form-control mb-1" name="descripcion_subcategoria" placeholder="Descripción (opcional)">
                <button class="btn btn-sm btn-success w-100" type="submit">Agregar subcategoría</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    
    <div class="col-lg-6">
      <div class="card border border-dark text-dark rounded p-4">
        <h2 class="text-center mb-4">Agregar nueva categoría</h2>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Nombre de la categoría</label>
            <input type="text" class="form-control" name="nueva_categoria" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion_categoria"></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100">Agregar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
