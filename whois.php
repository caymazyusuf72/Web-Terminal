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

if (!isset($input['domain']) || empty($input['domain'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Domain parameter is required']);
    exit;
}

$domain = trim(strtolower($input['domain']));

// Domain validation
if (!filter_var("http://".$domain, FILTER_VALIDATE_URL) && !filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
    if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid domain format']);
        exit;
    }
}

// Remove protocol if exists
$domain = preg_replace('/^https?:\/\//', '', $domain);
$domain = preg_replace('/\/.*$/', '', $domain); // Remove path
$domain = preg_replace('/^www\./', '', $domain); // Remove www

// Whois komutunu çalıştır
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows için alternatif yaklaşım - nslookup kullan
    $command = "nslookup " . escapeshellarg($domain) . " 2>&1";
} else {
    // Linux/Unix için whois
    $command = "whois " . escapeshellarg($domain) . " 2>&1";
}

$output = [];
$return_code = 0;

exec($command, $output, $return_code);

// Whois bilgilerini analiz et
$whois_info = parseWhoisData($output, $domain);

// DNS bilgileri ekle
$dns_info = getDNSInfo($domain);

$response = [
    'success' => true,
    'domain' => $domain,
    'whois_info' => $whois_info,
    'dns_info' => $dns_info,
    'raw_output' => $output,
    'command_used' => strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'nslookup' : 'whois',
    'timestamp' => date('Y-m-d H:i:s'),
    'security_analysis' => analyzeSecurityInfo($whois_info, $dns_info)
];

echo json_encode($response, JSON_PRETTY_PRINT);

function parseWhoisData($output, $domain) {
    $info = [
        'registrar' => 'Unknown',
        'creation_date' => 'Unknown',
        'expiration_date' => 'Unknown',
        'last_updated' => 'Unknown',
        'status' => 'Unknown',
        'name_servers' => [],
        'owner_info' => [],
        'admin_contact' => [],
        'tech_contact' => []
    ];
    
    $output_text = implode("\n", $output);
    
    // Registrar
    if (preg_match('/Registrar:\s*(.+)/i', $output_text, $matches)) {
        $info['registrar'] = trim($matches[1]);
    }
    
    // Dates
    if (preg_match('/Creation Date:\s*(.+)/i', $output_text, $matches) ||
        preg_match('/Created:\s*(.+)/i', $output_text, $matches) ||
        preg_match('/Registration Date:\s*(.+)/i', $output_text, $matches)) {
        $info['creation_date'] = trim($matches[1]);
    }
    
    if (preg_match('/Expir[ya]tion Date:\s*(.+)/i', $output_text, $matches) ||
        preg_match('/Expires:\s*(.+)/i', $output_text, $matches)) {
        $info['expiration_date'] = trim($matches[1]);
    }
    
    if (preg_match('/Updated Date:\s*(.+)/i', $output_text, $matches) ||
        preg_match('/Last Modified:\s*(.+)/i', $output_text, $matches)) {
        $info['last_updated'] = trim($matches[1]);
    }
    
    // Status
    if (preg_match('/Status:\s*(.+)/i', $output_text, $matches)) {
        $info['status'] = trim($matches[1]);
    }
    
    // Name servers
    if (preg_match_all('/Name Server:\s*(.+)/i', $output_text, $matches)) {
        $info['name_servers'] = array_map('trim', $matches[1]);
    }
    
    return $info;
}

function getDNSInfo($domain) {
    $dns_info = [
        'ip_address' => 'Unknown',
        'mx_records' => [],
        'txt_records' => [],
        'cname' => 'None'
    ];
    
    // IP Address
    $ip = gethostbyname($domain);
    if ($ip !== $domain) {
        $dns_info['ip_address'] = $ip;
    }
    
    // MX Records
    if (function_exists('dns_get_record')) {
        $mx_records = dns_get_record($domain, DNS_MX);
        if ($mx_records) {
            foreach ($mx_records as $mx) {
                $dns_info['mx_records'][] = [
                    'host' => $mx['target'],
                    'priority' => $mx['pri']
                ];
            }
        }
        
        // TXT Records
        $txt_records = dns_get_record($domain, DNS_TXT);
        if ($txt_records) {
            foreach ($txt_records as $txt) {
                $dns_info['txt_records'][] = $txt['txt'];
            }
        }
    }
    
    return $dns_info;
}

function analyzeSecurityInfo($whois, $dns) {
    $analysis = [
        'risk_level' => 'LOW',
        'warnings' => [],
        'recommendations' => [],
        'security_features' => []
    ];
    
    // Domain yaşı kontrolü
    if ($whois['creation_date'] !== 'Unknown') {
        $creation_time = strtotime($whois['creation_date']);
        if ($creation_time) {
            $domain_age_days = (time() - $creation_time) / (24 * 60 * 60);
            
            if ($domain_age_days < 30) {
                $analysis['risk_level'] = 'HIGH';
                $analysis['warnings'][] = 'Domain çok yeni (30 günden az) - dikkatli olun';
            } elseif ($domain_age_days < 365) {
                $analysis['risk_level'] = 'MEDIUM';
                $analysis['warnings'][] = 'Domain 1 yıldan yeni';
            } else {
                $analysis['security_features'][] = 'Domain eskiliği güvenilir (' . round($domain_age_days/365, 1) . ' yıl)';
            }
        }
    }
    
    // Expiration kontrolü
    if ($whois['expiration_date'] !== 'Unknown') {
        $expiry_time = strtotime($whois['expiration_date']);
        if ($expiry_time) {
            $days_to_expiry = ($expiry_time - time()) / (24 * 60 * 60);
            
            if ($days_to_expiry < 30) {
                $analysis['warnings'][] = 'Domain yakında sona erecek (' . round($days_to_expiry) . ' gün)';
            }
        }
    }
    
    // DNS kontrolü
    if ($dns['ip_address'] !== 'Unknown') {
        $analysis['security_features'][] = 'IP adresi çözümlenebiliyor';
        
        // Özel IP aralıkları kontrolü
        if (filter_var($dns['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $analysis['security_features'][] = 'Geçerli public IP adresi';
        } else {
            $analysis['warnings'][] = 'Private/Reserved IP adresi tespit edildi';
        }
    }
    
    // MX records kontrolü
    if (!empty($dns['mx_records'])) {
        $analysis['security_features'][] = 'Email servisi aktif (MX kayıtları mevcut)';
    }
    
    // TXT records kontrolü (SPF, DKIM, DMARC)
    foreach ($dns['txt_records'] as $txt) {
        if (stripos($txt, 'v=spf1') !== false) {
            $analysis['security_features'][] = 'SPF kaydı bulundu (email güvenliği)';
        }
        if (stripos($txt, 'v=DMARC1') !== false) {
            $analysis['security_features'][] = 'DMARC kaydı bulundu (email güvenliği)';
        }
    }
    
    // Genel öneriler
    $analysis['recommendations'] = [
        'Domain bilgilerini düzenli kontrol edin',
        'Whois privacy koruması kullanın',
        'DNS kayıtlarını güncel tutun',
        'SSL sertifikası kullanın',
        'Email güvenlik kayıtları ekleyin (SPF, DKIM, DMARC)'
    ];
    
    return $analysis;
}
?>