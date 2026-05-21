<?php
require 'auth_check.php';

// iniciar carrito
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// procesar acciones (eliminar individual, vaciar o adoptar)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_cart'])) {
        $rid = intval($_POST['remove_cart']);
        foreach ($_SESSION['cart'] as $idx => $item) {
            if ($item['id'] === $rid) {
                unset($_SESSION['cart'][$idx]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    } elseif (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    } elseif (isset($_POST['adopt_cart'])) {
        // simular adopción: vaciar lista y felicitar
        $_SESSION['cart'] = [];
        $msg = '¡Felicidades! Tus Pokémon han sido adoptados con éxito.';
    }
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carrito de Adopción · PokéPet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { margin:0; padding:0; }
    .container { padding:2rem 1.5rem; }
    .card-img-top { object-fit: cover; height:200px; }
    /* same header styling as other pages */
    .site-header {
      background: linear-gradient(135deg, #667eea 0%, #d8a23e 100%);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      z-index: 1000;
    }
    .site-header nav {
      padding: 1rem 1.5rem;
    }
    .site-header a {
      color: white;
      text-decoration: none;
      transition: opacity 0.3s ease;
    }
    .site-header a:hover {
      opacity: 0.7;
    }
    .site-header .nav-links a {
      font-weight: 500;
      font-size: 0.95rem;
    }
    .site-header .text-muted {
      color: rgba(255,255,255,0.8) !important;
    }
  </style>
</head>
<body>
<header class="site-header sticky-top">
  <nav class="d-flex flex-md-row justify-content-between align-items-center flex-wrap">
    <a class="py-2 ps-3" href="home.php" aria-label="Home">
      <img src="images/pokepetlogo.png" width="28" height="28" alt="Logo">
    </a>
    <div class="nav-links d-flex gap-2">
      <a href="home.php">Home</a>
      <a href="producto.php">Producto</a>
      <a href="cart.php" class="fw-bold">Carrito</a>
    </div>
    <!-- carrito dropdown (mirar en home/producto) -->
    <div class="dropdown me-3">
      <a class="nav-link dropdown-toggle text-white" href="#" id="cartDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        Adopciones <span class="badge bg-secondary"><?php echo count($_SESSION['cart'] ?? []); ?></span>
      </a>
      <ul class="dropdown-menu" aria-labelledby="cartDropdown">
        <?php if (!empty($_SESSION['cart'])): ?>
          <?php foreach ($_SESSION['cart'] as $item): ?>
            <li class="dropdown-item d-flex justify-content-between align-items-center">
              <span><?=htmlspecialchars($item['nombre'])?></span>
              <form method="post" class="ms-2 mb-0">
                <input type="hidden" name="remove_cart" value="<?=htmlspecialchars($item['id'])?>">
                <button class="btn btn-sm btn-outline-danger">×</button>
              </form>
            </li>
          <?php endforeach; ?>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-center" href="cart.php">Ver lista completa</a></li>
        <?php else: ?>
          <li><span class="dropdown-item text-muted">(vacío)</span></li>
        <?php endif; ?>
      </ul>
    </div>
    <div class="d-flex gap-2 align-items-center pe-3">
      <span class="py-2 text-muted d-none d-md-inline">Bienvenido</span>
      <strong class="d-md-none">Hola</strong>
      <a href="logout.php" class="btn btn-sm btn-caution">Salir</a>
    </div>
  </nav>
</header>
<main class="container">
  <h1>Lista de Adopción</h1>
  <?php if ($msg): ?>
    <div class="alert alert-success" role="alert"><?=htmlspecialchars($msg)?></div>
  <?php endif; ?>
  <?php if (empty($_SESSION['cart'])): ?>
    <p>No hay Pokémon en tu lista. Visita <a href="producto.php">Producto</a> para añadir algunos.</p>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($_SESSION['cart'] as $item): ?>
        <div class="col-12 col-md-4">
          <div class="card">
            <?php if (!empty($item['imagen'])): ?>
              <img src="<?=htmlspecialchars($item['imagen'])?>" class="card-img-top" alt="<?=htmlspecialchars($item['nombre'])?>">
            <?php endif; ?>
            <div class="card-body">
              <h5 class="card-title"><?=htmlspecialchars($item['nombre'])?></h5>
              <form method="post" class="mt-2">
                <input type="hidden" name="remove_cart" value="<?=htmlspecialchars($item['id'])?>">
                <button class="btn btn-sm btn-danger">Eliminar</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="d-flex gap-2 mt-4">
      <form method="post">
        <button name="clear_cart" class="btn btn-warning">Vaciar lista</button>
      </form>
      <form method="post">
        <button name="adopt_cart" class="btn btn-success">Adoptar</button>
      </form>
    </div>
  <?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
