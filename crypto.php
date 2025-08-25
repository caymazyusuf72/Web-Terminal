<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// CoinGecko API - ücretsiz kullanım
$api_url = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=10&page=1&sparkline=false';

// Kripto emojileri
$crypto_emojis = [
    'bitcoin' => '🟠',
    'ethereum' => '🔷',
    'binancecoin' => '🟡',
    'cardano' => '🔵',
    'solana' => '🟣',
    'polkadot' => '🔴',
    'dogecoin' => '🟤',
    'avalanche-2' => '🔺',
    'chainlink' => '🔗',
    'polygon' => '🟪'
];

// Gerçek kripto verilerini çek
function fetchCryptoPrices() {
    global $api_url, $crypto_emojis;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CryptoTerminal/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Network error: ' . $error);
    }
    
    if ($http_code !== 200) {
        throw new Exception('API request failed. HTTP: ' . $http_code);
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !is_array($data)) {
        throw new Exception('Invalid API response');
    }
    
    $prices = [];
    foreach ($data as $crypto) {
        $emoji = isset($crypto_emojis[$crypto['id']]) ? $crypto_emojis[$crypto['id']] : '💎';
        
        $prices[$crypto['id']] = [
            'id' => $crypto['id'],
            'symbol' => strtoupper($crypto['symbol']),
            'emoji' => $emoji,
            'name' => $crypto['name'],
            'current_price' => $crypto['current_price'],
            'price_change_percentage_24h' => round($crypto['price_change_percentage_24h'], 2),
            'market_cap' => $crypto['market_cap'],
            'volume_24h' => $crypto['total_volume'],
            'last_updated' => $crypto['last_updated']
        ];
    }
    
    return $prices;
}

function analyzeCryptoMarket($prices) {
    $total_positive = 0;
    $total_negative = 0;
    $biggest_gainer = null;
    $biggest_loser = null;
    
    foreach ($prices as $crypto) {
        $change = $crypto['price_change_percentage_24h'];
        
        if ($change > 0) {
            $total_positive++;
            if (!$biggest_gainer || $change > $biggest_gainer['price_change_percentage_24h']) {
                $biggest_gainer = $crypto;
            }
        } else {
            $total_negative++;
            if (!$biggest_loser || $change < $biggest_loser['price_change_percentage_24h']) {
                $biggest_loser = $crypto;
            }
        }
    }
    
    $market_sentiment = 'NEUTRAL';
    if ($total_positive > $total_negative * 1.5) {
        $market_sentiment = 'BULLISH';
    } elseif ($total_negative > $total_positive * 1.5) {
        $market_sentiment = 'BEARISH';
    }
    
    return [
        'total_cryptos' => count($prices),
        'positive_count' => $total_positive,
        'negative_count' => $total_negative,
        'market_sentiment' => $market_sentiment,
        'biggest_gainer' => $biggest_gainer,
        'biggest_loser' => $biggest_loser,
        'fear_greed_index' => rand(10, 90), // Demo fear & greed index
        'recommendations' => generateCryptoRecommendations($market_sentiment)
    ];
}

function generateCryptoRecommendations($sentiment) {
    $recommendations = [
        'BULLISH' => [
            '📈 Piyasa yükseliş trendinde - kar alma stratejisi düşünün',
            '🎯 Stop-loss seviyelerini güncelleyin',
            '💰 FOMO\'ya kapılmayın, analiz yapın',
            '📊 Teknik analiz seviyelerini takip edin'
        ],
        'BEARISH' => [
            '📉 Piyasa düşüş trendinde - dikkatli olun',
            '💎 Uzun vadeli yatırımcıysanız HODLing düşünün',
            '🛡️ Risk yönetimi uygulayın',
            '📈 DCA (Dollar Cost Averaging) stratejisi düşünün'
        ],
        'NEUTRAL' => [
            '⚖️ Piyasa kararsız - beklemede kalın',
            '📈 Önemli direnç/destek seviyelerini izleyin',
            '💡 Araştırma yapın, yeni projeler keşfedin',
            '🎯 Entry/exit stratejilerinizi planlayın'
        ]
    ];
    
    return $recommendations[$sentiment] ?? $recommendations['NEUTRAL'];
}

// Gerçek kripto verilerini çek ve API response oluştur
try {
    $prices = fetchCryptoPrices();
    
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'prices' => $prices,
        'market_analysis' => analyzeCryptoMarket($prices),
        'demo_mode' => false,
        'data_source' => 'CoinGecko API',
        'disclaimer' => 'Bu veriler gerçek piyasa verileridir. Yatırım tavsiyesi değildir!'
    ];
    
} catch (Exception $e) {
    // API başarısız olursa hata döndür
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'demo_mode' => false
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>