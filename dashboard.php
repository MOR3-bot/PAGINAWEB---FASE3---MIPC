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

// Conexión a la base de datos
$host = 'localhost';
$db   = 'MiPC5';
$user = 'root'; // Cambia según tu configuración
$pass = '1234'; // Cambia según tu configuración
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}

// Recuperar nombre de usuario e imagen desde la sesión
$usuario_nombre = $_SESSION['NombreUsuario'] ?? '';
$imagenPerfil = $_SESSION['ImagenPerfil'] ?? '';
$rutaImagen = (!empty($imagenPerfil) && file_exists($imagenPerfil)) ? $imagenPerfil : 'images/default_profile.png';

// Consultar Inventario
$sql_inventario = "SELECT Nombre, Stock FROM Productos";
$inventario = $pdo->query($sql_inventario)->fetchAll();

// Consultar Ventas excluyendo pedidos cancelados (EstadoID = 5)
$sql_ventas = "
    SELECT p.Nombre, SUM(dp.Cantidad) AS TotalVendidas
    FROM DetallesPedido dp
    JOIN Productos p ON dp.ProductoID = p.ProductoID
    JOIN Pedidos ped ON dp.PedidoID = ped.PedidoID
    WHERE ped.EstadoID <> 5
    GROUP BY p.Nombre
";
$ventas = $pdo->query($sql_ventas)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
  
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
        <li class="nav-item">
          <a class="nav-link active mx-3" aria-current="page" href="dashboard.php"> 
            <img src="images/30240.png" class="rounded" alt=""> 
            <h6>admin</h6>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container min-vh-100 d-flex flex-column mt-4">
  <div class="row flex-grow-1 w-100">
    <div class="col-12 col-lg-4 mb-4 d-flex">
      <div class="card border border-dark text-dark rounded w-100 p-4 d-flex flex-column justify-content-center centrado">
        <h2 class="text-center mb-4">Dashboard</h2>

        <div class="d-flex justify-content-center mb-4">
          <div class="card" style="width: 18rem;">
            <img src="<?php echo htmlspecialchars($rutaImagen); ?>" alt="Foto de perfil" class="card-img-top">
            <div class="card-body">
              <h5 class="card-title">Usuario: <?php echo htmlspecialchars($usuario_nombre); ?></h5>
            </div>
          </div>
        </div>

        <div class="text-center">
          <h3>Reportes</h3>
          <div class="d-flex flex-column align-items-center gap-3 mt-3">
            <button type="button" class="btn btn-outline-dark w-75" onclick="mostrarReportes('Inventario')">Inventario</button>
            <button type="button" class="btn btn-outline-dark w-75" onclick="mostrarReportes('Ventas')">Ventas</button>
          </div>

          <h3 class="mt-4">Productos</h3>
          <button type="button" class="btn btn-outline-dark w-75" onclick="window.location.href='newproducto.php'">Gestión de Productos</button>
          <button type="button" class="btn btn-outline-dark w-75" onclick="window.location.href='Categorias.php'">Nueva categoría</button>

          <?php if (isset($_SESSION["RolNombre"]) && $_SESSION["RolNombre"] === "Administrador"): ?>
              <h3 class="mt-4">Usuarios</h3> 
              <button type="button" class="btn btn-outline-dark w-75" onclick="window.location.href='gestion_usuarios.php'">
                  Gestor de Usuarios
              </button>
          <?php endif; ?>


          <h3 class="mt-4">pedidos</h3>
          <button type="button" class="btn btn-outline-dark w-75" onclick="window.location.href='editar_pedidos.php'">pedidos en proceso</button>

          <h3 class="mt-4">opiniones</h3>
          <button type="button" class="btn btn-outline-dark w-75" onclick="window.location.href='admin_contacto.php'">opiniones de los usuario</button>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8 d-flex">
      <div class="card border border-dark text-dark rounded w-100 p-4" id="contenidoPrincipal">
        <h2 id="tituloContenido">Contenido Principal</h2>
        <div id="reportesContent" class="d-none d-flex flex-column gap-4">

          <!-- Contenedor para la primera gráfica -->
          <div class="card">
            <div class="card-body">
              <canvas id="grafica1" height="150" class="w-100"></canvas>
            </div>
          </div>

          <!-- Contenedor para la segunda gráfica -->
          <div class="card">
            <div class="card-body">
              <canvas id="grafica2" height="150" class="w-100"></canvas>
            </div>
          </div>

          <!-- Tabla -->
          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Tabla de Datos</h4>
              <div class="table-responsive">
                <table class="table table-bordered mt-2">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Producto</th>
                      <th>Cantidad</th>
                    </tr>
                  </thead>
                  <tbody id="tablaDatos">
                    <!-- Se llena con JS -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>


<!-- Datos en JavaScript -->
<script>
const datosInventario = <?= json_encode($inventario); ?>;
const datosVentas = <?= json_encode($ventas); ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const tituloContenido = document.getElementById("tituloContenido");
  const reportesDiv = document.getElementById("reportesContent");
  const tablaDatos = document.getElementById("tablaDatos");
  let chart1, chart2;

  window.mostrarReportes = function(tipo) {
    tituloContenido.textContent = "Reporte de " + tipo;
    reportesDiv.classList.remove("d-none");
    tablaDatos.innerHTML = "";
    if (chart1) chart1.destroy();
    if (chart2) chart2.destroy();

    let labels = [], datos = [];

    if (tipo === "Inventario") {
      datosInventario.forEach((item, i) => {
        tablaDatos.innerHTML += `<tr><td>${i+1}</td><td>${item.Nombre}</td><td>${item.Stock}</td><td>${item.Stock > 0 ? 'Disponible' : 'Agotado'}</td></tr>`;
        labels.push(item.Nombre);
        datos.push(item.Stock);
      });
    } else {
      datosVentas.forEach((item, i) => {
        tablaDatos.innerHTML += `<tr><td>${i+1}</td><td>${item.Nombre}</td><td>${item.TotalVendidas}</td></tr>`;
        labels.push(item.Nombre);
        datos.push(item.TotalVendidas);
      });
    }

    chart1 = new Chart(document.getElementById("grafica1"), {
      type: "bar",
      data: {
        labels: labels,
        datasets: [{
          label: tipo + " por producto",
          data: datos,
          backgroundColor: "rgba(75, 192, 192, 0.6)"
        }]
      }
    });

    chart2 = new Chart(document.getElementById("grafica2"), {
      type: "line",
      data: {
        labels: labels,
        datasets: [{
          label: tipo + " por producto",
          data: datos,
          borderColor: "rgba(75, 192, 192, 1)",
          fill: false,
          tension: 0.1
        }]
      }
    });
  }
});
</script>

</body>
</html>
