<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sobre Nosotros</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="index.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background: #fff;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    header {
      background: #eaeced;
      padding: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .menu-icon {
      font-size: 24px;
      margin-left: 10px;
    }
    .icons {
      display: flex;
      gap: 10px;
      margin-right: 10px;
    }
    .icons div {
      width: 24px;
      height: 24px;
      background: #ccc;
      border-radius: 5px;
    }
    main {
      padding: 20px;
    }
    h2 {
      font-style: italic;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      max-width: 800px;
      margin: auto;
    }
    .block {
      border: 1px solid #aaa;
      padding: 20px;
      border-radius: 15px;
      text-align: center;
    }
    .valores {
      grid-column: span 2;
    }
    footer {
      background-color: #000000;
      color: white;
      text-align: center;
      padding: 15px 0;
      margin-top: auto;
    }
  </style>
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


<main>
  <h2>Sobre nosotros</h2>
  <div class="grid">
    <div class="block">
      <h3>Imagen</h3>
      <img src="images/MiPC_Logo.jpg" alt="MiPC" style="display: block; margin: 10px auto; width: 80%; max-width: 200px;">
    </div>

    <div class="block">
      <h3>Quiénes somos</h3>
      <p>Somos una empresa comprometida con brindar los productos de más alta calidad para nuestros clientes.</p>
    </div>
    <div class="block">
      <h3>Misión</h3>
      <p>Ofrecer productos y servicios de alta calidad que generen valor y satisfacción a nuestros usuarios.</p>
    </div>
    <div class="block">
      <h3>Visión</h3>
      <p>Ser líderes en el sector de ventas de Videojuegos y Tecnología a nivel nacional, promoviendo la innovación y el desarrollo sustentable.</p>
    </div>
    <div class="block valores">
      <h3>Valores</h3>
      <p>Responsabilidad, honestidad, innovación, trabajo en equipo y orientación al cliente.</p>
    </div>
  </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

</body>
</html>
