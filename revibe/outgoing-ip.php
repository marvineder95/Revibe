<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== Revibe Server IP-Informationen ===\n\n";

$outgoingIp = null;

// Mögliche Dienste, um die ausgehende IPv4-Adresse zu ermitteln
$sources = [
    'https://api.ipify.org' => true,
    'https://ifconfig.me/ip' => true,
    'https://checkip.amazonaws.com' => true,
    'https://icanhazip.com' => true,
];

foreach ($sources as $url => $expectPlain) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'Revibe-IP-Check/1.0',
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $result = @file_get_contents($url, false, $ctx);
    if ($result !== false) {
        $ip = trim($result);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $outgoingIp = $ip;
            echo "Ausgehende IP (via " . $url . "): " . $outgoingIp . "\n";
            break;
        }
    }
}

if (!$outgoingIp) {
    echo "Konnte ausgehende IP nicht ermitteln (möglicherweise ist allow_url_fopen deaktiviert).\n";
}

echo "\n--- Weitere Server-Informationen ---\n";
echo "SERVER_ADDR:    " . ($_SERVER['SERVER_ADDR'] ?? 'nicht verfügbar') . "\n";
echo "SERVER_NAME:    " . ($_SERVER['SERVER_NAME'] ?? 'nicht verfügbar') . "\n";
echo "REMOTE_ADDR:    " . ($_SERVER['REMOTE_ADDR'] ?? 'nicht verfügbar') . "\n";
echo "HTTP_HOST:      " . ($_SERVER['HTTP_HOST'] ?? 'nicht verfügbar') . "\n";
echo "allow_url_fopen:" . (ini_get('allow_url_fopen') ? 'ja' : 'nein') . "\n";
