<?php
session_start();

// Configuración de la conexión
$host = "localhost";
$dbname = "MiPC5";
$user = "root";    
$pass = "1234";    

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para obtener categorías para el select
function obtenerCategorias($pdo) {
    $stmt = $pdo->query("SELECT CategoriaID, Nombre FROM Categorias ORDER BY Nombre");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$mensaje = "";

// Insertar producto
if (isset($_POST['action']) && $_POST['action'] === 'insertar') {
    $categoriaID = $_POST['categoriaID'] ?? 0;
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    $stock = $_POST['stock'] ?? 0;

    $imagenNombre = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $carpeta = "uploads/";
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }
        $nombreArchivo = basename($_FILES['imagen']['name']);
        $imagenNombre = $carpeta . uniqid() . "_" . $nombreArchivo;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $imagenNombre);
    }

    $sql = "INSERT INTO Productos (CategoriaID, Nombre, Descripcion, Precio, Stock, Imagen) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$categoriaID, $nombre, $descripcion, $precio, $stock, $imagenNombre])) {
        $mensaje = "Producto agregado correctamente.";
    } else {
        $mensaje = "Error al agregar producto.";
    }
}

// Eliminar producto
if (isset($_GET['eliminar'])) {
    $productoID = (int) $_GET['eliminar'];

    // Borrar imagen antes de eliminar producto
    $stmtImg = $pdo->prepare("SELECT Imagen FROM Productos WHERE ProductoID = ?");
    $stmtImg->execute([$productoID]);
    $imagen = $stmtImg->fetchColumn();
    if ($imagen && file_exists($imagen)) {
        unlink($imagen);
    }

    // Eliminar producto
    $sql = "DELETE FROM Productos WHERE ProductoID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productoID]);

    $mensaje = "🗑️ Producto eliminado correctamente.";
}

// Editar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'editar') {
    $productoID = $_POST['productoID'];
    $categoriaID = $_POST['categoriaID'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];

    $imagenNombre = null;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $carpeta = "uploads/";
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }
        $nombreArchivo = basename($_FILES['imagen']['name']);
        $imagenNombre = $carpeta . uniqid() . "_" . $nombreArchivo;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $imagenNombre);

        // Borrar imagen anterior
        $stmtImg = $pdo->prepare("SELECT Imagen FROM Productos WHERE ProductoID = ?");
        $stmtImg->execute([$productoID]);
        $imagenAnterior = $stmtImg->fetchColumn();
        if ($imagenAnterior && file_exists($imagenAnterior)) {
            unlink($imagenAnterior);
        }
    }

    if ($imagenNombre) {
        $sql = "UPDATE Productos SET CategoriaID=?, Nombre=?, Descripcion=?, Precio=?, Stock=?, Imagen=?, FechaModificacion=NOW() WHERE ProductoID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoriaID, $nombre, $descripcion, $precio, $stock, $imagenNombre, $productoID]);
    } else {
        $sql = "UPDATE Productos SET CategoriaID=?, Nombre=?, Descripcion=?, Precio=?, Stock=?, FechaModificacion=NOW() WHERE ProductoID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoriaID, $nombre, $descripcion, $precio, $stock, $productoID]);
    }

    // Obtener el producto actualizado para mostrar en el formulario
    $stmt = $pdo->prepare("SELECT * FROM Productos WHERE ProductoID = ?");
    $stmt->execute([$productoID]);
    $editarProducto = $stmt->fetch(PDO::FETCH_ASSOC);

    $mensaje = "✅ Producto actualizado correctamente.";
}

// Obtener todos los productos para listar
$sql = "SELECT p.ProductoID, p.Nombre, p.Descripcion, p.Precio, p.Stock, p.Imagen, c.Nombre as Categoria 
        FROM Productos p 
        JOIN Categorias c ON p.CategoriaID = c.CategoriaID 
        ORDER BY p.ProductoID DESC";
$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para editar (mostrar formulario con datos), si se pasa ?editar=ID
$editarProducto = null;
if (isset($_GET['editar'])) {
    $productoID = (int) $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM Productos WHERE ProductoID = ?");
    $stmt->execute([$productoID]);
    $editarProducto = $stmt->fetch(PDO::FETCH_ASSOC);
}

$categorias = obtenerCategorias($pdo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Gestión de Productos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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

<div class="container  my-5">
  <div class="row">
    
  <div class="col-lg-6 mb-4 " >
    <div class="card border border-dark text-dark rounded p-4" style="max-height: 400px; overflow-y: auto;">
    <h2>Lista de Productos</h2>

    <input type="text" id="buscadorProductos" class="form-control mb-3" placeholder="Buscar productos...">

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Imagen</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $prod): ?>
            <tr>
                <td><?= $prod['ProductoID'] ?></td>
                <td><?= htmlspecialchars($prod['Nombre']) ?></td>
                <td><?= htmlspecialchars($prod['Categoria']) ?></td>
                <td><?= htmlspecialchars($prod['Descripcion']) ?></td>
                <td>$<?= number_format($prod['Precio'], 2) ?></td>
                <td><?= $prod['Stock'] ?></td>
                <td>
                    <?php if ($prod['Imagen'] && file_exists($prod['Imagen'])): ?>
                        <img src="<?= htmlspecialchars($prod['Imagen']) ?>" alt="Imagen" style="max-width:80px;">
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?editar=<?= $prod['ProductoID'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="?eliminar=<?= $prod['ProductoID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar producto?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

  </div>
    

  <div class="col-lg-6 mb-4 " >
     <div class="card border border-dark text-dark rounded p-4" >
       <h1>Agregar / Editar Producto</h1>

    <?php if (!empty($mensaje)) : ?>
        <p class="alert alert-success"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="mb-4">
        <input type="hidden" name="action" value="<?= $editarProducto ? 'editar' : 'insertar' ?>" />
        <?php if ($editarProducto): ?>
            <input type="hidden" name="productoID" value="<?= $editarProducto['ProductoID'] ?>" />
        <?php endif; ?>

        <div class="mb-3">
            <label for="categoriaID" class="form-label">Categoría</label>
            <select id="categoriaID" name="categoriaID" class="form-select" required>
                <option value="">Seleccione una categoría</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['CategoriaID'] ?>" <?= ($editarProducto && $editarProducto['CategoriaID'] == $cat['CategoriaID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['Nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input id="nombre" name="nombre" type="text" class="form-control" required
                value="<?= $editarProducto ? htmlspecialchars($editarProducto['Nombre']) : '' ?>" />
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea id="descripcion" name="descripcion" class="form-control" required><?= $editarProducto ? htmlspecialchars($editarProducto['Descripcion']) : '' ?></textarea>
        </div>

        <div class="mb-3">
            <label for="precio" class="form-label">Precio</label>
            <input id="precio" name="precio" type="number" step="0.01" class="form-control" required
                value="<?= $editarProducto ? $editarProducto['Precio'] : '' ?>" />
        </div>

        <div class="mb-3">
            <label for="stock" class="form-label">Stock</label>
            <input id="stock" name="stock" type="number" class="form-control" required
                value="<?= $editarProducto ? $editarProducto['Stock'] : '0' ?>" />
        </div>

        <div class="mb-3">
            <label for="imagen" class="form-label">Imagen</label>
            <input id="imagen" name="imagen" type="file" class="form-control" <?= $editarProducto ? '' : 'required' ?> />
            <?php if ($editarProducto && $editarProducto['Imagen']): ?>
                <img src="<?= htmlspecialchars($editarProducto['Imagen']) ?>" alt="Imagen del producto" style="max-width:150px; margin-top:10px;">
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary"><?= $editarProducto ? 'Actualizar Producto' : 'Agregar Producto' ?></button>
        <?php if ($editarProducto): ?>
            <a href="newproducto.php" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>
    </form>
    </div>

  </div>
    

  </div>
   
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('buscadorProductos').addEventListener('input', function() {
    const filtro = this.value.toLowerCase();
    const filas = document.querySelectorAll('table tbody tr');

    filas.forEach(fila => {
      // Busca texto en todas las celdas de la fila (menos acciones y la imagen para mejorar rendimiento)
      const celdas = fila.querySelectorAll('td');
      let textoFila = '';
      for(let i=0; i < celdas.length - 2; i++) { 
        textoFila += celdas[i].textContent.toLowerCase() + ' ';
      }

      if (textoFila.includes(filtro)) {
        fila.style.display = '';
      } else {
        fila.style.display = 'none';
      }
    });
  });
</script>
</body>
</html>

