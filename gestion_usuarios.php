<?php
session_start();

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'MiPC5';
$user = 'root';
$pass = '1234';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}

// Inicializar mensajes de sesión en variables locales
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// Manejar eliminación vía GET
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $idEliminar = (int)$_GET['eliminar'];

    try {
        // Obtener PedidoID de los pedidos del usuario
        $stmtPedidos = $pdo->prepare("SELECT PedidoID FROM Pedidos WHERE UsuarioID = ?");
        $stmtPedidos->execute([$idEliminar]);
        $pedidoIDs = $stmtPedidos->fetchAll(PDO::FETCH_COLUMN);

        // Eliminar detalles de pedidos relacionados
        if (!empty($pedidoIDs)) {
            $placeholders = implode(',', array_fill(0, count($pedidoIDs), '?'));
            $stmtEliminarDetalles = $pdo->prepare("DELETE FROM DetallesPedido WHERE PedidoID IN ($placeholders)");
            $stmtEliminarDetalles->execute($pedidoIDs);
        }

        // Eliminar pedidos del usuario
        $pdo->prepare("DELETE FROM Pedidos WHERE UsuarioID = ?")->execute([$idEliminar]);

        // Eliminar carrito del usuario
        $pdo->prepare("DELETE FROM Carrito WHERE UsuarioID = ?")->execute([$idEliminar]);

        // Eliminar direcciones del usuario
        $pdo->prepare("DELETE FROM Direcciones WHERE UsuarioID = ?")->execute([$idEliminar]);

        // Eliminar usuario
        $pdo->prepare("DELETE FROM Usuarios WHERE UsuarioID = ?")->execute([$idEliminar]);

        $_SESSION['success'] = "Usuario eliminado correctamente.";
        header("Location: gestion_usuarios.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "No se pudo eliminar el usuario. Aún tiene pedidos en proceso o datos relacionados.";
        header("Location: gestion_usuarios.php");
        exit;
    }
}

// Manejar actualización vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['UsuarioID'])) {
    // Verificar si nombre de usuario o email ya existen (excluyendo al mismo usuario)
    $verificar = $pdo->prepare("SELECT * FROM Usuarios WHERE (NombreUsuario = ? OR Email = ?) AND UsuarioID != ?");
    $verificar->execute([$_POST['NombreUsuario'], $_POST['Email'], $_POST['UsuarioID']]);

    if ($verificar->rowCount() > 0) {
        $_SESSION['error'] = "El nombre de usuario o el correo ya están en uso.";
        header("Location: gestion_usuarios.php");
        exit;
    }

    // Si no hay duplicados, actualizar
    $stmt = $pdo->prepare("UPDATE Usuarios SET Nombre=?, Apellidos=?, NombreUsuario=?, Email=?, RolID=?, UltimaModificacion=NOW() WHERE UsuarioID=?");
    $stmt->execute([
        $_POST['Nombre'],
        $_POST['Apellidos'],
        $_POST['NombreUsuario'],
        $_POST['Email'],
        $_POST['RolID'],
        $_POST['UsuarioID']
    ]);

    $_SESSION['success'] = "Usuario actualizado correctamente.";
    header("Location: gestion_usuarios.php");
    exit;
}

// Buscar usuarios y roles
$buscar = $_GET['buscar'] ?? '';
$rolSeleccionado = $_GET['rol'] ?? '';

$sql = "SELECT U.*, R.Nombre AS NombreRol
        FROM Usuarios U
        INNER JOIN Roles R ON U.RolID = R.RolID
        WHERE (U.Nombre LIKE :buscar OR U.NombreUsuario LIKE :buscar)";
$params = [':buscar' => "%$buscar%"];

if (!empty($rolSeleccionado)) {
    $sql .= " AND U.RolID = :rol";
    $params[':rol'] = $rolSeleccionado;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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

<div class="container py-4">
<?php if ($error): ?>
  <div class="alert alert-danger" role="alert">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="alert alert-success" role="alert">
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<h2 class="mb-4 text-center">Gestión de Usuarios</h2>

<div class="row">
  <div class="col-lg-6">
<form method="get" class="mb-3">
  <div class="input-group">
    <select name="rol" class="form-select" onchange="this.form.submit()">
      <option value="">Todos los roles</option>
      <?php foreach ($roles as $rol): ?>
        <option value="<?= $rol['RolID'] ?>" <?= (isset($_GET['rol']) && $_GET['rol'] == $rol['RolID']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($rol['Nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="text" class="form-control" placeholder="Buscar por nombre o usuario..." name="buscar" value="<?= htmlspecialchars($buscar) ?>" />
    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
  </div>
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
  return true;
}
</script>

</body>

</html>
