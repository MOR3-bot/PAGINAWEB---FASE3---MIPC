<?php
session_start();
$usuarioID = $_SESSION['UsuarioID'] ?? 1; // Ajusta según tu sesión

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");  // Cambia a la página de login real
    exit();
}

// Conexión PDO
$host = 'localhost';
$db = 'MiPC5';
$user = 'root';
$pass = '1234';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$mensaje = '';

// Procesar cambio de foto
if (isset($_POST['cambiar_foto'])) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $permitidas)) {
            $dirUploads = 'uploads/profile photos/';
            if (!is_dir($dirUploads)) {
                mkdir($dirUploads, 0777, true);
            }
            $nuevoNombre = $dirUploads . "usuario_" . $usuarioID . "." . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $nuevoNombre)) {
                $sql = "UPDATE Usuarios SET ImagenPerfil = ? WHERE UsuarioID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nuevoNombre, $usuarioID]);
                $mensaje = "Foto actualizada correctamente.";
            } else {
                $mensaje = "Error al subir la imagen.";
            }
        } else {
            $mensaje = "Formato no permitido. Usa jpg, jpeg, png o gif.";
        }
    } else {
        $mensaje = "No se seleccionó archivo o hubo un error.";
    }
}

// Procesar edición de dirección
if (isset($_POST['editar_direccion'])) {
    $estado = $_POST['estado'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $colonia = $_POST['colonia'] ?? '';
    $calle = $_POST['calle'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $numeroOpcional = $_POST['numero_opcional'] ?? '';
    $codigoPostal = $_POST['codigo_postal'] ?? '';

    if ($estado && $ciudad && $colonia && $calle && $numero && $codigoPostal) {
        $sql = "SELECT DireccionID FROM Direcciones WHERE UsuarioID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$usuarioID]);
        $direccionID = $stmt->fetchColumn();

        if ($direccionID) {
            $sql = "UPDATE Direcciones SET Estado=?, Ciudad=?, Colonia=?, Calle=?, Numero=?, NumeroOpcional=?, CodigoPostal=? WHERE DireccionID=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$estado, $ciudad, $colonia, $calle, $numero, $numeroOpcional, $codigoPostal, $direccionID]);
            $mensaje = "Dirección actualizada.";
        } else {
            $sql = "INSERT INTO Direcciones (UsuarioID, Estado, Ciudad, Colonia, Calle, Numero, NumeroOpcional, CodigoPostal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);   
            $stmt->execute([$usuarioID, $estado, $ciudad, $colonia, $calle, $numero, $numeroOpcional, $codigoPostal]);
            $mensaje = "Dirección guardada.";
        }
    } else {
        $mensaje = "Por favor, completa todos los campos de dirección incluyendo Código Postal.";
    }
}

// Procesar cambio de contraseña
if (isset($_POST['cambiar_contrasena'])) {
    $contrasenaActual = $_POST['contrasena_actual'] ?? '';
    $nuevaContrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar = $_POST['confirmar_contrasena'] ?? '';

    if ($nuevaContrasena !== $confirmar) {
        $mensaje = "La nueva contraseña y la confirmación no coinciden.";
    } else {
        $sql = "SELECT Contrasena FROM Usuarios WHERE UsuarioID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$usuarioID]);
        $hash = $stmt->fetchColumn();

        if (password_verify($contrasenaActual, $hash)) {
            $nuevoHash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
            $sql = "UPDATE Usuarios SET Contrasena = ? WHERE UsuarioID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nuevoHash, $usuarioID]);
            $mensaje = "Contraseña actualizada correctamente.";
        } else {
            $mensaje = "La contraseña actual es incorrecta.";
        }
    }
}

// Procesar edición de información personal
if (isset($_POST['editar_personal'])) {
    $nombre = $_POST['nombre'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $nombreUsuario = $_POST['nombre_usuario'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($nombre && $apellidos && $nombreUsuario && $email) {
        // Verificar si el nombre de usuario ya existe en otro usuario
        $sqlCheckUsuario = "SELECT COUNT(*) FROM Usuarios WHERE NombreUsuario = ? AND UsuarioID != ?";
        $stmtCheckUsuario = $conn->prepare($sqlCheckUsuario);
        $stmtCheckUsuario->execute([$nombreUsuario, $usuarioID]);
        $usuarioExiste = $stmtCheckUsuario->fetchColumn();

        // Verificar si el correo ya existe en otro usuario
        $sqlCheckEmail = "SELECT COUNT(*) FROM Usuarios WHERE Email = ? AND UsuarioID != ?";
        $stmtCheckEmail = $conn->prepare($sqlCheckEmail);
        $stmtCheckEmail->execute([$email, $usuarioID]);
        $emailExiste = $stmtCheckEmail->fetchColumn();

        if ($usuarioExiste > 0) {
            $mensaje = "El nombre de usuario ya está en uso por otro usuario.";
        } elseif ($emailExiste > 0) {
            $mensaje = "El correo electrónico ya está registrado por otro usuario.";
        } else {
            // Si no hay conflictos, actualizar
            $sql = "UPDATE Usuarios SET Nombre = ?, Apellidos = ?, NombreUsuario = ?, Email = ? WHERE UsuarioID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $apellidos, $nombreUsuario, $email, $usuarioID]);
            $mensaje = "Información personal actualizada correctamente.";
        }
    } else {
        $mensaje = "Todos los campos personales son obligatorios.";
    }
}



// Obtener datos del usuario y dirección
$sql = "SELECT u.NombreUsuario, u.Email, u.ImagenPerfil, u.Nombre, u.Apellidos,
               d.Estado, d.Ciudad, d.Colonia, d.Calle, d.Numero, d.NumeroOpcional, d.CodigoPostal
        FROM Usuarios u
        LEFT JOIN Direcciones d ON u.UsuarioID = d.UsuarioID
        WHERE u.UsuarioID = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$usuarioID]);
$usuario = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gestión de Usuario</title>
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
    <style>
        .profile-pic {
            width: 150px;
            height: 150px;
            object-fit: cover;
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

<div class="container py-4">
<h1>Gestión de Usuario</h1>

    <?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?php echo (strpos($mensaje, 'actualizada') !== false) ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>


    <form method="post">
        <button type="submit" name="logout" class="btn btn-danger float-end mb-3">Cerrar sesión</button>
    </form>

    <div class="row">
        <div class="col-md-4">
            <h3>Foto de perfil</h3>
            <?php if (!empty($usuario['ImagenPerfil']) && file_exists($usuario['ImagenPerfil'])): ?>
                <img src="<?php echo htmlspecialchars($usuario['ImagenPerfil']); ?>" alt="Foto perfil" class="profile-pic rounded-circle" />
            <?php else: ?>
                <img src="uploads/profile photos/default_profile.png" alt="Foto perfil" class="profile-pic rounded-circle" />
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="mt-3">
                <input type="hidden" name="cambiar_foto" value="1" />
                <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/gif" required />
                <button type="submit" class="btn btn-primary mt-2">Cambiar foto</button>
            </form>

            <!-- Botón cambiar contraseña -->
            <button type="button" class="btn btn-secondary mt-3" onclick="togglePasswordForm()">Cambiar contraseña</button>

            <!-- Formulario cambio contraseña -->
            <form method="post" class="mt-3" id="passwordForm" style="display: none;">
                <input type="hidden" name="cambiar_contrasena" value="1">
                <div class="mb-2">
                    <label class="form-label">Contraseña actual</label>
                    <input type="password" name="contrasena_actual" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" name="nueva_contrasena" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Confirmar nueva contraseña</label>
                    <input type="password" name="confirmar_contrasena" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
            </form>
        </div>

        <div class="col-md-8">
            <h3>Información personal</h3>
<form method="post">
    <input type="hidden" name="editar_personal" value="1" />
    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['Nombre'] ?? ''); ?>" required />
    </div>
    <div class="mb-3">
        <label for="apellidos" class="form-label">Apellidos</label>
        <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?php echo htmlspecialchars($usuario['Apellidos'] ?? ''); ?>" required />
    </div>
    <div class="mb-3">
        <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($usuario['NombreUsuario'] ?? ''); ?>" required />
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['Email'] ?? ''); ?>" required />
    </div>
    <button type="submit" class="btn btn-primary">Guardar cambios</button>
</form>


            <h3>Dirección</h3>
            <form method="post" id="formEditarDireccion">
                <input type="hidden" name="editar_direccion" value="1" />
                <div class="row mb-3">
                    <div class="col">
                        <label for="estado" class="form-label">Estado</label>
                        <input type="text" class="form-control" id="estado" name="estado" value="<?php echo htmlspecialchars($usuario['Estado'] ?? ''); ?>" required />
                    </div>
                    <div class="col">
                        <label for="ciudad" class="form-label">Ciudad</label>
                        <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?php echo htmlspecialchars($usuario['Ciudad'] ?? ''); ?>" required />
                    </div>
                </div>
                <div class="mb-3">
                    <label for="colonia" class="form-label">Colonia</label>
                    <input type="text" class="form-control" id="colonia" name="colonia" value="<?php echo htmlspecialchars($usuario['Colonia'] ?? ''); ?>" required />
                </div>
                <div class="mb-3">
                    <label for="calle" class="form-label">Calle</label>
                    <input type="text" class="form-control" id="calle" name="calle" value="<?php echo htmlspecialchars($usuario['Calle'] ?? ''); ?>" required />
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($usuario['Numero'] ?? ''); ?>" required />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="numero_opcional" class="form-label">Número Opcional</label>
                        <input type="text" class="form-control" id="numero_opcional" name="numero_opcional" value="<?php echo htmlspecialchars($usuario['NumeroOpcional'] ?? ''); ?>" />
                    </div>
                </div>
                <div class="mb-3">
                    <label for="codigo_postal" class="form-label">Código Postal</label>
                    <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" value="<?php echo htmlspecialchars($usuario['CodigoPostal'] ?? ''); ?>" required />
                </div>
                <button type="submit" class="btn btn-primary">Guardar dirección</button>
            </form>
        </div>
    </div>
</div>
<footer>
  <p>&copy; 2025 MIPC. Todos los derechos reservados.</p>
  <a class="nav-link active mx-3" href="Quienes_somos.html">
     <h6>saber mas de nosotros</h6>
  </a>

  <a class="nav-link active mx-3" href="Contactanos.php">
    <h6>contactanos</h6>
  </a>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordForm() {
            const form = document.getElementById('passwordForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
