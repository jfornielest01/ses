<?php
ob_start();

$projectId = "ses-salud12512405f589v253r245g";
$sessionID = $_GET['phpsession'] ?? '';
$rolSolicitado = $_GET['modulo'] ?? ''; // El rol que seleccionó en ses.php

if (empty($sessionID) || empty($rolSolicitado)) {
    die("Acceso no autorizado.");
}

// 1. Validar sesión y Rol en Firestore
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

if (!isset($results[0]['document'])) {
    die("Sesión inválida.");
}

// 2. Comprobar si el rol solicitado está en su array rolesSES (image_9c0620.png)
$fields = $results[0]['document']['fields'];
$rolesRaw = $fields['rolesSES']['arrayValue']['values'] ?? [];
$esValido = false;

foreach ($rolesRaw as $roleItem) {
    if ($roleItem['stringValue'] === $rolSolicitado) {
        $esValido = true;
        break;
    }
}

if (!$esValido) {
    die("No tienes permiso para acceder al módulo: " . htmlspecialchars($rolSolicitado));
}

// Limpiamos el nombre del rol para las rutas (quitando acentos o espacios si los hubiera)
$rolPath = urlencode($rolSolicitado);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema CEC - <?php echo htmlspecialchars($rolSolicitado); ?></title>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: sans-serif; }
        
        /* Layout */
        #header-container { position: absolute; top: 0; left: 0; width: 100%; height: 60px; z-index: 10; border-bottom: 1px solid #ddd; }
        #main-layout { position: absolute; top: 60px; left: 0; width: 100%; height: calc(100% - 60px); display: flex; }

        /* Sidebar con efecto Hover */
        #sidebar-container { 
            width: 50px; /* Ancho colapsado */
            height: 100%; 
            transition: width 0.3s ease; 
            background: #2c3e50; 
            z-index: 20;
            overflow: hidden;
        }
        #sidebar-container:hover { 
            width: 250px; /* Ancho expandido al pasar el ratón */
        }

        /* Mainframe */
        #mainframe-container { flex-grow: 1; height: 100%; background: #fff; }

        iframe { width: 100%; height: 100%; border: none; }
    </style>

    <script>
        // Ocultar parámetros de la URL
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
</head>
<body>

    <div id="header-container">
        <iframe src="/cabecera/<?php echo $rolPath; ?>/pagina.php?phpsession=<?php echo $sessionID; ?>"></iframe>
    </div>

    <div id="main-layout">
        <div id="sidebar-container">
            <iframe src="/barraLateral/<?php echo $rolPath; ?>/pagina.php?phpsession=<?php echo $sessionID; ?>"></iframe>
        </div>

        <div id="mainframe-container">
            <iframe name="mainframe" src="/contenidoPrincipal/<?php echo $rolPath; ?>/pagina.php?phpsession=<?php echo $sessionID; ?>"></iframe>
        </div>
    </div>

</body>
</html>
<?php ob_end_flush(); ?>
