<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ⚠️ COLOQUE A SUA CHAVE DO GEMINI API AQUI:
$geminiApiKey = "SUA_CHAVE_GEMINI_API_AQUI";

if (empty($geminiApiKey) || $geminiApiKey === "SUA_CHAVE_GEMINI_API_AQUI") {
    echo json_encode([
        'success' => false,
        'error' => 'Insira a sua Chave API do Gemini no ficheiro scan.php.'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imageBase64 = $input['image'] ?? '';

if (empty($imageBase64)) {
    echo json_encode(['success' => false, 'error' => 'Nenhuma imagem foi recebida.']);
    exit;
}

if (preg_match('/^data:image\/(\w+);base64,/', $imageBase64, $type)) {
    $imageBase64 = substr($imageBase64, strpos($imageBase64, ',') + 1);
    $mimeType = strtolower($type[1]);
    if ($mimeType === 'jpg') $mimeType = 'jpeg';
} else {
    $mimeType = 'jpeg';
}

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $geminiApiKey;

$prompt = "Analisa este cartão de visita e extrai os dados num JSON com as chaves exatas: \"name\", \"company\", \"phone\", \"email\". Retorna apenas o JSON puro sem markdown.";

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt],
                [
                    'inline_data' => [
                        'mime_type' => "image/" . $mimeType,
                        'data' => $imageBase64
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'error' => 'Erro na API Gemini: ' . $response]);
    exit;
}

$result = json_decode($response, true);
$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
$cleanJson = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/', '$1', trim($text));
$extracted = json_decode($cleanJson, true);

if (!$extracted) {
    echo json_encode(['success' => false, 'error' => 'Não foi possível ler os dados da imagem.']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => [
        'name'    => $extracted['name'] ?? '',
        'company' => $extracted['company'] ?? '',
        'phone'   => $extracted['phone'] ?? '',
        'email'   => $extracted['email'] ?? ''
    ]
]);
