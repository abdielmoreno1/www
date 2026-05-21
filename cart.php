<?php
require 'auth_check.php';
require __DIR__ . '/db.php';

$conn = $GLOBALS['db_connection'];

// iniciar carrito
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// procesar acciones (eliminar individual, vaciar o adoptar)
$msg = '';
$msgType = '';
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
        if (!empty($_SESSION['cart'])) {
            // guardar adopción en base de datos
            $usuario = $_SESSION['usuario'] ?? 'Usuario Anónimo';
            $pokemonList = [];
            foreach ($_SESSION['cart'] as $item) {
                $pokemonList[] = htmlspecialchars($item['nombre']);
            }
            $pokemonJson = json_encode($pokemonList);
            
            $stmt = $conn->prepare("INSERT INTO adopciones (usuario, pokemon_adoptados) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $usuario, $pokemonJson);
                if ($stmt->execute()) {
                    $msg = '¡Felicidades! Tus Pokémon han sido adoptados con éxito. ¡Gracias por encontrarles un hogar! 💕';
                    $msgType = 'success';
                } else {
                    $msg = 'Error al guardar la adopción.';
                    $msgType = 'warning';
                }
                $stmt->close();
            }
        } else {
            $msg = 'Tu carrito está vacío.';
            $msgType = 'info';
        }
        $_SESSION['cart'] = [];
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
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      margin: 0; 
      padding: 0;
      background: #f8f9fa;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    .container { 
      padding: 1rem;
      max-width: 1200px;
      margin: 0 auto;
    }
    @media (min-width: 768px) {
      .container { padding: 2rem 1.5rem; }
    }
    
    /* HEADER */
    .site-header {
      background: linear-gradient(135deg, #667eea 0%, #d8a23e 100%);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      z-index: 1000;
      position: sticky;
      top: 0;
    }
    .site-header nav {
      padding: 0.75rem 1rem;
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
      justify-content: space-between;
    }
    @media (max-width: 480px) {
      .site-header nav {
        padding: 0.5rem 0.75rem;
        gap: 0.5rem;
      }
    }
    .site-header a {
      color: white;
      text-decoration: none;
      transition: opacity 0.3s ease;
      font-size: clamp(0.85rem, 2vw, 1rem);
    }
    .site-header a:hover {
      opacity: 0.7;
    }
    .site-header .nav-links {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .site-header .nav-links a {
      font-weight: 500;
    }
    .site-header .text-muted {
      color: rgba(255,255,255,0.8) !important;
    }
    .site-header img {
      width: clamp(24px, 5vw, 32px);
      height: auto;
    }
    
    /* MAIN */
    main h1 {
      font-size: clamp(1.8rem, 4vw, 2.5rem);
      font-weight: 700;
      color: #333;
      margin-bottom: 2rem;
      text-align: center;
    }
    
    /* ALERT */
    .alert {
      border-radius: 12px;
      border: none;
      padding: 1.25rem 1.5rem;
      margin-bottom: 2rem;
      animation: slideDown 0.4s ease-out;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .alert-success {
      background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
      color: #1a4d2e;
      border-left: 4px solid #2d6a4f;
    }
    .alert-info {
      background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
      color: #0c4a70;
    }
    .alert-warning {
      background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
      color: #6b3410;
    }
    
    /* CARDS */
    .row {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(min(100%, 200px), 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
      .row {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      }
    }
    @media (min-width: 1024px) {
      .row {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      }
    }
    
    .card {
      border: none;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      background: white;
    }
    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    
    .card-img-top {
      object-fit: contain;
      height: 180px;
      background: #f0f0f0;
      padding: 1rem;
    }
    @media (max-width: 480px) {
      .card-img-top {
        height: 140px;
        padding: 0.75rem;
      }
    }
    
    .card-body {
      padding: 1.25rem 1rem;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    
    .card-title {
      font-weight: 600;
      color: #333;
      font-size: clamp(1rem, 2.5vw, 1.25rem);
      margin-bottom: 1rem;
      flex-grow: 1;
    }
    
    .btn {
      width: 100%;
      border-radius: 8px;
      font-weight: 600;
      padding: 0.5rem 1rem;
      transition: all 0.3s ease;
      border: none;
      font-size: 0.9rem;
    }
    
    .btn-danger {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
      color: white;
    }
    .btn-danger:hover {
      background: linear-gradient(135deg, #ff5252 0%, #e63946 100%);
      transform: scale(0.98);
    }
    
    .btn-warning {
      background: linear-gradient(135deg, #ffa502 0%, #ff8d3e 100%);
      color: white;
      margin-right: 0.5rem;
    }
    .btn-warning:hover {
      background: linear-gradient(135deg, #ff9400 0%, #ff6b00 100%);
      transform: scale(0.98);
    }
    
    .btn-success {
      background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
      color: #1a4d2e;
      font-weight: 700;
      flex-grow: 1;
      min-width: 150px;
    }
    .btn-success:hover {
      background: linear-gradient(135deg, #6ee7a0 0%, #72c9e8 100%);
      transform: scale(1.02);
      box-shadow: 0 4px 12px rgba(132, 250, 176, 0.4);
    }
    
    /* BUTTONS CONTAINER */
    .d-flex {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .mt-4 {
      margin-top: 2rem;
    }
    
    /* EMPTY STATE */
    p {
      text-align: center;
      color: #666;
      font-size: 1.1rem;
      padding: 2rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    p a {
      color: #667eea;
      font-weight: 600;
      text-decoration: none;
    }
    p a:hover {
      text-decoration: underline;
    }
    
    /* RESPONSIVE */
    @media (max-width: 640px) {
      main h1 {
        margin-bottom: 1.5rem;
      }
      .alert {
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
      }
      .d-flex {
        gap: 0.75rem;
      }
      .btn {
        padding: 0.6rem 0.8rem;
        font-size: 0.85rem;
      }
      .btn-success {
        min-width: auto;
      }
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
  <h1>🎁 Lista de Adopción</h1>
  <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType === 'success' ? 'success' : ($msgType === 'warning' ? 'warning' : 'info'); ?>" role="alert">
      <?=htmlspecialchars($msg)?>
    </div>
  <?php endif; ?>
  <?php if (empty($_SESSION['cart'])): ?>
    <p>📭 No hay Pokémon en tu lista. Visita <a href="producto.php">Pokemones</a> para añadir algunos.</p>
  <?php else: ?>
    <div class="row">
      <?php foreach ($_SESSION['cart'] as $item): ?>
        <div class="col-12">
          <div class="card">
            <?php if (!empty($item['imagen'])): ?>
              <img src="<?=htmlspecialchars($item['imagen'])?>" class="card-img-top" alt="<?=htmlspecialchars($item['nombre'])?>">
            <?php endif; ?>
            <div class="card-body">
              <h5 class="card-title"><?=htmlspecialchars($item['nombre'])?></h5>
              <form method="post" style="width: 100%;">
                <input type="hidden" name="remove_cart" value="<?=htmlspecialchars($item['id'])?>">
                <button class="btn btn-danger">🗑️ Eliminar</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="d-flex mt-4" style="justify-content: flex-end; flex-wrap: wrap-reverse;">
      <form method="post">
        <button name="adopt_cart" class="btn btn-success">✨ Confirmar Adopción</button>
      </form>
      <form method="post">
        <button name="clear_cart" class="btn btn-warning">🔄 Vaciar lista</button>
      </form>
    </div>
  <?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
