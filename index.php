<?php
session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['UsuarioID'])) {
    header('Location: login.php');
    exit;
}

// Conexión a la base de datos (ajusta usuario y contraseña)
$host = "localhost";
$dbname = "MiPC5";
$user = "root";      
$pass = "1234";      

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Traer todos los productos con stock > 0 y su categoría
    $sql = "SELECT p.ProductoID, p.Nombre, p.Descripcion, p.Precio, p.Stock, p.Imagen, c.Nombre AS Categoria 
            FROM Productos p
            JOIN Categorias c ON p.CategoriaID = c.CategoriaID
            WHERE p.Stock > 0
            ORDER BY p.ProductoID DESC";
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Página Principal</title>
    <link rel="stylesheet" href="index.css" />
    <link rel="stylesheet" href="dashboard.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT"
      crossorigin="anonymous"
    />
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


<div class="container min-vh-100 d-flex flex-column mt-4">
  <div class="row flex-grow-1 w-100">

    <div class="mb-4 card border border-dark text-dark rounded w-100 p-4 d-flex flex-column">
      <div id="carouselExampleSlidesOnly" class="carousel slide carousel-fade rounded-4 shadow-lg overflow-hidden" data-bs-ride="carousel" data-bs-interval="3000">
        <div class="carousel-inner">
          <div class="carousel-item active">
            <img src="images/images.jpeg" class="d-block w-100" alt="..." style="object-fit: cover; height: 400px;">
          </div>
          <div class="carousel-item">
            <img src="images/UGRPUCERCVBSJAN2HQ5ZQYI2QE.jpg" class="d-block w-100" alt="..." style="object-fit: cover; height: 400px;">
          </div>
          <div class="carousel-item">
            <img src="images/images (1).jpeg" class="d-block w-100" alt="..." style="object-fit: cover; height: 400px;">
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card border border-dark text-dark rounded w-100 p-4">
        <h2>Productos</h2>
        <p>disfruta de la gran gama de tecnologia que tenemos para ti</p>

        <!-- Scroll horizontal de productos -->
        <div class="d-flex overflow-auto gap-3 py-3">
          <div class="col-12 overflow-auto">
            <div class="d-flex gap-3 flex-wrap" id="contenedorProductos" style="min-height: 200px">
              <?php if (!empty($productos)): ?>
                  <?php foreach ($productos as $prod): ?>
                      <div class="card text-center border border-dark p-2" style="min-width: 180px; max-width: 180px;">
                          <?php if ($prod['Imagen'] && file_exists($prod['Imagen'])): ?>
                              <img src="<?= htmlspecialchars($prod['Imagen']) ?>" class="img-fluid rounded mb-2" style="height: 120px; object-fit: cover;" alt="<?= htmlspecialchars($prod['Nombre']) ?>">
                          <?php else: ?>
                              <img src="images/default-product.png" class="img-fluid rounded mb-2" style="height: 120px; object-fit: cover;" alt="Sin imagen">
                          <?php endif; ?>
                          <p class="mb-1 fw-bold"><?= htmlspecialchars($prod['Nombre']) ?></p>
                          <p class="mb-1 text-truncate" title="<?= htmlspecialchars($prod['Descripcion']) ?>"><?= htmlspecialchars(substr($prod['Descripcion'], 0, 40)) ?>...</p>
                          <p class="mb-1 text-success">$<?= number_format($prod['Precio'], 2) ?></p>
                          <small>Stock: <?= $prod['Stock'] ?></small>
                      </div>
                  <?php endforeach; ?>
              <?php else: ?>
                  <p>No hay productos disponibles.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<main class="main-container">
  <section class="novedades">
    <h3>Novedades</h3>
    <ul>
      <li>¡Nuevo sistema de recompensas disponible!</li>
      <li>Nuevo diseño de perfil ya disponible.</li>
    </ul>
  </section>
</main>

<footer>
  <p>&copy; 2025 MIPC. Todos los derechos reservados.</p>
  <a class="nav-link active mx-3" href="Quienes_somos.php">
     <h6>saber mas de nosotros</h6>
  </a>

  <a class="nav-link active mx-3" href="Contactanos.php">
    <h6>contactanos</h6>
  </a>
</footer>

<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
  crossorigin="anonymous"
></script>

</body>
</html>
