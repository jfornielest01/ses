<?php
ob_start();

$projectId = "ses-salud12512405f589v253r245g";
$sessionID = $_GET['phpsession'] ?? '';

if (empty($sessionID)) {
    die("Acceso denegado: Sesión no válida.");
}

// 1. Buscar en Firestore el documento que tenga este phpsession
// Usamos un StructuredQuery para encontrar al empleado por el token
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

// Verificar si encontramos al usuario
if (!isset($results[0]['document'])) {
    die("Sesión expirada o inválida.");
}

$fields = $results[0]['document']['fields'];
$rolesRaw = $fields['rolesSES']['arrayValue']['values'] ?? [];

// Convertimos el array de Firestore a un array simple de PHP para facilitar la búsqueda
$roles = [];
foreach ($rolesRaw as $roleItem) {
    $roles[] = $roleItem['stringValue'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel SES - Selección de Módulo</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); text-align: center; width: 400px; }
        h2 { color: #2c3e50; margin-bottom: 25px; }
        .btn-modulo { 
            display: block; width: 100%; padding: 15px; margin-bottom: 15px; 
            background: #0056b3; color: white; text-decoration: none; 
            border-radius: 8px; font-weight: bold; border: none; cursor: pointer; transition: 0.3s;
        }
        .btn-modulo:hover { background: #003d80; transform: translateY(-2px); }
    </style>

    <script>
        // Ocultar el phpsession de la URL inmediatamente por seguridad visual
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }

        const sessionActiva = "<?php echo $sessionID; ?>";

        function abreAplicacionEmergente(modulo) {
            // Redirige o abre la aplicación enviando la sesión por parámetro
            const urlFinal = `/s/i/CEC.php?phpsession=${sessionActiva}&modulo=${modulo}`;
            window.location.href = urlFinal;
        }
    </script>
</head>
<body>

<div class="container">
    <h2>Seleccione el módulo</h2>

    <?php 
    // Lógica basada en image_9c0620.png
    // Si tiene "Jefatura" aparece botón "Jefatura Enfermeria"
    if (in_array("Jefatura", $roles)): ?>
        <button class="btn-modulo" onclick="javascript:abreAplicacionEmergente('Jefatura')">
            Jefatura Enfermería
        </button>
    <?php endif; ?>

    <?php 
    // Si tiene "Supervisores" (o "Supervisor" según tu texto, ajustado a la imagen)
    if (in_array("Supervisores", $roles) || in_array("Supervisor", $roles)): ?>
        <button class="btn-modulo" onclick="javascript:abreAplicacionEmergente('Supervisor')">
            Supervisores
        </button>
    <?php endif; ?>

</div>

</body>
</html>
<?php ob_end_flush(); ?>
