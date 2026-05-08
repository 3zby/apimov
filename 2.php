<?php

header("Content-Type: application/json; charset=UTF-8");

$cacheDir = __DIR__ . "/cache";
$allSeriesCacheFile = $cacheDir . "/all_series.json";

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

/* =========================
   🔥 ID ثابت
========================= */
function makeId($link) {
    return substr(preg_replace('/\D/', '', md5($link)), 0, 5);
}

/* =========================
   🔥 جلب الدومين الحالي تلقائي
========================= */
function getBaseDomain() {

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";

    return $protocol . "://" . $_SERVER['HTTP_HOST'];
}

/* =========================
   🔥 تحويل الصور إلى دومينك تلقائي
========================= */
function fixImageDomain($url) {

    if (!$url) return null;

    $base = getBaseDomain();

    // نشيل أي دومين قديم ونخلي المسار فقط
    $path = parse_url($url, PHP_URL_PATH);

    return $base . $path;
}

/* =========================
   جلب HTML
========================= */
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

    return $html ? mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') : null;
}

/* =========================
   أفضل صورة
========================= */
function getBestImage($img) {

    if (!$img) return null;

    return $img->getAttribute("data-src")
        ?: $img->getAttribute("data-original")
        ?: $img->getAttribute("src");
}

/* =========================
   تحميل الكاش
========================= */
$allSeries = [];

if (file_exists($allSeriesCacheFile)) {

    $existing = json_decode(file_get_contents($allSeriesCacheFile), true);

    if (is_array($existing)) {

        foreach ($existing as $item) {

            if (!empty($item['link'])) {
                $allSeries[$item['link']] = $item;
            }
        }
    }
}

/* =========================
   scrape صفحة 1
========================= */
function scrapePage1() {

    $html = getHtml("https://shahid4u.casa/series/?offset=1");

    if (!$html) return [];

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $xpath = new DOMXPath($dom);

    $items = $xpath->query("//div[contains(@class,'Small--Box')]");

    $data = [];

    foreach ($items as $item) {

        $a = $xpath->query(".//a", $item)->item(0);
        $img = $xpath->query(".//img", $item)->item(0);
        $title = $xpath->query(".//h2", $item)->item(0);
        $desc = $xpath->query(".//p", $item)->item(0);
        $cat = $xpath->query(".//li[contains(@class,'category')]", $item)->item(0);

        $link = $a?->getAttribute("href");

        if (!$link) continue;

        $data[] = [
            "id" => makeId($link),
            "title" => trim($title?->textContent),
            "description" => trim($desc?->textContent),
            "category" => $cat ? trim($cat->textContent) : null,
            "image" => fixImageDomain(getBestImage($img)), // 🔥 مهم
            "link" => $link,
        ];
    }

    return $data;
}

/* =========================
   🔥 REFRESH LOGIC
========================= */

$page1 = scrapePage1();

$newItems = 0;
$updated = 0;

$newList = [];
$map = $allSeries;

foreach ($page1 as $series) {

    $link = $series['link'];

    if (!isset($map[$link])) {

        $newList[] = $series;
        $newItems++;

    } else {

        if ($map[$link] != $series) {
            $map[$link] = $series;
            $updated++;
        }

        $newList[] = $map[$link];
        unset($map[$link]);
    }
}

/* القديم */
foreach ($map as $old) {
    $newList[] = $old;
}

/* =========================
   حفظ
========================= */
file_put_contents(
    $allSeriesCacheFile,
    json_encode($newList, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

/* =========================
   output
========================= */
echo json_encode([
    "status" => "success",
    "mode" => "refresh_api",
    "new_items" => $newItems,
    "updated_items" => $updated,
    "total" => count($newList)
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);