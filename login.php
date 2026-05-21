<?php
// Iniciar sesión para rastrear usuarios autenticados
session_start();

// Solo procesar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

// Incluir configuración de base de datos
require __DIR__ . '/db.php';
$conn = $GLOBALS['db_connection'];

// Recibir datos del formulario (puede ser nombre de usuario o correo)
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Nota: la conexión se estableció arriba con la base de datos 'usuarios'.
// Si necesita verificar que está bien enlazada, puede revisar
// $conn->connect_error o ejecutar una consulta simple.

// Preparar la consulta para evitar SQL Injection
// aquí asumimos que el campo en la tabla se llama USUARIO; si almacena
// correos sustituya por el nombre de la columna correspondiente.
$sql = "SELECT * FROM cuenta WHERE USUARIO = ? AND CONTRASENA = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Usuario correcto, crear sesión y redirigir a home.php
    $_SESSION['usuario'] = $username;
    header("Location: home.php");
    exit;
} else {
    // Usuario o contraseña incorrectos
    echo "<script>alert('Usuario o contraseña incorrectos'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
?>


