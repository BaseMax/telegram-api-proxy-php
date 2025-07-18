<?php
// 
// MAX bASE
// https://github.com/BaseMax/telegram-api-proxy-php
// Telegram API Forwarder in PHP - supports all HTTP methods and unlimited limits
// 

set_time_limit(0);
ini_set('memory_limit', '-1');
ini_set('post_max_size', '2048M');
ini_set('upload_max_filesize', '2048M');
ini_set('max_input_time', '-1');
ini_set('max_execution_time', '0');

$method = $_SERVER['REQUEST_METHOD'];

$requestUri = $_SERVER['REQUEST_URI'];

$headers = getallheaders();

unset($headers['Host']);
unset($headers['host']);

$curlHeaders = [];
foreach ($headers as $key => $value) {
    $curlHeaders[] = $key . ': ' . $value;
}

$body = file_get_contents('php://input');

$telegramUrl = 'https://api.telegram.org' . $requestUri;

$ch = curl_init($telegramUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
curl_setopt($ch, CURLOPT_HEADER, true);

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Telegram API Forwarder Error: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

$rawHeaders = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

$responseHeaders = [];
foreach (explode("\r\n", $rawHeaders) as $headerLine) {
    if (strpos($headerLine, ':') !== false) {
        list($name, $value) = explode(':', $headerLine, 2);
        $responseHeaders[trim($name)] = trim($value);
    }
}

curl_close($ch);

http_response_code($statusCode);

if (isset($responseHeaders['Content-Type'])) {
    header('Content-Type: ' . $responseHeaders['Content-Type']);
} else {
    header('Content-Type: application/json; charset=utf-8');
}

echo $body;
