<?php
/**
 * Telegram API Forwarder in PHP
 * Supports all HTTP methods, unlimited limits, and logs requests/responses.
 * 
 * Author: Max Base
 * Repo: https://github.com/BaseMax/telegram-api-proxy-php
 */

// === Configuration ===
define('LOG_FILE', __DIR__ . '/telegram_forwarder.log');
define('MAX_LOG_FILE_SIZE_MB', 10);

// === Set unlimited limits ===
set_time_limit(0);
ini_set('memory_limit', '-1');
ini_set('post_max_size', '2048M');
ini_set('upload_max_filesize', '2048M');
ini_set('max_input_time', '-1');
ini_set('max_execution_time', '0');

function log_message(string $msg): void {
    $date = date('Y-m-d H:i:s');
    $entry = "[$date] $msg\n";
    
    if (file_exists(LOG_FILE) && filesize(LOG_FILE) > MAX_LOG_FILE_SIZE_MB * 1024 * 1024) {
        rename(LOG_FILE, LOG_FILE . '.' . time() . '.bak');
    }
    
    file_put_contents(LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $requestUri = $_SERVER['REQUEST_URI'];
    $headers = getallheaders();

    unset($headers['Host'], $headers['host']);

    $curlHeaders = [];
    foreach ($headers as $key => $value) {
        $curlHeaders[] = $key . ': ' . $value;
    }

    $body = file_get_contents('php://input');

    $telegramUrl = 'https://api.telegram.org' . $requestUri;

    log_message("Incoming Request: $method $requestUri");
    log_message("Request Headers: " . json_encode($headers));
    log_message("Request Body: " . ($body ?: '[empty]'));

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
        $curlErr = curl_error($ch);
        curl_close($ch);

        // Log error
        log_message("cURL Error: $curlErr");

        // Return error to client
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Telegram API Forwarder Error: $curlErr";
        exit;
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    $rawHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);

    $responseHeaders = [];
    foreach (explode("\r\n", $rawHeaders) as $headerLine) {
        if (strpos($headerLine, ':') !== false) {
            list($name, $value) = explode(':', $headerLine, 2);
            $responseHeaders[trim($name)] = trim($value);
        }
    }

    curl_close($ch);

    log_message("Telegram Response Status: $statusCode");
    log_message("Response Headers: " . json_encode($responseHeaders));
    log_message("Response Body: " . ($responseBody ?: '[empty]'));

    http_response_code($statusCode);

    if (isset($responseHeaders['Content-Type'])) {
        header('Content-Type: ' . $responseHeaders['Content-Type']);
    } else {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo $responseBody;

} catch (Throwable $e) {
    log_message("Unexpected Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Telegram API Forwarder Unexpected Error';
}
