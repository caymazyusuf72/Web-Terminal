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

if (!isset($input['city']) || empty($input['city'])) {
    http_response_code(400);
    echo json_encode(['error' => 'City parameter is required']);
    exit;
}

$city = trim($input['city']);

// OpenWeatherMap API - ücretsiz kullanım için
$api_key = "b6907d289e10d714a6e88b30761fae22"; // Genel public key (sınırlı kullanım)

// Gerçek API çağrısı
$url = "http://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&appid=" . $api_key . "&units=metric&lang=tr";

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_USERAGENT, 'WeatherApp/1.0');

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

if ($error) {
    echo json_encode(['error' => 'Network error: ' . $error]);
    exit;
}

if ($http_code !== 200) {
    echo json_encode(['error' => 'Weather API request failed. HTTP: ' . $http_code]);
    exit;
}

$api_data = json_decode($response, true);

if (!$api_data || isset($api_data['cod']) && $api_data['cod'] !== 200) {
    $error_msg = isset($api_data['message']) ? $api_data['message'] : 'City not found';
    echo json_encode(['error' => 'API Error: ' . $error_msg]);
    exit;
}

$weather_data = parseWeatherData($api_data);

echo json_encode($weather_data, JSON_PRETTY_PRINT);

function parseWeatherData($data) {
    $temp = round($data['main']['temp']);
    $feels_like = round($data['main']['feels_like']);
    $condition = $data['weather'][0]['main'];
    $description = $data['weather'][0]['description'];
    
    // Weather icons
    $icons = [
        'Clear' => '☀️',
        'Clouds' => '☁️',
        'Rain' => '🌧️',
        'Drizzle' => '🌦️',
        'Thunderstorm' => '⛈️',
        'Snow' => '❄️',
        'Mist' => '🌫️',
        'Fog' => '🌫️',
        'Haze' => '🌫️'
    ];
    
    $icon = isset($icons[$condition]) ? $icons[$condition] : '🌤️';
    
    return [
        'success' => true,
        'city' => $data['name'],
        'country' => $data['sys']['country'],
        'temperature' => $temp,
        'feels_like' => $feels_like,
        'condition' => $condition,
        'description' => ucfirst($description),
        'icon' => $icon,
        'humidity' => $data['main']['humidity'],
        'pressure' => $data['main']['pressure'],
        'wind_speed' => round($data['wind']['speed'] ?? 0, 1),
        'visibility' => round(($data['visibility'] ?? 10000) / 1000, 1),
        'timestamp' => date('Y-m-d H:i:s'),
        'demo_mode' => false,
        'advice' => getWeatherAdvice($temp, strtolower($condition), $data['wind']['speed'] ?? 0)
    ];
}

function getWeatherAdvice($temp, $condition, $wind_speed) {
    $advice = [];
    
    if ($temp < 0) {
        $advice[] = "🧥 Çok soğuk! Kalın giyinmeyi unutmayın";
        $advice[] = "⚠️ Buzlanma riski var, dikkatli olun";
    } elseif ($temp < 10) {
        $advice[] = "🧥 Soğuk hava, mont almayı unutmayın";
    } elseif ($temp > 30) {
        $advice[] = "🌡️ Çok sıcak! Bol su için ve gölgelik alanlarda durun";
        $advice[] = "🧴 Güneş kremi kullanmayı unutmayın";
    } elseif ($temp > 25) {
        $advice[] = "☀️ Güzel bir gün, hafif giyin";
    }
    
    if (strpos($condition, 'rain') !== false) {
        $advice[] = "☔ Şemsiye almayı unutmayın";
        $advice[] = "🚗 Yolda dikkatli olun, kaygan zemin";
    }
    
    if (strpos($condition, 'snow') !== false) {
        $advice[] = "❄️ Kar zinciri gerekebilir";
        $advice[] = "🚶‍♂️ Yürürken dikkatli olun";
    }
    
    if ($wind_speed > 10) {
        $advice[] = "💨 Rüzgarlı hava, hafif eşyalar uçabilir";
    }
    
    if (strpos($condition, 'thunderstorm') !== false) {
        $advice[] = "⛈️ Fırtına var, mümkünse içeride kalın";
        $advice[] = "📱 Elektronik cihazları koruyun";
    }
    
    if (empty($advice)) {
        $advice[] = "🌤️ Güzel bir gün, dışarı çıkın!";
    }
    
    return $advice;
}
?>