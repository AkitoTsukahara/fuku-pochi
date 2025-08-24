<?php
/**
 * Laravel Health Check Script
 * Docker コンテナのヘルスチェック用
 */

// エラー表示を無効化
error_reporting(0);
ini_set('display_errors', 0);

// タイムアウト設定
set_time_limit(5);

$status = 'healthy';
$checks = [];

try {
    // 1. PHP基本チェック
    $checks['php'] = [
        'version' => PHP_VERSION,
        'status' => 'ok'
    ];

    // 2. Laravel存在チェック
    if (file_exists('/var/www/html/artisan')) {
        $checks['laravel'] = ['status' => 'ok'];
    } else {
        $checks['laravel'] = ['status' => 'error', 'message' => 'artisan not found'];
        $status = 'unhealthy';
    }

    // 3. 重要ディレクトリの書き込み権限チェック
    $writableDirectories = [
        '/var/www/html/storage/logs',
        '/var/www/html/storage/framework/cache',
        '/var/www/html/bootstrap/cache'
    ];

    $checks['permissions'] = ['status' => 'ok', 'directories' => []];
    
    foreach ($writableDirectories as $dir) {
        if (is_dir($dir) && is_writable($dir)) {
            $checks['permissions']['directories'][$dir] = 'writable';
        } else {
            $checks['permissions']['directories'][$dir] = 'not_writable';
            $checks['permissions']['status'] = 'warning';
        }
    }

    // 4. 必要なPHP拡張チェック
    $requiredExtensions = [
        'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 
        'xml', 'ctype', 'json', 'bcmath', 'zip', 'gd'
    ];

    $checks['extensions'] = ['status' => 'ok', 'loaded' => []];
    
    foreach ($requiredExtensions as $extension) {
        $loaded = extension_loaded($extension);
        $checks['extensions']['loaded'][$extension] = $loaded;
        
        if (!$loaded) {
            $checks['extensions']['status'] = 'error';
            $status = 'unhealthy';
        }
    }

    // 5. OPcacheチェック（存在する場合）
    if (function_exists('opcache_get_status')) {
        $opcacheStatus = opcache_get_status(false);
        $checks['opcache'] = [
            'status' => $opcacheStatus ? 'ok' : 'disabled',
            'enabled' => $opcacheStatus !== false
        ];
    }

    // 6. メモリ使用量チェック
    $memoryLimit = ini_get('memory_limit');
    $memoryUsage = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);
    
    $checks['memory'] = [
        'limit' => $memoryLimit,
        'usage' => round($memoryUsage / 1024 / 1024, 2) . 'MB',
        'peak' => round($memoryPeak / 1024 / 1024, 2) . 'MB',
        'status' => 'ok'
    ];

    // メモリ使用率が90%を超えている場合は警告
    $limitBytes = return_bytes($memoryLimit);
    if ($limitBytes > 0 && ($memoryUsage / $limitBytes) > 0.9) {
        $checks['memory']['status'] = 'warning';
    }

} catch (Exception $e) {
    $status = 'unhealthy';
    $checks['error'] = [
        'message' => $e->getMessage(),
        'status' => 'error'
    ];
}

// レスポンス
$response = [
    'status' => $status,
    'timestamp' => date('c'),
    'checks' => $checks
];

// HTTPステータスコードの設定
if ($status === 'healthy') {
    http_response_code(200);
} else {
    http_response_code(503);
}

// JSON形式で出力
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

/**
 * ini_get('memory_limit')の値をバイト数に変換
 */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
}

exit($status === 'healthy' ? 0 : 1);
?>