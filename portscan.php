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
$timeout = 1; // 1 saniye timeout

// Host'u güvenli hale getir
if (!filter_var($host, FILTER_VALIDATE_IP) && !filter_var("http://".$host, FILTER_VALIDATE_URL)) {
    // Basit domain name validation
    if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $host)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid host format']);
        exit;
    }
}

// Yaygın portlar listesi
$common_ports = [
    21 => 'FTP',
    22 => 'SSH',
    23 => 'Telnet',
    25 => 'SMTP',
    53 => 'DNS',
    80 => 'HTTP',
    110 => 'POP3',
    143 => 'IMAP',
    443 => 'HTTPS',
    993 => 'IMAPS',
    995 => 'POP3S',
    1433 => 'MSSQL',
    3306 => 'MySQL',
    3389 => 'RDP',
    5432 => 'PostgreSQL',
    5900 => 'VNC',
    8080 => 'HTTP-Alt',
    8443 => 'HTTPS-Alt'
];

$open_ports = [];
$closed_ports = [];
$scan_results = [];

foreach ($common_ports as $port => $service) {
    $start_time = microtime(true);
    
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    $scan_time = round((microtime(true) - $start_time) * 1000, 2);
    
    if ($connection) {
        fclose($connection);
        $open_ports[] = [
            'port' => $port,
            'service' => $service,
            'status' => 'open',
            'time' => $scan_time . 'ms'
        ];
        $scan_results[] = "Port $port ($service): OPEN - Response time: {$scan_time}ms";
    } else {
        $closed_ports[] = [
            'port' => $port,
            'service' => $service,
            'status' => 'closed',
            'time' => $scan_time . 'ms'
        ];
        $scan_results[] = "Port $port ($service): CLOSED/FILTERED";
    }
}

// İstatistikler
$total_ports = count($common_ports);
$open_count = count($open_ports);
$closed_count = count($closed_ports);

$response = [
    'success' => true,
    'host' => $host,
    'scan_time' => date('Y-m-d H:i:s'),
    'total_ports_scanned' => $total_ports,
    'open_ports' => $open_ports,
    'closed_ports' => $closed_ports,
    'open_count' => $open_count,
    'closed_count' => $closed_count,
    'scan_results' => $scan_results,
    'security_assessment' => assessSecurity($open_ports)
];

function assessSecurity($open_ports) {
    $risky_ports = [21, 23, 25, 1433, 3389, 5900]; // FTP, Telnet, SMTP, MSSQL, RDP, VNC
    $web_ports = [80, 443, 8080, 8443];
    
    $risk_level = 'LOW';
    $warnings = [];
    
    foreach ($open_ports as $port_info) {
        $port = $port_info['port'];
        
        if (in_array($port, $risky_ports)) {
            $risk_level = 'HIGH';
            $warnings[] = "Port {$port} ({$port_info['service']}) is potentially risky if exposed to internet";
        }
        
        if ($port == 22) {
            $warnings[] = "SSH (22) is open - Ensure strong authentication is configured";
        }
        
        if (in_array($port, $web_ports)) {
            $warnings[] = "Web service detected on port {$port} - Check for proper security headers";
        }
    }
    
    if (count($open_ports) > 5) {
        $risk_level = 'MEDIUM';
        $warnings[] = "Many ports are open - Consider closing unnecessary services";
    }
    
    if (empty($warnings)) {
        $warnings[] = "No immediate security concerns detected";
    }
    
    return [
        'risk_level' => $risk_level,
        'warnings' => $warnings,
        'recommendations' => [
            'Close unnecessary ports',
            'Use firewall to restrict access',
            'Keep services updated',
            'Use strong authentication',
            'Monitor access logs'
        ]
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>