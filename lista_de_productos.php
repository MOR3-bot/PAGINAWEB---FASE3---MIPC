<?php
session_start();

if (!isset($_SESSION['UsuarioID'])) {
    $_SESSION['UsuarioID'] = 1;
}

// Configuración de conexión
$host = 'localhost';
$db = 'MiPC5';
$user = 'root';
$pass = '1234';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$terminoBusqueda = $_GET['buscar'] ?? '';
$categoriaFiltro = $_GET['categoria'] ?? '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmtCategorias = $pdo->query("SELECT CategoriaID, Nombre, Descripcion FROM Categorias ORDER BY Nombre");
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

    $productosPorCategoria = [];

    $ordenFiltro = $_GET['orden'] ?? '';

    // Construir consulta dinámica
    $query = "
        SELECT ProductoID, Nombre, Descripcion, Precio, Stock, Imagen, CategoriaID 
        FROM Productos 
        WHERE 1=1
    ";
    $params = [];

    if ($terminoBusqueda !== '') {
        $query .= " AND Nombre LIKE ? ";
        $params[] = "%$terminoBusqueda%";
    }
    if ($categoriaFiltro !== '' && $categoriaFiltro !== 'todas') {
        $query .= " AND CategoriaID = ? ";
        $params[] = $categoriaFiltro;
    }

    if ($ordenFiltro === 'mas_vendidos') {
    $query = "
        SELECT p.ProductoID, p.Nombre, p.Descripcion, p.Precio, p.Stock, p.Imagen, p.CategoriaID,
               COALESCE(SUM(dp.Cantidad), 0) AS TotalVendidos
        FROM Productos p
        LEFT JOIN DetallesPedido dp ON p.ProductoID = dp.ProductoID
        WHERE 1=1
    ";

    if ($terminoBusqueda !== '') {
        $query .= " AND p.Nombre LIKE ? ";
    }
    if ($categoriaFiltro !== '' && $categoriaFiltro !== 'todas') {
        $query .= " AND p.CategoriaID = ? ";
    }

    $query .= " GROUP BY p.ProductoID ORDER BY TotalVendidos DESC";
} else {
    $query .= " ORDER BY Nombre";
}


$stmt = $pdo->prepare($query);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($productos as $p) {
        $productosPorCategoria[$p['CategoriaID']][] = $p;
    }

    // Filtrar categorías solo a las que tienen productos
    $categorias = array_filter($categorias, fn($cat) => isset($productosPorCategoria[$cat['CategoriaID']]));

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Lista de Productos</title>
  <link rel="stylesheet" href="index.css" />
  <link rel="stylesheet" href="dashboard.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

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
  <h1 class="text-center mb-4">Lista de Productos</h1>

  <form class="d-flex mb-4" method="GET" action="">
    <input class="form-control me-2" type="search" name="buscar" placeholder="Buscar..." value="<?= htmlspecialchars($terminoBusqueda) ?>">

    <select name="categoria" class="form-select me-2" style="max-width: 200px;">
      <option value="todas" <?= ($categoriaFiltro === 'todas' || $categoriaFiltro === '') ? 'selected' : '' ?>>Todas las categorías</option>
      <?php foreach ($categorias as $cat): ?>
        <option value="<?= $cat['CategoriaID'] ?>" <?= ($categoriaFiltro == $cat['CategoriaID']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat['Nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="orden" class="form-select me-2" style="max-width: 200px;">
  <option value="">Ordenar por...</option>
  <option value="mas_vendidos" <?= ($ordenFiltro === 'mas_vendidos') ? 'selected' : '' ?>>Más vendidos</option>
</select>


    <button class="btn btn-primary" type="submit">Buscar</button>
  </form>

  <div class="text-end mb-3">
    <a href="carrito.php" class="btn btn-danger border border-warning position-relative">
      <img src="images/icone-de-panier-rouge.png" alt="Carrito" class="img-fluid" style="max-width: 24px;">
    </a>
  </div>

  <?php if (empty($categorias)): ?>
    <p>No se encontraron productos.</p>
  <?php else: ?>
    <?php foreach ($categorias as $categoria): ?>
      <div class="mb-5">
        <h3><?= htmlspecialchars($categoria['Nombre']) ?></h3>
        <p><?= htmlspecialchars($categoria['Descripcion']) ?></p>
        <div class="row">
          <?php foreach ($productosPorCategoria[$categoria['CategoriaID']] ?? [] as $producto): ?>
            <div class="col-md-4">
              <div class="card mb-4">
                <img src="<?= htmlspecialchars($producto['Imagen']) ?: 'https://via.placeholder.com/300x200' ?>" class="card-img-top" alt="<?= htmlspecialchars($producto['Nombre']) ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($producto['Nombre']) ?></h5>
                  <p class="card-text"><?= htmlspecialchars($producto['Descripcion']) ?></p>
                  <p><strong>$<?= number_format($producto['Precio'], 2) ?></strong></p>
                  <input type="number" min="1" max="<?= (int)$producto['Stock'] ?>" value="1" class="form-control cantidad mb-2" data-productoid="<?= $producto['ProductoID'] ?>">
                  <button class="btn btn-primary agregar-carrito" data-productoid="<?= $producto['ProductoID'] ?>">Agregar al carrito</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<footer>
  <p>&copy; 2025 MIPC. Todos los derechos reservados.</p>
  <a class="nav-link active mx-3" href="Quienes_somos.php">
     <h6>saber mas de nosotros</h6>
  </a>

  <a class="nav-link active mx-3" href="Contactanos.php">
    <h6>contactanos</h6>
  </a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Agregar producto al carrito
document.querySelectorAll('.agregar-carrito').forEach(btn => {
  btn.addEventListener('click', () => {
    const productoID = btn.getAttribute('data-productoid');
    const cantidad = document.querySelector(`.cantidad[data-productoid='${productoID}']`).value;

    fetch('agregar_al_carrito.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ProductoID=${productoID}&Cantidad=${cantidad}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Producto agregado al carrito");
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch(() => alert("Error al agregar al carrito"));
  });
});
</script>

</body>
</html>
