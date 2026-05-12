<?php
ob_start();

$projectId = "ses-salud12512405f589v253r245g";
$sessionID = $_GET['phpsession'] ?? '';

// 1. Validación de seguridad en el Sidebar
if (empty($sessionID)) {
    exit(); // No mostrar nada si no hay sesión
}

// Consultar Firestore para verificar sesión y rol
$url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:runQuery";
$query = [
    "structuredQuery" => [
        "from" => [["collectionId" => "empleadosSes"]],
        "where" => [
            "fieldFilter" => [
                "field" => ["fieldPath" => "phpsession"],
                "op" => "EQUAL",
                "value" => ["stringValue" => $sessionID]
            ]
        ],
        "limit" => 1
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$results = json_decode($response, true);

// Si no se encuentra el documento o el phpsession no coincide, no mostramos los botones
$mostrarMenu = false;
if (isset($results[0]['document'])) {
    $fields = $results[0]['document']['fields'];
    $rolesRaw = $fields['rolesSES']['arrayValue']['values'] ?? [];
    
    foreach ($rolesRaw as $roleItem) {
        if ($roleItem['stringValue'] === "Jefatura") {
            $mostrarMenu = true;
            break;
        }
    }
}

if (!$mostrarMenu) {
    exit("Acceso Restringido");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            margin: 0;
            padding: 10px;
            background-color: #ffffff; /* Fondo blanco */
            font-family: 'Segoe UI', sans-serif;
        }
        .menu-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-nav {
            padding: 12px 15px;
            background-color: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            border: 1px solid #eee;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }
        .btn-nav:hover {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .section-title {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            margin: 15px 0 5px 5px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

    <div class="menu-container">
        <div class="section-title">Gestión</div>
        
        <a href="/contenidoPrincipal/Jefatura/introducirPlantillas.php?phpsession=<?php echo $sessionID; ?>" 
           target="mainframe" class="btn-nav">
           Introducir Plantillas
        </a>

        <div class="section-title">Usuario</div>

        <a href="/contenidoPrincipal/Jefatura/misDatos.php?phpsession=<?php echo $sessionID; ?>" 
           target="mainframe" class="btn-nav">
           Mis Datos
        </a>
    </div>

    <script>
        // Ocultar el token de la URL en el iframe
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
