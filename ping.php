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

if (!isset($input['host']) || empty($input['host'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Host parameter is required']);
    exit;
}

$host = $input['host'];

// Host'u güvenli hale getir - sadece alfanümerik karakterler, nokta, tire ve alt çizgi
if (!preg_match('/^[a-zA-Z0-9.\-_]+$/', $host)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid host format']);
    exit;
}

// Ping komutunu çalıştır
$count = 4; // 4 ping paketi gönder

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows için ping komutu
    $command = "ping -n $count " . escapeshellarg($host) . " 2>&1";
} else {
    // Linux/Unix için ping komutu
    $command = "ping -c $count " . escapeshellarg($host) . " 2>&1";
}

$output = [];
$return_code = 0;

exec($command, $output, $return_code);

// Ping sonuçlarını analiz et
$success_count = 0;
$failed_count = 0;
$times = [];
$formatted_output = [];

foreach ($output as $line) {
    $formatted_output[] = $line;
    
    // Windows ping analizi
    if (preg_match('/Reply from.*time[<=](\d+)ms/i', $line, $matches)) {
        $success_count++;
        $times[] = (int)$matches[1];
    } elseif (preg_match('/Request timed out/i', $line)) {
        $failed_count++;
    }
    
    // Linux ping analizi  
    if (preg_match('/64 bytes from.*time=([0-9.]+) ms/', $line, $matches)) {
        $success_count++;
        $times[] = (float)$matches[1];
    } elseif (preg_match('/no answer yet/', $line) || preg_match('/Destination Host Unreachable/', $line)) {
        $failed_count++;
    }
}

// Eğer başarı/başarısızlık sayısı bulunamadıysa, return code'a bak
if ($success_count == 0 && $failed_count == 0) {
    if ($return_code == 0) {
        $success_count = $count; // Tüm paketler başarılı
    } else {
        $failed_count = $count; // Tüm paketler başarısız
    }
}

// İstatistikleri hesapla
$packet_loss = $count > 0 ? round(($failed_count / $count) * 100, 1) : 100;
$avg_time = count($times) > 0 ? round(array_sum($times) / count($times), 1) : 0;
$min_time = count($times) > 0 ? min($times) : 0;
$max_time = count($times) > 0 ? max($times) : 0;

$response = [
    'success' => $return_code === 0,
    'host' => $host,
    'packets_sent' => $count,
    'packets_received' => $success_count,
    'packets_lost' => $failed_count,
    'packet_loss_percent' => $packet_loss,
    'times' => $times,
    'avg_time' => $avg_time,
    'min_time' => $min_time,
    'max_time' => $max_time,
    'raw_output' => $formatted_output,
    'return_code' => $return_code
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>