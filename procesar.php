<?php

$apiKey = "AIzaSyA752oCuP38hIIZ8uqirwVU7nU2JknuQb8";

// Validar imagen
if (!isset($_FILES['imagen'])) {
    echo json_encode(["error" => "No se recibió imagen"]);
    exit;
}

// Convertir imagen a base64
$imageData = base64_encode(file_get_contents($_FILES['imagen']['tmp_name']));

// Prompt PRO
$prompt = "Extrae los datos de esta imagen en JSON.

Columnas:
- descripcion
- cantidad

Reglas:
- Corrige errores de texto
- Une líneas separadas
- Detecta productos aunque estén borrosos
- Asocia cantidades correctamente
- Devuelve SOLO JSON válido sin texto adicional";

// Endpoint Gemini
$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

// Datos
$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt],
                [
                    "inline_data" => [
                        "mime_type" => "image/jpeg",
                        "data" => $imageData
                    ]
                ]
            ]
        ]
    ]
];

// CURL
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    exit;
}

curl_close($ch);

// Procesar respuesta
$result = json_decode($response, true);

if (isset($result["candidates"][0]["content"]["parts"][0]["text"])) {
    echo $result["candidates"][0]["content"]["parts"][0]["text"];
} else {
    echo json_encode($result);
}
