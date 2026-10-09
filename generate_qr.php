<?php
// generate_qr.php
// Usage: <img src="generate_qr.php?token=ABC">
require_once 'config.php';

$data = $_GET['data'] ?? '';
if ($data === '') { http_response_code(400); exit; }

$size = 300;
$apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);

// Proxy the request so the URL doesn't expose the token
header('Content-Type: image/png');
readfile($apiUrl);

$qr = new \Endroid\QrCode\QrCode($data);
header('Content-Type: '.$qr->getContentType());
echo $qr->writeString();