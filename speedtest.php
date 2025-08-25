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

function performSpeedTest() {
    $start_time = microtime(true);
    
    // Download test - test dosyası indir
    $test_urls = [
        'https://httpbin.org/bytes/1048576', // 1MB test file
        'https://www.google.com/images/branding/googlelogo/2x/googlelogo_color_92x30dp.png',
        'https://via.placeholder.com/500x500.png'
    ];
    
    $download_speeds = [];
    $successful_tests = 0;
    
    foreach ($test_urls as $url) {
        $test_start = microtime(true);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SpeedTest-Terminal/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $download_size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        curl_close($ch);
        
        $test_end = microtime(true);
        $test_duration = $test_end - $test_start;
        
        if ($http_code == 200 && $data !== false && $download_size > 0 && $test_duration > 0) {
            $speed_bps = $download_size / $test_duration;
            $speed_mbps = ($speed_bps * 8) / (1024 * 1024); // Convert to Mbps
            $download_speeds[] = $speed_mbps;
            $successful_tests++;
        }
    }
    
    // Upload test simülasyonu (POST request)
    $upload_start = microtime(true);
    $test_data = str_repeat('A', 102400); // 100KB test data
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://httpbin.org/post');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $test_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $upload_response = curl_exec($ch);
    $upload_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $upload_end = microtime(true);
    $upload_duration = $upload_end - $upload_start;
    
    $upload_speed_mbps = 0;
    if ($upload_http_code == 200 && $upload_duration > 0) {
        $upload_speed_bps = strlen($test_data) / $upload_duration;
        $upload_speed_mbps = ($upload_speed_bps * 8) / (1024 * 1024);
    }
    
    // Ping test
    $ping_times = [];
    for ($i = 0; $i < 3; $i++) {
        $ping_start = microtime(true);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/favicon.ico');
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $ping_end = microtime(true);
        $ping_time = ($ping_end - $ping_start) * 1000; // Convert to ms
        
        if ($http_code == 200) {
            $ping_times[] = $ping_time;
        }
    }
    
    $total_time = microtime(true) - $start_time;
    
    return [
        'download_speeds' => $download_speeds,
        'upload_speed' => $upload_speed_mbps,
        'ping_times' => $ping_times,
        'successful_tests' => $successful_tests,
        'total_test_time' => $total_time
    ];
}

function analyzeSpeedResults($results) {
    $analysis = [
        'download_mbps' => 0,
        'upload_mbps' => 0,
        'ping_ms' => 0,
        'connection_quality' => 'Poor',
        'recommendations' => []
    ];
    
    // Download analizi
    if (!empty($results['download_speeds'])) {
        $analysis['download_mbps'] = round(array_sum($results['download_speeds']) / count($results['download_speeds']), 2);
    }
    
    // Upload analizi
    $analysis['upload_mbps'] = round($results['upload_speed'], 2);
    
    // Ping analizi
    if (!empty($results['ping_times'])) {
        $analysis['ping_ms'] = round(array_sum($results['ping_times']) / count($results['ping_times']), 0);
    }
    
    // Bağlantı kalitesi değerlendirmesi
    $download = $analysis['download_mbps'];
    $upload = $analysis['upload_mbps'];
    $ping = $analysis['ping_ms'];
    
    if ($download > 50 && $upload > 10 && $ping < 50) {
        $analysis['connection_quality'] = 'Excellent';
        $analysis['recommendations'][] = '🚀 Mükemmel bağlantı! 4K streaming, gaming için ideal';
    } elseif ($download > 25 && $upload > 5 && $ping < 100) {
        $analysis['connection_quality'] = 'Good';
        $analysis['recommendations'][] = '👍 İyi bağlantı! HD streaming ve çoğu uygulama için yeterli';
    } elseif ($download > 10 && $ping < 200) {
        $analysis['connection_quality'] = 'Average';
        $analysis['recommendations'][] = '⚡ Orta seviye bağlantı. Temel kullanım için uygun';
    } else {
        $analysis['connection_quality'] = 'Poor';
        $analysis['recommendations'][] = '📶 Yavaş bağlantı. ISP ile iletişime geçin';
    }
    
    // Özel öneriler
    if ($ping > 100) {
        $analysis['recommendations'][] = '🎮 Yüksek ping - online gaming zor olabilir';
    }
    
    if ($upload < 1) {
        $analysis['recommendations'][] = '⬆️ Düşük upload hızı - video konferans etkilenebilir';
    }
    
    if ($download > 100) {
        $analysis['recommendations'][] = '💨 Süper hızlı download! Büyük dosyalar için ideal';
    }
    
    return $analysis;
}

// Speed test çalıştır
$test_results = performSpeedTest();
$analysis = analyzeSpeedResults($test_results);

$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'test_duration' => round($test_results['total_test_time'], 2),
    'results' => [
        'download_mbps' => $analysis['download_mbps'],
        'upload_mbps' => $analysis['upload_mbps'],
        'ping_ms' => $analysis['ping_ms'],
        'connection_quality' => $analysis['connection_quality']
    ],
    'analysis' => $analysis,
    'raw_data' => [
        'download_tests' => count($test_results['download_speeds']),
        'successful_tests' => $test_results['successful_tests'],
        'ping_samples' => count($test_results['ping_times'])
    ],
    'server_info' => [
        'location' => 'Multiple Test Servers',
        'test_method' => 'HTTP Download/Upload',
        'accuracy' => 'Approximate (PHP-based)'
    ],
    'disclaimer' => 'Bu test PHP tabanlı yaklaşık sonuçlar verir. Daha doğru sonuç için resmi speedtest uygulamaları kullanın.'
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>