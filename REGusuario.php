<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "MiPC5";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $nombreUsuario = trim($_POST['nombreUsuario']);
    $email = trim($_POST['email']);
    $contrasena = $_POST['contrasena'];  // No hacer trim para contraseña
    $rolID = 3; // Rol por defecto: Usuario

    // Validar campos básicos
    if (!$nombre || !$apellidos || !$nombreUsuario || !$email || !$contrasena) {
        $error = "Por favor completa todos los campos.";
    } else {
        // Verificar que no exista el usuario ni email
        $sqlCheck = "SELECT * FROM Usuarios WHERE NombreUsuario = ? OR Email = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("ss", $nombreUsuario, $email);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            $error = "El nombre de usuario o email ya están registrados.";
        } else {
            // Hashear contraseña
            $hashContrasena = password_hash($contrasena, PASSWORD_DEFAULT);

            // Insertar nuevo usuario
            $sqlInsert = "INSERT INTO Usuarios (Nombre, Apellidos, NombreUsuario, Contrasena, Email, RolID) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bind_param("sssssi", $nombre, $apellidos, $nombreUsuario, $hashContrasena, $email, $rolID);

            if ($stmtInsert->execute()) {
                header("Location: login.php");  
                exit();
            } else {
            $error = "Error al registrar usuario: " . $conn->error;
            }

        }
        $stmtCheck->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Registro de Usuario</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
<link rel="stylesheet" href="styles.css" />
</head>
<body>
<div class="login-container">
    <form method="POST" action="REGusuario.php" class="login-form" id="registerForm" novalidate>
        <h2>Registro de Usuario</h2>

        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <div class="form-group">
            <i class="fas fa-user icon"></i>
            <input type="text" name="nombre" placeholder="Nombre" required />
        </div>

        <div class="form-group">
            <i class="fas fa-user icon"></i>
            <input type="text" name="apellidos" placeholder="Apellidos" required />
        </div>

        <div class="form-group">
            <i class="fas fa-user icon"></i>
            <input type="text" name="nombreUsuario" placeholder="Nombre de Usuario" required />
        </div>

        <div class="form-group">
            <i class="fas fa-envelope icon"></i>
            <input type="email" name="email" placeholder="Correo Electrónico" required />
        </div>

        <div class="form-group password-group">
            <i class="fas fa-lock icon"></i>
            <input type="password" name="contrasena" id="password" placeholder="Contraseña" required />
            <i class="fas fa-eye toggle-password" id="togglePassword" style="cursor:pointer;"></i>
        </div>

        <button type="submit" class="btn">Registrarse</button>

        <div class="login-link">
            <p>¿Ya tienes cuenta? <a href="login.php" class="login-text">¡Inicia sesión aquí!</a></p>
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
