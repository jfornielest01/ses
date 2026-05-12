<?php
ob_start(); // Previene el error de "headers already sent"

// --- CONFIGURACIÓN ---
$projectId = "ses-salud12512405f589v253r245g";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $passwordInput = $_POST['contrasena'] ?? '';

    if (!empty($usuario) && !empty($passwordInput)) {
        
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/empleadosSes/" . urlencode($usuario);

        // 1. Obtener datos del usuario
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Ya no usamos curl_close($ch) en PHP moderno

        $userData = json_decode($response, true);

        if ($httpCode === 200 && isset($userData['fields'])) {
            $dbPassword = $userData['fields']['contrasena']['stringValue'] ?? '';

            if ($dbPassword === $passwordInput) {
                
                $newSession = bin2hex(random_bytes(16));

                // 2. Actualizar phpsession
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

                // 3. Redirección limpia
                header("Location: /s/i/ses.php?phpsession=" . $newSession);
                exit();
            } else {
                $error = "La contraseña es incorrecta.";
            }
        } else {
            $error = "El usuario no existe o error de conexión.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Empleados SES</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; margin: 0; }
        .login-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .error { color: #d93025; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="login-card">
    <h2 style="margin-top:0">Login SES</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Usuario:</label>
        <input type="text" name="usuario" required autofocus>
        
        <label>Contraseña:</label>
        <input type="password" name="contrasena" required>
        
        <button type="submit">ENTRAR</button>
    </form>
</div>

</body>
</html>
<?php
ob_end_flush();
?>
