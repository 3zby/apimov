<?php

header("Content-Type: application/json; charset=UTF-8");

$cacheDir = __DIR__ . "/cache";

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$allSeriesCacheFile = $cacheDir . "/all_series.json";

$refresh = isset($_GET['refresh']);

// 🔥 حذف الكاش عند refresh
if ($refresh && file_exists($allSeriesCacheFile)) {
    unlink($allSeriesCacheFile);
}

// 🔥 استخدام الكاش
if (file_exists($allSeriesCacheFile) && !$refresh) {

    $cachedData = json_decode(file_get_contents($allSeriesCacheFile), true);

    echo json_encode([
        "status" => "success",
        "mode" => "full-cache",
        "source" => "all_series.json",
        "total_series" => count($cachedData),
        "result" => $cachedData
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    exit;
}

/* =========================
   🔥 تحويل الدومين تلقائي
========================= */
function fixImageDomain($url) {

    if (!$url) return null;

    return str_replace(
        "https://shahid4u.beer",
        "https://df.xo.je",
        $url
    );
}

function getHtml($url) {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => "Mozilla/5.0",
        CURLOPT_TIMEOUT => 10,
    ]);

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return null;

    return mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
}

function getBestImage($img) {

    if (!$img) return null;

    $imgUrl = $img->getAttribute("data-src");

    if (!$imgUrl) {
        $imgUrl = $img->getAttribute("data-original");
    }

    if (!$imgUrl) {
        $imgUrl = $img->getAttribute("src");
    }

    return $imgUrl;
}

function makeId($link) {
    return substr(preg_replace('/\D/', '', md5($link)), 0, 5);
}

function scrapeSeries($offset) {

    global $cacheDir, $refresh;

    $cacheFile = $cacheDir . "/page_$offset.json";

    if (!$refresh && file_exists($cacheFile)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    $html = getHtml("https://shahid4u.casa/series/?offset=" . $offset);

    if (!$html) return [];

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $xpath = new DOMXPath($dom);

    $items = $xpath->query("//div[contains(@class,'Small--Box')]");

    $data = [];

    if ($items) {
        foreach ($items as $item) {

            $cat = $xpath->query(".//li[contains(@class,'category')]", $item)->item(0);
            $category = $cat ? trim($cat->textContent) : null;

            $a = $xpath->query(".//a", $item)->item(0);
            $img = $xpath->query(".//img", $item)->item(0);
            $title = $xpath->query(".//h2", $item)->item(0);
            $desc = $xpath->query(".//p", $item)->item(0);

            $link = $a?->getAttribute("href");

            $image = fixImageDomain(getBestImage($img));

            $data[] = [
                "id" => makeId($link),
                "title" => $title?->textContent,
                "description" => $desc?->textContent,
                "category" => $category,
                "image" => $image,
                "link" => $link,
            ];
        }
    }

    file_put_contents(
        $cacheFile,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );

    return $data;
}

$all = [];
$allSeries = [];

$batchSize = 50;

for ($start = 1; $start <= 297; $start += $batchSize) {

    $batch = [];

    for ($i = $start; $i < $start + $batchSize; $i++) {

        $page = scrapeSeries($i);

        if (!empty($page)) {

            $batch[] = [
                "page" => $i,
                "data" => $page
            ];

            foreach ($page as $series) {
                $allSeries[] = $series;
            }
        }
    }

    if (!empty($batch)) {
        $all[] = [
            "batch" => $start . "-" . ($start + $batchSize - 1),
            "pages" => $batch
        ];
    }
}

file_put_contents(
    $allSeriesCacheFile,
    json_encode($allSeries, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

echo json_encode([
    "status" => "success",
    "mode" => $refresh ? "fresh_refresh" : "fresh_build",
    "batches_loaded" => count($all),
    "total_series" => count($allSeries),
    "total_pages" => 297,
    "refresh" => $refresh,
    "result" => $all
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);