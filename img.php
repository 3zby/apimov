<?php

// منع أي إخراج غير الصورة
header("Access-Control-Allow-Origin: *");

$file = $_GET['file'] ?? '';

if (!$file) {
    http_response_code(400);
    exit("Missing file parameter");
}

// تنظيف المسار لمنع أي اختراق بسيط
$file = filter_var($file, FILTER_SANITIZE_URL);

// الدومين الأساسي
$baseUrl = "https://shahid4u.casa";

// بناء الرابط النهائي
$url = $baseUrl . $file;

// جلب الصورة
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => "Mozilla/5.0",
    CURLOPT_TIMEOUT => 15,
]);

$image = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// لو فشل الطلب
if ($httpCode != 200 || !$image) {
    http_response_code(404);
    exit("Image not found");
}

// تحديد نوع المحتوى
if ($contentType) {
    header("Content-Type: " . $contentType);
} else {
    header("Content-Type: image/jpeg");
}

// كاش بسيط للمتصفح
header("Cache-Control: public, max-age=86400");

// عرض الصورة
echo $image;