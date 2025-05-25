<?php
session_start();

header('Content-Type: application/json');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['UsuarioID'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuarioID = $_SESSION['UsuarioID'];

// Verificar datos POST
if (!isset($_POST['ProductoID']) || !isset($_POST['Cantidad'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$productoID = (int) $_POST['ProductoID'];
$cantidad = (int) $_POST['Cantidad'];

if ($cantidad < 1) {
    echo json_encode(['success' => false, 'message' => 'Cantidad inválida']);
    exit;
}

$host = 'localhost';
$db = 'MiPC5';
$user = 'root';      
$pass = '1234';      
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Verificar si el producto ya está en el carrito
    $stmt = $pdo->prepare("SELECT Cantidad FROM Carrito WHERE UsuarioID = ? AND ProductoID = ?");
    $stmt->execute([$usuarioID, $productoID]);

    if ($stmt->rowCount() > 0) {
        // Actualizar cantidad
        $stmtUpdate = $pdo->prepare("UPDATE Carrito SET Cantidad = Cantidad + ?, FechaAgregado = NOW() WHERE UsuarioID = ? AND ProductoID = ?");
        $stmtUpdate->execute([$cantidad, $usuarioID, $productoID]);
    } else {
        // Insertar nuevo producto
        $stmtInsert = $pdo->prepare("INSERT INTO Carrito (UsuarioID, ProductoID, Cantidad) VALUES (?, ?, ?)");
        $stmtInsert->execute([$usuarioID, $productoID, $cantidad]);
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
