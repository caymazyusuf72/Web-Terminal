<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['text']) || empty($input['text'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Text parameter is required']);
    exit;
}

$text = $input['text'];
$size = isset($input['size']) ? intval($input['size']) : 200;

// Size validation
if ($size < 100 || $size > 800) {
    $size = 200;
}

// QR Code API kullanımı (ücretsiz servis)
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/";

$params = [
    'size' => $size . 'x' . $size,
    'data' => urlencode($text),
    'format' => 'png',
    'ecc' => 'M', // Error correction level
    'margin' => 10,
    'color' => '000000',
    'bgcolor' => 'ffffff'
];

$query_string = http_build_query($params);
$qr_url = $qr_api_url . '?' . $query_string;

// QR kodu indir ve base64'e çevir
$qr_image = @file_get_contents($qr_url);

if ($qr_image === false) {
    // API başarısız olursa ASCII QR code oluştur
    $ascii_qr = generateASCIIQR($text);
    
    echo json_encode([
        'success' => true,
        'text' => $text,
        'size' => $size,
        'qr_url' => $qr_url,
        'image_data' => null,
        'ascii_qr' => $ascii_qr,
        'api_failed' => true,
        'message' => 'QR API failed, showing ASCII version',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Base64 encode
$base64_image = base64_encode($qr_image);

// QR analizi
$analysis = analyzeQRContent($text);

echo json_encode([
    'success' => true,
    'text' => $text,
    'size' => $size,
    'qr_url' => $qr_url,
    'image_data' => 'data:image/png;base64,' . $base64_image,
    'ascii_qr' => null,
    'api_failed' => false,
    'analysis' => $analysis,
    'file_size' => strlen($qr_image),
    'timestamp' => date('Y-m-d H:i:s'),
    'instructions' => [
        'Tarayıcıda görüntülemek için image_data URL\'sini kullanın',
        'Mobil cihazda taramak için QR kodu ekranda gösterin',
        'İndirmek için sağ tık > Resmi farklı kaydet'
    ]
]);

function generateASCIIQR($text) {
    // Basit ASCII QR kod simülasyonu
    $hash = md5($text);
    $size = 15;
    $pattern = [];
    
    for ($i = 0; $i < $size; $i++) {
        $row = '';
        for ($j = 0; $j < $size; $j++) {
            $index = ($i * $size + $j) % strlen($hash);
            $char = hexdec($hash[$index]);
            $row .= ($char % 2 == 0) ? '██' : '  ';
        }
        $pattern[] = $row;
    }
    
    return $pattern;
}

function analyzeQRContent($text) {
    $analysis = [
        'type' => 'text',
        'length' => strlen($text),
        'complexity' => 'simple',
        'recommendations' => []
    ];
    
    // URL kontrolü
    if (filter_var($text, FILTER_VALIDATE_URL)) {
        $analysis['type'] = 'url';
        $analysis['domain'] = parse_url($text, PHP_URL_HOST);
        $analysis['protocol'] = parse_url($text, PHP_URL_SCHEME);
        
        if ($analysis['protocol'] === 'https') {
            $analysis['security'] = 'secure';
            $analysis['recommendations'][] = 'URL güvenli (HTTPS)';
        } else {
            $analysis['security'] = 'insecure';
            $analysis['recommendations'][] = 'HTTP URL - güvenlik riski olabilir';
        }
    }
    
    // Email kontrolü
    if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
        $analysis['type'] = 'email';
        $analysis['domain'] = substr(strrchr($text, "@"), 1);
        $analysis['recommendations'][] = 'Email adresi tespit edildi';
    }
    
    // Telefon kontrolü
    if (preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $text)) {
        $analysis['type'] = 'phone';
        $analysis['recommendations'][] = 'Telefon numarası tespit edildi';
    }
    
    // WiFi kontrolü
    if (strpos($text, 'WIFI:') === 0) {
        $analysis['type'] = 'wifi';
        $analysis['recommendations'][] = 'WiFi bilgisi - cihazınız otomatik bağlanabilir';
    }
    
    // Karmaşıklık analizi
    if (strlen($text) > 100) {
        $analysis['complexity'] = 'complex';
        $analysis['recommendations'][] = 'Uzun metin - tarama zor olabilir';
    } elseif (strlen($text) > 50) {
        $analysis['complexity'] = 'medium';
    }
    
    // Özel karakterler
    if (preg_match('/[^\x00-\x7F]/', $text)) {
        $analysis['contains_unicode'] = true;
        $analysis['recommendations'][] = 'Unicode karakterler içeriyor';
    }
    
    // Güvenlik uyarıları
    if (stripos($text, 'password') !== false || stripos($text, 'şifre') !== false) {
        $analysis['security_warning'] = 'Şifre içerik tespit edildi - dikkatli olun!';
        $analysis['recommendations'][] = '⚠️ Hassas bilgi içeriyor olabilir';
    }
    
    return $analysis;
}
?>