<?php
ob_start();

// Configuración de tu proyecto según la imagen
$projectId = "ses-salud12512405f589v253r245g";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $passwordInput = trim($_POST['contrasena'] ?? '');

    if (!empty($usuario) && !empty($passwordInput)) {
        
        // URL apuntando a la colección empleadosSes y al documento del usuario
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/empleadosSes/" . urlencode($usuario);

        // 1. Consultar el documento
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $userData = json_decode($response, true);

        if ($httpCode === 200 && isset($userData['fields'])) {
            // Extraer contraseña de Firestore (como se ve en image_9c6776.png)
            $dbPassword = $userData['fields']['contrasena']['stringValue'] ?? '';

            if ($dbPassword === $passwordInput) {
                
                // 2. Generar el nuevo ID de sesión
                $newSession = bin2hex(random_bytes(16));

                // 3. Actualizar el campo phpsession en Firestore
                $updateUrl = $url . "?updateMask.fieldPaths=phpsession";
                $updatePayload = json_encode([
                    "fields" => [
                        "phpsession" => ["stringValue" => $newSession]
                    ]
                ]);

                $chUp = curl_init();
                curl_setopt($chUp, CURLOPT_URL, $updateUrl);
                curl_setopt($chUp, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($chUp, CURLOPT_POSTFIELDS, $updatePayload);
                curl_setopt($chUp, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chUp, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_exec($chUp);

                // 4. Redirección final con el token en la URL
                header("Location: /s/i/ses.php?phpsession=" . $newSession);
                exit();
            } else {
                $error = "La contraseña no coincide.";
            }
        } else {
            $error = "Usuario no encontrado.";
        }
    } else {
        $error = "Escribe el usuario y la contraseña.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - SES Salud</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e9ecef; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); width: 320px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #218838; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; text-align: center; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Acceso Personal</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="usuario" placeholder="Usuario (ej: jfornielest01)" required>
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <button type="submit">Iniciar Sesión</button>
    </form>
</div>

</body>
</html>
<?php ob_end_flush(); ?>
