<?php
// Verificar que el usuario esté autenticado
require 'auth_check.php';

// asegurarnos de tener el carrito en sesión
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// procesar eventos de carrito (solo eliminar desde el dropdown)
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
        // redirigir para evitar reenvío de formulario
        header('Location: home.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PokéPet - Tu aplicación de Pokémon favorita">
    <meta name="author" content="Abdiel Moreno Ayvar">
    <title>PokéPet - Captura, Entrena y Evoluciona</title>

    <!-- Bootstrap core CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

    <!-- Favicons -->
<link rel="apple-touch-icon" href="/docs/5.0/assets/img/favicons/apple-touch-icon.png" sizes="180x180">
<link rel="icon" href="/docs/5.0/assets/img/favicons/favicon-32x32.png" sizes="32x32" type="image/png">
<link rel="icon" href="/docs/5.0/assets/img/favicons/favicon-16x16.png" sizes="16x16" type="image/png">
<link rel="manifest" href="/docs/5.0/assets/img/favicons/manifest.json">
<link rel="mask-icon" href="/docs/5.0/assets/img/favicons/safari-pinned-tab.svg" color="#da1616">
<link rel="icon" href="/docs/5.0/assets/img/favicons/favicon.ico">
<meta name="theme-color" content="#b35252">

    <style>

      .hero-section {
        position: relative;
        overflow: hidden;
        min-height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 1.5rem;
        color: white;
      }
      /* Video full-bleed */
      .hero-video {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: translate(-50%, -50%);
        z-index: 0;
        filter: brightness(0.55) saturate(1.05);
      }
      /* Overlay content centered */
      .hero-overlay {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 900px;
        padding: 1rem 1rem;
      }

      body {
        margin: 0;
        padding: 0;
      }
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
      .hero-section {
        background: linear-gradient(135deg,  #667eea 100%);
        color: white;
        padding: 3rem 1.5rem;
        text-align: center;
      }
      .hero-section h1 {
        font-size: clamp(2rem, 5vw, 3.5rem);
        font-weight: 700;
        margin-bottom: 1rem;
      }
      .hero-section p {
        font-size: clamp(1rem, 3vw, 1.25rem);
        margin-bottom: 2rem;
        opacity: 0.95;
      }
      .card-section {
        padding: 2rem 1.5rem;
      }
      .card-item {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        height: 100%;
      }
      .card-content {
        padding: 2rem 1.5rem;
        text-align: center;
      }
      .card-image {
        width: 100%;
        aspect-ratio: 4/3;
        border-radius: 12px 12px 0 0;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f0f0f0;
      }
      .card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .card-content h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #333;
      }
      .card-content p {
        color: #666;
        line-height: 1.6;
      }
      footer {
        background-color: #2d3436;
        color: white;
        padding: 3rem 1.5rem 1.5rem;
      }
      footer h5 {
        font-weight: 600;
        margin-bottom: 1rem;
      }
      footer a {
        color: #a9a9a9;
        text-decoration: none;
        transition: color 0.3s ease;
      }
      footer a:hover {
        color: white;
      }
      @media (max-width: 768px) {
        .site-header nav {
          flex-wrap: wrap;
          gap: 0.75rem;
          padding: 0.75rem 1rem;
        }
        .nav-links {
          order: 3;
          flex-basis: 100%;
          display: flex;
          gap: 1rem;
          flex-wrap: wrap;
          font-size: 0.9rem;
        }
        .card-image {
          aspect-ratio: 16/9;
          background-attachment: scroll;
        }
        .hero-section {
          min-height: 50vh;
          padding: 2rem 1rem;
        }
        .card-section {
          padding: 1.5rem 1rem;
        }
        .card-content {
          padding: 1.5rem 1rem;
        }
        .card-content h3 {
          font-size: 1.25rem;
        }
        footer {
          padding: 2rem 1rem 1rem;
        }
      }
      
      @media (max-width: 480px) {
        .site-header nav {
          padding: 0.5rem 0.75rem;
          gap: 0.5rem;
        }
        .site-header a {
          font-size: 0.8rem;
        }
        .site-header .text-muted {
          display: none;
        }
        .nav-links {
          gap: 0.75rem;
          font-size: 0.85rem;
        }
        .hero-section {
          min-height: 40vh;
          padding: 1.5rem 0.75rem;
        }
        .hero-section h1 {
          font-size: 1.8rem;
        }
        .hero-section p {
          font-size: 1rem;
          margin-bottom: 1.5rem;
        }
        .card-section {
          padding: 1rem 0.75rem;
        }
        .card-content {
          padding: 1rem 0.75rem;
        }
        .card-content h3 {
          font-size: 1.15rem;
          margin-bottom: 0.75rem;
        }
        .card-content p {
          font-size: 0.95rem;
          line-height: 1.5;
        }
        footer {
          padding: 1.5rem 0.75rem 0.75rem;
        }
        footer h5 {
          font-size: 1rem;
          margin-bottom: 0.75rem;
        }
        footer small {
          font-size: 0.8rem;
        }
        .list-unstyled li {
          margin-bottom: 0.5rem;
        }
        .list-unstyled a {
          font-size: 0.85rem;
        }
      }
    </style>

  </head>
  <body>
    
<header class="site-header sticky-top">
  <nav class="d-flex flex-md-row justify-content-between align-items-center flex-wrap">
    <a class="py-2 ps-3" href="home.php" aria-label="Product">
      <img src="images/pokepetlogo.png" width="28" height="28" fill="white" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" role="img" viewBox="0 0 24 24"><title>Home</title><circle cx="12" cy="12" r="10"/><path d="M14.31 8l5.74 9.94M9.69 8h11.48M7.38 12l5.74-9.94M9.69 16L3.95 6.06M14.31 16H2.83m13.79-4l-5.74 9.94"/>
    </a>
    <div class="nav-links d-flex gap-2">
      <a href="home.php">Home</a>
      <a href="producto.php">Pokemones</a>
    </div>
    <!-- carrito dropdown -->
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
          <li><a class="dropdown-item text-center text-dark" href="cart.php">Ver lista completa</a></li>
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

<main>
  <div class="hero-section">
      <!-- video fuente: coloca tu archivo en images/hero.mp4 o ajusta la ruta -->
      <video class="hero-video" autoplay muted loop playsinline poster="images/hero-poster.jpg">
        <source src="images/videoplayback.mp4" type="video/mp4">
        <!-- opcional: webm fallback -->
        <source src="images/videoplayback.webm" type="video/webm">
        Tu navegador no soporta video en background.
      </video>

      <div class="hero-overlay">
        <h1>Bienvenido a PokéPet</h1>
        <p>Descubre el mundo increíble de tu Pokémon mascota y construye una aventura única</p>
        
      </div>
  </div>

  <div class="card-section">
    <div style="max-width: 1200px; margin: 0 auto;">
      <h2 style="text-align: center; margin-bottom: 3rem; font-size: clamp(1.8rem, 4vw, 2.5rem); font-weight: 700; color: #333;">Características destacadas</h2>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
        <div class="card-item">
          <div class="card-image"><img src="images/catch.jpg" alt="Captura y Entrena"></div>
          <div class="card-content">
            <h3>Captura y Entrena</h3>
            <p>Captura Pokémon únicos y entrena tu equipo para las batallas más intensas.</p>
          </div>
        </div>
        
        <div class="card-item">
          <div class="card-image"><img src="images/evolution.avif" alt="Evolución Avanzada"></div>
          <div class="card-content">
            <h3>Evolución Avanzada</h3>
            <p>Descubre nuevas evolucionesy formas especiales para tus Pokémon favoritos.</p>
          </div>
        </div>
        
        <div class="card-item">
          <div class="card-image"><img src="images/battles.jpg" alt="Batallas en Tiempo Real"></div>
          <div class="card-content">
            <h3>Batallas en Tiempo Real</h3>
            <p>Enféntate contra otros entrenadores en competiciones en vivo y gana recompensas.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card-section" style="background-color: #f8f9fa;">
    <div style="max-width: 1200px; margin: 0 auto;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
        <div class="card-item">
          <div class="card-image"><img src="images/gyms.avif" alt="Gimnasios"></div>
          <div class="card-content">
            <h3>Gimnasios</h3>
            <p>Mide tus fuerzas contra los líderes de gimnasio en aventuras legendarias.</p>
          </div>
        </div>
        
        <div class="card-item">
          <div class="card-image"><img src="images/pokedex.jpg" alt="Pokédex Completa"></div>
          <div class="card-content">
            <h3>Pokédex Completa</h3>
            <p>Registra información detallada de todos los Pokémon que encuentres y entrenes.</p>
          </div>
        </div>
        
        <div class="card-item">
          <div class="card-image"><img src="images/events.jpg" alt="Eventos Especiales"></div>
          <div class="card-content">
            <h3>Eventos Especiales</h3>
            <p>Participa en eventos semanales y consigue Pokémon exclusivos y limitados.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card-section">
    <div style="max-width: 1200px; margin: 0 auto;">
      <h2 style="text-align: center; margin-bottom: 3rem; font-size: clamp(1.8rem, 4vw, 2.5rem); font-weight: 700; color: #333;">💝 Adopciones Exitosas</h2>
      <p style="text-align: center; color: #666; margin-bottom: 2rem; font-size: clamp(1rem, 2vw, 1.15rem);">Gracias por encontrarles un hogar a nuestros Pokémon</p>
      
      <?php
      require __DIR__ . '/db.php';
      $conn = $GLOBALS['db_connection'];
      $adopciones = $conn->query("SELECT usuario, pokemon_adoptados, fecha FROM adopciones ORDER BY fecha DESC LIMIT 50");
      
      if ($adopciones && $adopciones->num_rows > 0):
      ?>
        <div style="overflow-x: auto; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
          <table style="width: 100%; border-collapse: collapse; background: white;">
            <thead>
              <tr style="background: linear-gradient(135deg, #667eea 0%, #d8a23e 100%); color: white;">
                <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: clamp(0.95rem, 2vw, 1.1rem);">👤 Usuario</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: clamp(0.95rem, 2vw, 1.1rem);">🎁 Pokémon Adoptados</th>
                <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: clamp(0.85rem, 1.5vw, 1rem); display: none;" class="d-md-table-cell">📅 Fecha</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $adopciones->fetch_assoc()): 
                $pokemon = json_decode($row['pokemon_adoptados'], true);
                $pokemon_str = is_array($pokemon) ? implode(', ', array_map('htmlspecialchars', $pokemon)) : htmlspecialchars($row['pokemon_adoptados']);
                $fecha = date('d/m/Y H:i', strtotime($row['fecha']));
              ?>
                <tr style="border-bottom: 1px solid #eee; transition: background 0.2s ease;">
                  <td style="padding: 1rem; font-weight: 500; color: #333; font-size: clamp(0.9rem, 1.8vw, 1rem);">
                    <?=htmlspecialchars($row['usuario'])?>
                  </td>
                  <td style="padding: 1rem; color: #666; font-size: clamp(0.85rem, 1.6vw, 0.95rem);">
                    <?=$pokemon_str?>
                  </td>
                  <td style="padding: 1rem; color: #999; font-size: clamp(0.75rem, 1.3vw, 0.85rem); display: none;" class="d-md-table-cell">
                    <?=$fecha?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p style="text-align: center; color: #999; background: white; border-radius: 12px; padding: 2rem;">
          Aún no hay adopciones. ¡Sé el primero en adoptar un Pokémon! 🚀
        </p>
      <?php endif; ?>
    </div>
  </div>
</main>

<footer>
  <div style="max-width: 1200px; margin: 0 auto;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
      <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" role="img" viewBox="0 0 24 24"><title>Product</title><circle cx="12" cy="12" r="10"/><path d="M14.31 8l5.74 9.94M9.69 8h11.48M7.38 12l5.74-9.94M9.69 16L3.95 6.06M14.31 16H2.83m13.79-4l-5.74 9.94"/></svg>
          <strong>PokéPet</strong>
        </div>
        <small>&copy; 2024 Abdiel Moreno Ayvar. Todos los derechos reservados.</small>
      </div>
      <div>
        <h5>Características</h5>
        <ul class="list-unstyled text-small">
          <li><a href="#">Captura y entrena</a></li>
          <li><a href="#">Evolución</a></li>
          <li><a href="#">Batallas</a></li>
          <li><a href="#">Gimnasios</a></li>
        </ul>
      </div>
      <div>
        <h5>Recursos</h5>
        <ul class="list-unstyled text-small">
          <li><a href="#">Guía para principiantes</a></li>
          <li><a href="#">FAQs</a></li>
          <li><a href="#">Soporte</a></li>
          <li><a href="#">Documentación</a></li>
        </ul>
      </div>
      <div>
        <h5>Sobre nosotros</h5>
        <ul class="list-unstyled text-small">
          <li><a href="#">Equipo</a></li>
          <li><a href="#">Blog</a></li>
          <li><a href="#">Privacidad</a></li>
          <li><a href="#">Términos</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div style="border-top: 1px solid #444; padding-top: 1.5rem; text-align: center;">
    <small>Hecho con ❤️ por desarrolladores apasionados</small>
  </div>
</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>

      
  </body>
</html>