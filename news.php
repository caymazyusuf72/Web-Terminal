<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// RSS Feed URL'leri (Türkçe teknoloji siteleri)
$rss_feeds = [
    'https://www.webtekno.com/rss.xml',
    'https://feeds.feedburner.com/shiftdelete/feed',
    'https://www.donanimhaber.com/rss.asp',
    'https://www.log.com.tr/feed/',
    'https://teknoblog.com/feed/'
];

function fetchNewsFromRSS($rss_url, $max_items = 1) {
    $news = [];
    
    // RSS feed'i indir
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $rss_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $xml_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || !$xml_content) {
        return [];
    }
    
    // XML parse et
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xml_content);
    
    if ($xml === false) {
        return [];
    }
    
    $site_name = 'Teknoloji Haberleri';
    if (isset($xml->channel->title)) {
        $site_name = (string)$xml->channel->title;
    }
    
    $item_count = 0;
    foreach ($xml->channel->item as $item) {
        if ($item_count >= $max_items) break;
        
        $title = (string)$item->title;
        $description = (string)$item->description;
        $link = (string)$item->link;
        $pub_date = (string)$item->pubDate;
        
        // Description'ı temizle (HTML tagları kaldır)
        $description = strip_tags($description);
        $description = html_entity_decode($description);
        
        // Çok uzunsa kısalt
        if (strlen($description) > 300) {
            $description = substr($description, 0, 300) . '...';
        }
        
        // Tarihi düzenle
        $formatted_date = date('d.m.Y H:i', strtotime($pub_date));
        if (!$formatted_date || $formatted_date == '01.01.1970 03:00') {
            $formatted_date = 'Bilinmiyor';
        }
        
        $news[] = [
            'title' => $title,
            'description' => $description,
            'url' => $link,
            'published' => $formatted_date,
            'source' => $site_name,
            'category' => 'teknoloji'
        ];
        
        $item_count++;
    }
    
    return $news;
}

// Haberleri gerçek RSS feedlerden çek
$news = [];
$successful_source = '';
$failed_feeds = [];

foreach ($rss_feeds as $feed_url) {
    $fetched_news = fetchNewsFromRSS($feed_url, 1);
    if (!empty($fetched_news)) {
        $news = $fetched_news;
        $successful_source = $feed_url;
        break;
    } else {
        $failed_feeds[] = $feed_url;
    }
}

// Eğer hiçbir RSS'den çekilemezse hata döndür
if (empty($news)) {
    $response = [
        'success' => false,
        'error' => 'Tüm RSS feedlerinden veri çekilemedi',
        'timestamp' => date('Y-m-d H:i:s'),
        'failed_feeds' => $failed_feeds,
        'demo_mode' => false,
        'suggestion' => 'Lütfen internet bağlantınızı kontrol edin ve tekrar deneyin'
    ];
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'news_count' => count($news),
    'news' => $news,
    'source_used' => $successful_source,
    'demo_mode' => false,
    'available_feeds' => count($rss_feeds),
    'failed_feeds_count' => count($failed_feeds),
    'categories' => ['teknoloji', 'yapay zeka', 'blockchain', 'yazılım', 'donanım'],
    'data_source' => 'Gerçek RSS Feedleri',
    'refresh_info' => [
        'last_update' => date('Y-m-d H:i:s'),
        'next_update' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
        'cache_duration' => '15 minutes'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>