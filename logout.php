<?php
// Logout: destruir la sesión
session_start();
session_destroy();

// Redirigir al login
header("Location: index.php");
exit;
?>
