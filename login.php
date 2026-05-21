<?php
// Iniciar sesión para rastrear usuarios autenticados
session_start();

// Solo procesar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

// Datos de conexión (Laragon, MySQL sin contraseña)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "usuarios";  // tu base de datos

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
} else {
    // conexión exitosa; ejecutar una consulta simple para confirmar el enlace
    $test = $conn->query("SELECT 1");
    if (! $test) {
        die("Error al probar la base de datos: " . $conn->error);
    }
    // si desea ver la confirmación, puede descomentar la línea siguiente:
    // echo "DB ok (SELECT 1) <br>";
}

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


