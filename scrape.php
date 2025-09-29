<?php
header('Content-Type: application/json; charset=utf-8');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$base = "https://quotes.toscrape.com";
$url = rtrim($base, '/') . '/page/' . $page . '/';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'DemoParser/1.0',
    CURLOPT_TIMEOUT => 15,
]);

$html = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err || $code >= 400 || !$html) {
    echo json_encode(['ok'=>false, 'error'=>'fetch_failed','details'=>$err,'http_code'=>$code], JSON_UNESCAPED_UNICODE);
    exit;
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
$xpath = new DOMXPath($dom);

$quotes = [];
$quoteNodes = $xpath->query('//div[@class="quote"]');
foreach ($quoteNodes as $q) {
    $textNode = $xpath->query('.//span[@class="text"]', $q)->item(0);
    $authorNode = $xpath->query('.//small[@class="author"]', $q)->item(0);
    $tagNodes = $xpath->query('.//div[@class="tags"]/a[@class="tag"]', $q);

    $tags = [];
    foreach ($tagNodes as $tag) {
        $tags[] = trim($tag->textContent);
    }

    $quotes[] = [
        'text' => trim($textNode?->textContent ?? ''),
        'author' => trim($authorNode?->textContent ?? ''),
        'tags' => $tags
    ];
}

$nextNode = $xpath->query('//li[@class="next"]/a')->item(0);
$has_next = $nextNode ? true : false;

echo json_encode([
    'ok' => true,
    'page' => $page,
    'has_next' => $has_next,
    'site' => $base,
    'quotes' => $quotes
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
