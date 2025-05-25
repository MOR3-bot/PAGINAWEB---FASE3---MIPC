<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "MiPC5");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

session_start();
$id_usuario = $_SESSION['UsuarioID'] ?? 1; // Usa 1 por defecto si no hay sesión

// Recibir datos del formulario
$nombre = $_POST['Nombre'] ?? '';
$apellidos = $_POST['Apellidos'] ?? '';
$nombreUsuario = $_POST['NombreUsuario'] ?? '';
$email = $_POST['Email'] ?? '';
$estado = $_POST['Estado'] ?? '';
$ciudad = $_POST['Ciudad'] ?? '';
$colonia = $_POST['Colonia'] ?? '';
$calle = $_POST['Calle'] ?? '';
$numero = $_POST['Numero'] ?? '';
$numeroOpcional = $_POST['NumeroOpcional'] ?? '';
$codigoPostal = $_POST['CodigoPostal'] ?? '';

// Procesar imagen si se carga
$imagenPerfil = null;
if (!empty($_FILES['ImagenPerfil']['name'])) {
    $carpetaDestino = "uploads/profile photos/";
    if (!file_exists($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
    }
    $nombreArchivo = basename($_FILES["ImagenPerfil"]["name"]);
    $rutaArchivo = $carpetaDestino . time() . "_" . $nombreArchivo;

    if (move_uploaded_file($_FILES["ImagenPerfil"]["tmp_name"], $rutaArchivo)) {
        $imagenPerfil = $rutaArchivo;
    }
}

// Actualizar tabla Usuarios
$sql_usuario = "UPDATE Usuarios SET 
    Nombre = ?, 
    Apellidos = ?, 
    NombreUsuario = ?, 
    Email = ?, 
    UltimaModificacion = NOW()";

if ($imagenPerfil !== null) {
    $sql_usuario .= ", ImagenPerfil = ?";
}

$sql_usuario .= " WHERE UsuarioID = ?";

$stmt_usuario = $conexion->prepare($sql_usuario);

if ($imagenPerfil !== null) {
    $stmt_usuario->bind_param("sssssi", $nombre, $apellidos, $nombreUsuario, $email, $imagenPerfil, $id_usuario);
} else {
    $stmt_usuario->bind_param("ssssi", $nombre, $apellidos, $nombreUsuario, $email, $id_usuario);
}

$stmt_usuario->execute();
$stmt_usuario->close();

// Verificar si ya hay dirección registrada
$sql_check = "SELECT * FROM Direcciones WHERE UsuarioID = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("i", $id_usuario);
$stmt_check->execute();
$resultado = $stmt_check->get_result();
$tieneDireccion = $resultado->num_rows > 0;
$stmt_check->close();

// Insertar o actualizar dirección
if ($tieneDireccion) {
    $sql_direccion = "UPDATE Direcciones SET 
        Estado = ?, Ciudad = ?, Colonia = ?, Calle = ?, 
        Numero = ?, NumeroOpcional = ?, CodigoPostal = ? 
        WHERE UsuarioID = ?";
    $stmt_direccion = $conexion->prepare($sql_direccion);
    $stmt_direccion->bind_param("sssssssi", $estado, $ciudad, $colonia, $calle, $numero, $numeroOpcional, $codigoPostal, $id_usuario);
} else {
    $sql_direccion = "INSERT INTO Direcciones 
        (UsuarioID, Estado, Ciudad, Colonia, Calle, Numero, NumeroOpcional, CodigoPostal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_direccion = $conexion->prepare($sql_direccion);
    $stmt_direccion->bind_param("isssssss", $id_usuario, $estado, $ciudad, $colonia, $calle, $numero, $numeroOpcional, $codigoPostal);
}

$stmt_direccion->execute();
$stmt_direccion->close();

// Redirigir con mensaje
header("Location: Gestion de Usuario.php?actualizado=1");
exit;
