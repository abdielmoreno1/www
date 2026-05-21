<?php
// Verificar si el usuario está autenticado
// Incluir este archivo al inicio de cualquier página protegida

session_start();

if (!isset($_SESSION['usuario'])) {
    // No hay sesión activa, redirigir al login
    header("Location: index.php");
    exit;
}

// Si llegamos aquí, el usuario está autenticado
// $usuario_actual = $_SESSION['usuario'];
?>
