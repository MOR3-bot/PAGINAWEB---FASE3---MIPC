<?php
// Conexión a la base de datos
$host = 'localhost';
$dbname = 'MiPC5';
$user = 'root';
$pass = '1234'; // Cambia según tu config


$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Manejar eliminación vía GET
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $idEliminar = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM Usuarios WHERE UsuarioID = ?");
    $stmt->execute([$idEliminar]);
    header("Location: gestion_usuarios.php");
    exit;
}

// Manejar actualización vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['UsuarioID'])) {
    $stmt = $pdo->prepare("UPDATE Usuarios SET Nombre=?, Apellidos=?, NombreUsuario=?, Email=?, RolID=?, UltimaModificacion=NOW() WHERE UsuarioID=?");
    $stmt->execute([
        $_POST['Nombre'],
        $_POST['Apellidos'],
        $_POST['NombreUsuario'],
        $_POST['Email'],
        $_POST['RolID'],
        $_POST['UsuarioID']
    ]);
    header("Location: gestion_usuarios.php");
    exit;
}

// Buscar usuarios
$buscar = $_GET['buscar'] ?? '';
$stmt = $pdo->prepare("SELECT U.*, R.Nombre AS NombreRol FROM Usuarios U INNER JOIN Roles R ON U.RolID = R.RolID WHERE U.Nombre LIKE ? OR U.NombreUsuario LIKE ?");
$stmt->execute(["%$buscar%", "%$buscar%"]);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener roles para el select
$roles = $pdo->query("SELECT * FROM Roles")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Gestión de Usuarios</title>
  <link rel="stylesheet" href="dashboard.css">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" />
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

<div class="container py-4">
<h2 class="mb-4 text-center">Gestión de Usuarios</h2>

<div class="row">
  <!-- Lista usuarios -->
  <div class="col-lg-6">
    <form class="input-group mb-3" method="get">
      <input type="text" class="form-control" placeholder="Buscar por nombre o usuario..." name="buscar" value="<?= htmlspecialchars($buscar) ?>" />
      <button class="btn btn-outline-secondary" type="submit">Buscar</button>
    </form>

    <table class="table table-bordered table-hover">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Usuario</th>
          <th>Nombre</th>
          <th>Rol</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= $u['UsuarioID'] ?></td>
            <td><?= htmlspecialchars($u['NombreUsuario']) ?></td>
            <td><?= htmlspecialchars($u['Nombre'] . ' ' . $u['Apellidos']) ?></td>
            <td><?= htmlspecialchars($u['NombreRol']) ?></td>
            <td>
              <button class="btn btn-sm btn-warning" onclick='editarUsuario(<?= json_encode($u) ?>)'>Editar</button>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if(count($usuarios) === 0): ?>
          <tr><td colspan="5" class="text-center">No se encontraron usuarios.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Formulario editar usuario -->
  <div class="col-lg-6">
    <div class="card p-4">
      <h4 class="mb-3">Editar Usuario</h4>
      <form id="formEditar" method="post" action="gestion_usuarios.php" onsubmit="return validarFormulario()">
        <input type="hidden" name="UsuarioID" id="UsuarioID" />
        <div class="mb-2">
          <label>Nombre</label>
          <input type="text" name="Nombre" id="Nombre" class="form-control" required />
        </div>
        <div class="mb-2">
          <label>Apellidos</label>
          <input type="text" name="Apellidos" id="Apellidos" class="form-control" required />
        </div>
        <div class="mb-2">
          <label>Nombre de Usuario</label>
          <input type="text" name="NombreUsuario" id="NombreUsuario" class="form-control" required />
        </div>
        <div class="mb-2">
          <label>Email</label>
          <input type="email" name="Email" id="Email" class="form-control" required />
        </div>
        <div class="mb-2">
          <label>Rol</label>
          <select name="RolID" id="RolID" class="form-select" required>
            <option value="" disabled selected>Selecciona un rol</option>
            <?php foreach ($roles as $rol): ?>
              <option value="<?= $rol['RolID'] ?>"><?= htmlspecialchars($rol['Nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="d-flex justify-content-between mt-3">
          <button type="submit" class="btn btn-primary">Guardar</button>
          <button type="button" class="btn btn-danger" id="btnEliminar" disabled>Eliminar</button>
        </div>
      </form>
    </div>
  </div>
</div>
</div>

<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
  crossorigin="anonymous"
></script>
<script>
// Rellena el formulario con los datos seleccionados
function editarUsuario(data) {
  document.getElementById('UsuarioID').value = data.UsuarioID;
  document.getElementById('Nombre').value = data.Nombre;
  document.getElementById('Apellidos').value = data.Apellidos;
  document.getElementById('NombreUsuario').value = data.NombreUsuario;
  document.getElementById('Email').value = data.Email;
  document.getElementById('RolID').value = data.RolID;
  document.getElementById('btnEliminar').disabled = false;

  document.getElementById('btnEliminar').onclick = () => {
    if(confirm(`¿Eliminar al usuario ${data.NombreUsuario}?`)) {
      // Redirige pasando parámetro eliminar por GET
      window.location.href = `gestion_usuarios.php?eliminar=${data.UsuarioID}`;
    }
  };
}

function validarFormulario() {
  // Aquí puedes agregar validaciones extra si quieres
  return true;
}
</script>

</body>

</html>
