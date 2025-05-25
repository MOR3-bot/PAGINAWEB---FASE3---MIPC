<?php
session_start();

// Configuración conexión
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "MiPC5";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Revisar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuarioInput = trim($_POST['usuario']);
    $contrasenaInput = trim($_POST['contrasena']);

    if (empty($usuarioInput) || empty($contrasenaInput)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        // Buscar usuario por nombre de usuario o email
$sql = "SELECT u.UsuarioID, u.NombreUsuario, u.Contrasena, u.ImagenPerfil, r.Nombre AS NombreRol 
        FROM Usuarios u
        JOIN Roles r ON u.RolID = r.RolID
        WHERE u.NombreUsuario = ? OR u.Email = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $usuarioInput, $usuarioInput);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 1) {
    $user = $resultado->fetch_assoc();

    if (password_verify($contrasenaInput, $user['Contrasena'])) {
        // Login correcto: guardamos datos en sesión
    $_SESSION['UsuarioID'] = $user['UsuarioID'];
    $_SESSION['NombreUsuario'] = $user['NombreUsuario'];
    $_SESSION['ImagenPerfil'] = $user['ImagenPerfil'] ?? 'uploads/profile photos/default_profile.png';
    $_SESSION['RolID'] = $user['RolID'];        
    $_SESSION['RolNombre'] = $user['NombreRol']; 



        header("Location: index.php");
        exit();
    } else {
        $error = "Contraseña incorrecta.";
    }
} else {
    $error = "Usuario no encontrado.";
}

    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Iniciar Sesión</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="login-container">
    <form method="POST" action="login.php" class="login-form" id="loginForm" novalidate>
      <h2>Iniciar Sesión</h2>

      <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <div class="form-group">
        <i class="fas fa-user icon"></i>
        <input type="text" name="usuario" placeholder="Usuario o correo" required />
      </div>

      <div class="form-group password-group">
        <i class="fas fa-lock icon"></i>
        <input type="password" name="contrasena" placeholder="Contraseña" id="password" required />
        <i class="fas fa-eye toggle-password" id="togglePassword" style="cursor:pointer;"></i>
      </div>

      <button type="submit" class="btn">Iniciar Sesión</button>

      <div class="signup-link">
        <p>¿No tienes cuenta? <a href="REGusuario.php" class="signup-text">¡Crea una ahora aquí!</a></p>
      </div>
    </form>
  </div>

  <script>
    // Mostrar/ocultar contraseña
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    togglePassword.addEventListener('click', () => {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      togglePassword.classList.toggle('fa-eye-slash');
    });
  </script>
</body>
</html>
