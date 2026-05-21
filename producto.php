<?php


require 'auth_check.php';

// gestionar carrito de adopción en sesión
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// determinar si la sesión pertenece a admin
$isAdmin = (isset($_SESSION['usuario']) && $_SESSION['usuario'] === 'admin');

// unificar el manejo de POST: carrito y, si es admin, operaciones extra
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // carrito normal disponible para cualquier usuario
    if (isset($_POST['add_cart'])) {
        $pid   = intval($_POST['add_cart']);
        $pname = $_POST['poke_name'] ?? '';
        $pimg  = $_POST['poke_img'] ?? '';
        // evitar duplicados
        $already = false;
        foreach ($_SESSION['cart'] as $item) {
            if ($item['id'] === $pid) {
                $already = true;
                break;
            }
        }
        if (!$already && $pid > 0) {
            $_SESSION['cart'][] = ['id'=>$pid,'nombre'=>$pname,'imagen'=>$pimg];
        }
    } elseif (isset($_POST['remove_cart'])) {
        $rid = intval($_POST['remove_cart']);
        foreach ($_SESSION['cart'] as $idx => $item) {
            if ($item['id'] === $rid) {
                unset($_SESSION['cart'][$idx]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    // resto de post (admin) será procesado después de conectar y de que $isAdmin esté definido
}


// Conexión a la base de datos usando variables de entorno
require __DIR__ . '/db.php';
$conn = $GLOBALS['db_connection'];

// si existían tablas anteriores con estructura distinta, eliminarlas para recrearlas
// NOTE: removido para evitar perder datos; la base se inicializa sólo si no existe
// (si se necesita resetear en desarrollo, hacerlo manualmente o con un script separado)
//
//

// asegurarse de que exista la tabla de tipos + pokémon (con fk)
$conn->query("
CREATE TABLE IF NOT EXISTS tipo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB;");

// recrear pokemon si no existe (tipo ahora va en tabla intermedia)
$conn->query("
CREATE TABLE IF NOT EXISTS pokemon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;");

// tabla de relación muchos-a-muchos
$conn->query("
CREATE TABLE IF NOT EXISTS pokemon_tipo (
    pokemon_id INT NOT NULL,
    tipo_id INT NOT NULL,
    PRIMARY KEY(pokemon_id,tipo_id),
    FOREIGN KEY (pokemon_id) REFERENCES pokemon(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_id) REFERENCES tipo(id) ON DELETE CASCADE
) ENGINE=InnoDB;");

// insertar tipos base si no existen
$tiposBase = ['Fuego','Agua','Planta','Eléctrico','Hielo','Psíquico','Normal','Roca','Volador','Tierra','Hada','Fantasma','Veneno','Acero','Dragón','Bicho'];
$insTipo = $conn->prepare("INSERT IGNORE INTO tipo (nombre) VALUES (?)");
foreach ($tiposBase as $t) {
    $insTipo->bind_param("s", $t);
    $insTipo->execute();
}
$insTipo->close();

// procesar acciones de administrador (sólo cuando logueado como admin)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['admin_add_pokemon'])) {
        // insertar pokémon nuevo en base de datos
        $newName = trim($_POST['poke_nombre'] ?? '');
        $newImg  = trim($_POST['poke_imagen'] ?? '');
        $newTipos = trim($_POST['poke_tipos'] ?? ''); // coma separados
        if ($newName !== '') {
            $stmtIns = $conn->prepare("INSERT INTO pokemon (nombre,imagen) VALUES (?,?)");
            $stmtIns->bind_param("ss", $newName, $newImg);
            $stmtIns->execute();
            $pid = $stmtIns->insert_id;
            $stmtIns->close();
            // manejar tipos (crear si es necesario)
            $tipoList = array_filter(array_map('trim', explode(',', $newTipos)));
            foreach ($tipoList as $tn) {
                if ($tn === '') continue;
                // insertar tipo si no existe
                $stmtTipo = $conn->prepare("INSERT IGNORE INTO tipo (nombre) VALUES (?)");
                $stmtTipo->bind_param("s", $tn);
                $stmtTipo->execute();
                $stmtTipo->close();
                // obtener id del tipo
                $resTipo = $conn->prepare("SELECT id FROM tipo WHERE nombre = ? LIMIT 1");
                $resTipo->bind_param("s", $tn);
                $resTipo->execute();
                $resTipo->bind_result($tid);
                if ($resTipo->fetch()) {
                    // cerrar el statement de selección antes de ejecutar otra consulta
                    $resTipo->close();
                    $rel = $conn->prepare("INSERT IGNORE INTO pokemon_tipo (pokemon_id,tipo_id) VALUES (?,?)");
                    $rel->bind_param("ii", $pid, $tid);
                    $rel->execute();
                    $rel->close();
                } else {
                    $resTipo->close();
                }
            }
        }
    } elseif (isset($_POST['admin_update_pokemon'])) {
        $updId = intval($_POST['admin_update_pokemon']);
        $updName = trim($_POST['poke_nombre'] ?? '');
        $updImg  = trim($_POST['poke_imagen'] ?? '');
        $updTipos = trim($_POST['poke_tipos'] ?? '');
        if ($updId > 0 && $updName !== '') {
            $ustmt = $conn->prepare("UPDATE pokemon SET nombre=?, imagen=? WHERE id=?");
            $ustmt->bind_param("ssi", $updName, $updImg, $updId);
            $ustmt->execute();
            $ustmt->close();
            // borrar relaciones anteriores
            $delRel = $conn->prepare("DELETE FROM pokemon_tipo WHERE pokemon_id=?");
            $delRel->bind_param("i", $updId);
            $delRel->execute();
            $delRel->close();
            // volver a insertar tipos
            $tipoList = array_filter(array_map('trim', explode(',', $updTipos)));
            foreach ($tipoList as $tn) {
                if ($tn === '') continue;
                $stmtTipo = $conn->prepare("INSERT IGNORE INTO tipo (nombre) VALUES (?)");
                $stmtTipo->bind_param("s", $tn);
                $stmtTipo->execute();
                $stmtTipo->close();
                $resTipo = $conn->prepare("SELECT id FROM tipo WHERE nombre = ? LIMIT 1");
                $resTipo->bind_param("s", $tn);
                $resTipo->execute();
                $resTipo->bind_result($tid);
                if ($resTipo->fetch()) {
                    $resTipo->close();
                    $rel = $conn->prepare("INSERT IGNORE INTO pokemon_tipo (pokemon_id,tipo_id) VALUES (?,?)");
                    $rel->bind_param("ii", $updId, $tid);
                    $rel->execute();
                    $rel->close();
                } else {
                    $resTipo->close();
                }
            }
        }
    } elseif (isset($_POST['admin_delete_pokemon'])) {
        $delId = intval($_POST['admin_delete_pokemon']);
        if ($delId > 0) {
            $dstmt = $conn->prepare("DELETE FROM pokemon WHERE id = ?");
            $dstmt->bind_param("i", $delId);
            $dstmt->execute();
            $dstmt->close();
        }
    }
}

// después de cualquier POST, hacemos un redirect para evitar repostear datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: producto.php' . (isset($_GET['type']) ? '?type='.urlencode($_GET['type']) : ''));
    exit;
}

// luego comprobar si tabla pokemon está vacía
$countRes = $conn->query("SELECT COUNT(*) AS cnt FROM pokemon");
if ($countRes) {
    $cnt = $countRes->fetch_assoc()['cnt'];
    if ($cnt == 0) {
        // recolectar ids de tipos
        $tipoIds = [];
        $res = $conn->query("SELECT id,nombre FROM tipo");
        while ($row = $res->fetch_assoc()) {
            $tipoIds[$row['nombre']] = $row['id'];
        }
        // cada sample puede tener varios tipos
        $samples = [
            ['nombre'=>'Bulbasaur','tipos'=>['Planta'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/1.png'],
            ['nombre'=>'Ivysaur','tipos'=>['Planta'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/2.png'],
            ['nombre'=>'Venusaur','tipos'=>['Planta','Veneno'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/3.png'],
            ['nombre'=>'Charmander','tipos'=>['Fuego'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/4.png'],
            ['nombre'=>'Charmeleon','tipos'=>['Fuego'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/5.png'],
            ['nombre'=>'Charizard','tipos'=>['Fuego','Volador'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/6.png'],
            ['nombre'=>'Squirtle','tipos'=>['Agua'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/7.png'],
            ['nombre'=>'Wartortle','tipos'=>['Agua'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/8.png'],
            ['nombre'=>'Blastoise','tipos'=>['Agua'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/9.png'],
            ['nombre'=>'Caterpie','tipos'=>['Bicho'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/10.png'],
            ['nombre'=>'Butterfree','tipos'=>['Bicho','Volador'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/12.png'],
            ['nombre'=>'Weedle','tipos'=>['Bicho','Veneno'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/13.png'],
            ['nombre'=>'Pidgey','tipos'=>['Normal','Volador'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/16.png'],
            ['nombre'=>'Pidgeotto','tipos'=>['Normal','Volador'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/17.png'],
            ['nombre'=>'Pidgeot','tipos'=>['Normal','Volador'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/18.png'],
            ['nombre'=>'Pikachu','tipos'=>['Eléctrico'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png'],
            ['nombre'=>'Jigglypuff','tipos'=>['Normal','Hada'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/39.png'],
            ['nombre'=>'Gengar','tipos'=>['Fantasma','Veneno'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/94.png'],
            ['nombre'=>'Onix','tipos'=>['Roca','Tierra'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/95.png'],
            ['nombre'=>'Scyther','tipos'=>['Bicho','Volador'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/123.png'],
            ['nombre'=>'Mew','tipos'=>['Psíquico'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/151.png'],
            ['nombre'=>'Lapras','tipos'=>['Agua','Hielo'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/131.png'],
            ['nombre'=>'Snorlax','tipos'=>['Normal'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/143.png'],
            ['nombre'=>'Vaporeon','tipos'=>['Agua'],'imagen'=>'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/134.png'],
        ];
        $insMonster = $conn->prepare("INSERT INTO pokemon (nombre,imagen) VALUES (?,?)");
        $insRel = $conn->prepare("INSERT INTO pokemon_tipo (pokemon_id,tipo_id) VALUES (?,?)");
        foreach ($samples as $s) {
            $insMonster->bind_param("ss", $s['nombre'], $s['imagen']);
            $insMonster->execute();
            $pid = $insMonster->insert_id;
            foreach ($s['tipos'] as $tn) {
                if (!isset($tipoIds[$tn])) continue;
                $tid = $tipoIds[$tn];
                $insRel->bind_param("ii", $pid, $tid);
                $insRel->execute();
            }
        }
        $insMonster->close();
        $insRel->close();
    }
}


$tipo = $_GET['type'] ?? '';
if ($tipo !== '') {
    // filtro pero queremos todos los tipos del pokémon
    $stmt = $conn->prepare("SELECT p.id,p.nombre,p.imagen,
            GROUP_CONCAT(t2.nombre SEPARATOR ', ') AS tipos
        FROM pokemon p
        JOIN pokemon_tipo pt2 ON pt2.pokemon_id = p.id
        JOIN tipo t2 ON t2.id = pt2.tipo_id
        WHERE p.id IN (
            SELECT pt.pokemon_id
            FROM pokemon_tipo pt
            JOIN tipo t ON t.id = pt.tipo_id
            WHERE t.nombre = ?
        )
        GROUP BY p.id
        LIMIT 6");
    $stmt->bind_param("s", $tipo);
} else {
    // sin filtro, mostrar todos los pokémon con sus tipos
    $stmt = $conn->prepare("SELECT p.id,p.nombre,p.imagen,
            GROUP_CONCAT(t.nombre SEPARATOR ', ') AS tipos
        FROM pokemon p
        JOIN pokemon_tipo pt ON pt.pokemon_id = p.id
        JOIN tipo t ON t.id = pt.tipo_id
        GROUP BY p.id");
}
$stmt->execute();
$result = $stmt->get_result();
$slots = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// debug: cuantos slots recuperados?
error_log("[producto.php] slots count=" . count($slots));


// tipos disponibles (cargo de la base)
$tipos = [];
$res2 = $conn->query("SELECT nombre FROM tipo");
while ($r = $res2->fetch_assoc()) {
    $tipos[] = $r['nombre'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Producto · PokéPet</title>
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
      main > h1 {
        font-size: clamp(1.8rem, 4vw, 2.5rem);
        font-weight: 700;
        color: #333;
        margin: 2rem 0 1.5rem;
        text-align: center;
      }
      
      /* GRID */
      .row-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 160px), 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
      }
      @media (min-width: 640px) {
        .row-grid {
          grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
      }
      @media (min-width: 768px) {
        .row-grid {
          grid-template-columns: repeat(4, 1fr);
          gap: 1.5rem;
        }
      }
      @media (min-width: 1024px) {
        .row-grid {
          grid-template-columns: repeat(5, 1fr);
        }
      }
      
      /* CARDS */
      .card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        background: white;
        height: 100%;
      }
      .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
      }
      
      .card-img-top {
        object-fit: contain;
        height: 140px;
        background: #f0f0f0;
        padding: 0.75rem;
      }
      @media (min-width: 768px) {
        .card-img-top {
          height: 160px;
          padding: 1rem;
        }
      }
      
      .card-body {
        padding: 0.75rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
      }
      
      .card-title {
        font-weight: 600;
        color: #333;
        font-size: clamp(0.85rem, 2vw, 1rem);
        margin-bottom: 0.5rem;
        flex-grow: 1;
        display: flex;
        align-items: center;
      }
      
      .card-text {
        color: #666;
        font-size: clamp(0.75rem, 1.5vw, 0.85rem);
        margin-bottom: 0.5rem;
      }
      
      .btn {
        width: 100%;
        border-radius: 8px;
        font-weight: 600;
        padding: 0.4rem 0.75rem;
        transition: all 0.3s ease;
        border: none;
        font-size: clamp(0.75rem, 1.5vw, 0.85rem);
      }
      
      .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
      }
      .btn-primary:hover {
        background: linear-gradient(135deg, #5568d3 0%, #6b3fa0 100%);
        transform: scale(1.02);
      }
      
      .btn-secondary {
        background: linear-gradient(135deg, #a8a8a8 0%, #7a7a7a 100%);
        color: white;
      }
      .btn-secondary:hover {
        background: linear-gradient(135deg, #909090 0%, #626262 100%);
      }
      
      .btn-sm {
        padding: 0.3rem 0.6rem;
        font-size: 0.75rem;
      }
      
      /* FILTERS */
      .filter-section {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        justify-content: center;
      }
      .filter-btn {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        background: white;
        border: 2px solid #667eea;
        color: #667eea;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
      }
      .filter-btn:hover,
      .filter-btn.active {
        background: #667eea;
        color: white;
      }
      
      /* RESPONSIVE */
      @media (max-width: 640px) {
        .container { padding: 0.75rem; }
        main > h1 { margin: 1.5rem 0 1rem; }
        .card-img-top { height: 120px; }
        .card-body { padding: 0.5rem; }
      }
      
      @media (max-width: 480px) {
        .container { padding: 0.5rem; }
        .site-header nav {
          padding: 0.5rem 0.5rem;
          gap: 0.35rem;
        }
        main > h1 {
          font-size: 1.5rem;
          margin: 1rem 0 0.75rem;
        }
        .row-grid {
          grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
          gap: 0.75rem;
        }
        .card-img-top { height: 100px; }
        .card-body { padding: 0.4rem; }
        .card {
          border-radius: 8px;
        }
        .filter-section {
          gap: 0.5rem;
          margin-bottom: 1.5rem;
        }
        .filter-btn {
          padding: 0.35rem 0.8rem;
          font-size: 0.75rem;
        }
      }
    </style>
</head>
<body>
<header class="site-header sticky-top">
  <nav class="d-flex flex-md-row justify-content-between align-items-center flex-wrap">
    <a class="py-2 ps-3" href="home.php" aria-label="Product">
      <img src="images/pokepetlogo.png" width="28" height="28" alt="Logo">
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

<div class="container py-4">
    <h1 class="mb-4">Selección de Pokémon</h1>

    <?php if ($isAdmin): ?>
        <section class="mb-4 p-3 border rounded bg-light">
            <h2 class="h5">Panel de administrador</h2>
            <form method="post" class="row g-2">
                <input type="hidden" name="admin_add_pokemon" value="1">
                <div class="col-md-4">
                    <input type="text" name="poke_nombre" class="form-control" placeholder="Nombre" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="poke_imagen" class="form-control" placeholder="URL imagen">
                </div>
                <div class="col-md-4">
                    <input type="text" name="poke_tipos" class="form-control" placeholder="Tipos (separados por coma)">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-success">Añadir pokémon</button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <div class="filter-section">
        <form method="GET" class="w-100">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                <a href="producto.php" class="filter-btn <?= $tipo === '' ? 'active' : '' ?>">🎯 Todos</a>
                <?php foreach ($tipos as $t): ?>
                    <a href="producto.php?type=<?=urlencode($t)?>" class="filter-btn <?= $tipo === $t ? 'active' : '' ?>">
                        <?=htmlspecialchars($t)?>
                    </a>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
    
    <div class="row-grid">
        <?php foreach ($slots as $p): ?>
            <div>
                <div class="card">
                    <?php if (!empty($p['imagen'])): ?>
                        <img src="<?=htmlspecialchars($p['imagen'])?>" class="card-img-top" alt="<?=htmlspecialchars($p['nombre'])?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?=htmlspecialchars($p['nombre'])?></h5>
                        <p class="card-text">📌 <?=htmlspecialchars($p['tipos'] ?? '')?></p>
                        <?php if ($isAdmin): ?>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; width: 100%;">
                                <form method="post" style="flex: 1; min-width: 80px; margin-bottom: 0.5rem;">
                                    <input type="hidden" name="add_cart" value="<?=htmlspecialchars($p['id'])?>">
                                    <input type="hidden" name="poke_name" value="<?=htmlspecialchars($p['nombre'])?>">
                                    <input type="hidden" name="poke_img" value="<?=htmlspecialchars($p['imagen'])?>">
                                    <button type="submit" class="btn btn-primary">✨ Añadir</button>
                                </form>
                                <form method="post" style="flex: 1; min-width: 80px;">
                                    <input type="hidden" name="admin_delete_pokemon" value="<?=htmlspecialchars($p['id'])?>">
                                    <button type="submit" class="btn btn-secondary">🗑️ Borrar</button>
                                </form>
                                <button class="btn btn-secondary" style="flex: 1; min-width: 80px;" type="button" data-bs-toggle="collapse" data-bs-target="#edit-<?=htmlspecialchars($p['id'])?>">✏️ Editar</button>
                            </div>
                            <div class="collapse mt-1.5" id="edit-<?=htmlspecialchars($p['id'])?>" style="background: #f0f0f0; padding: 0.75rem; border-radius: 8px; margin-top: 0.75rem;">
                                <form method="post">
                                    <input type="hidden" name="admin_update_pokemon" value="<?=htmlspecialchars($p['id'])?>">
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <input type="text" name="poke_nombre" class="form-control" value="<?=htmlspecialchars($p['nombre'])?>" required style="font-size: 0.85rem; padding: 0.4rem;">
                                        <input type="text" name="poke_imagen" class="form-control" value="<?=htmlspecialchars($p['imagen'])?>" placeholder="URL de imagen" style="font-size: 0.85rem; padding: 0.4rem;">
                                        <input type="text" name="poke_tipos" class="form-control" value="<?=htmlspecialchars($p['tipos'] ?? '')?>" placeholder="Tipos (comas)" style="font-size: 0.85rem; padding: 0.4rem;">
                                        <button type="submit" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.4rem;">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="add_cart" value="<?=htmlspecialchars($p['id'])?>">
                                <input type="hidden" name="poke_name" value="<?=htmlspecialchars($p['nombre'])?>">
                                <input type="hidden" name="poke_img" value="<?=htmlspecialchars($p['imagen'])?>">
                                <button type="submit" class="btn btn-primary">💝 Adoptar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>