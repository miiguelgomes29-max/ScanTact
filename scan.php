<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Insira aqui a sua chave obtida no Google AI Studio (https://aistudio.google.com/)
define('GEMINI_API_KEY', 'SUA_CHAVE_API_GEMINI_AQUI');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido. Utilize POST.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data || empty($data['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nenhuma imagem enviada para análise.']);
    exit;
}

$base64Image = $data['image'];
$mimeType = !empty($data['mimeType']) ? $data['mimeType'] : 'image/jpeg';

if (strpos($base64Image, ',') !== false) {
    list(, $base64Image) = explode(',', $base64Image);
}

if (GEMINI_API_KEY === 'SUA_CHAVE_API_GEMINI_AQUI' || empty(GEMINI_API_KEY)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'A chave da API Gemini não está configurada no ficheiro scan.php no servidor.'
    ]);
    exit;
}

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => "Analisa este cartão de visita e extrai a informação em formato JSON com os campos: 'name', 'company', 'phone', 'email'."],
                [
                    'inlineData' => [
                        'mimeType' => $mimeType,
                        'data' => $base64Image
                    ]
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'responseMimeType' => 'application/json',
        'responseSchema' => [
            'type' => 'OBJECT',
            'properties' => [
                'name' => ['type' => 'STRING'],
                'company' => ['type' => 'STRING'],
                'phone' => ['type' => 'STRING'],
                'email' => ['type' => 'STRING']
            ]
        ]
    ]
];

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de comunicação no servidor: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    $errData = json_decode($response, true);
    $errMsg = isset($errData['error']['message']) ? $errData['error']['message'] : 'Erro na API do Gemini.';
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

$responseData = json_decode($response, true);
$rawText = isset($responseData['candidates'][0]['content']['parts'][0]['text']) ? $responseData['candidates'][0]['content']['parts'][0]['text'] : null;

if (!$rawText) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Não foi possível extrair dados do cartão de visita.']);
    exit;
}

$cleanJson = preg_replace('/^```json\s*|\s*```$/i', '', trim($rawText));
$parsedData = json_decode($cleanJson, true);

if (!$parsedData) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Formato de resposta da IA inválido.']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => [
        'name' => isset($parsedData['name']) ? $parsedData['name'] : '',
        'company' => isset($parsedData['company']) ? $parsedData['company'] : '',
        'phone' => isset($parsedData['phone']) ? $parsedData['phone'] : '',
        'email' => isset($parsedData['email']) ? $parsedData['email'] : ''
    ]
]);
?>