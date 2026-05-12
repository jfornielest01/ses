
<?php
// --- CONFIGURACIÓN ---
$projectId = "ses-salud12512405f589v253r245g";

// Lógica de procesamiento al recibir el POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $passwordInput = $_POST['contrasena'] ?? '';

    if (!empty($usuario) && !empty($passwordInput)) {
        
        // 1. URL del documento en Firestore (REST API)
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/empleadosSes/{$usuario}";

        // 2. Obtener datos del usuario vía cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $userData = json_decode($response, true);

        if ($httpCode === 200 && isset($userData['fields'])) {
            $dbPassword = $userData['fields']['contrasena']['stringValue'] ?? '';

            // 3. Verificar contraseña (comparación directa según tu esquema)
            if ($dbPassword === $passwordInput) {
                
                // 4. Generar nuevo phpsession
                $newSession = bin2hex(random_bytes(16));

                // 5. Actualizar Firestore usando PATCH
                $updateUrl = $url . "?updateMask.fieldPaths=phpsession";
                $updatePayload = json_encode([
                    "fields" => [
                        "phpsession" => ["stringValue" => $newSession]
                    ]
                ]);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $updateUrl);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $updatePayload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_exec($ch);
                curl_close($ch);

                // 6. Redirección solicitada
                header("Location: /s/i/ses.php?phpsession=" . $newSession);
                exit();
            } else {
                $error = "La contraseña es incorrecta.";
            }
        } else {
            $error = "El usuario no existe o hay un error de conexión.";
        }
    } else {
        $error = "Por favor, rellena todos los campos.";
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
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; }
        .login-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; font-size: 0.9rem; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Login SES</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Usuario (ID de empleado):</label>
        <input type="text" name="usuario" required placeholder="ej: Juan123">
        
        <label>Contraseña:</label>
        <input type="password" name="contrasena" required>
        
        <button type="submit">Entrar</button>
    </form>
</div>

</body>
</html>
