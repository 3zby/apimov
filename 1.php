<?php

header("Content-Type: application/json; charset=UTF-8");

$cacheFile = __DIR__ . "/cache/all_series.json";

// ================== CHECK FILE ==================

if (!file_exists($cacheFile)) {

    echo json_encode([
        "status" => "error",
        "message" => "Cache file not found"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    exit;
}

// ================== LOAD DATA ==================

$data = json_decode(file_get_contents($cacheFile), true);

if (!is_array($data)) {

    echo json_encode([
        "status" => "error",
        "message" => "Invalid cache data"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    exit;
}

// ================== OUTPUT ALL ==================

echo json_encode([
    "status" => "success",
    "category" => "all series",
    "total" => count($data),
    "result" => array_values($data)
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>