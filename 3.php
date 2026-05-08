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

// ================== INPUT SEARCH ==================

$q = isset($_GET['q']) ? trim($_GET['q']) : "";

// ================== SMART FILTER ==================

$filtered = array_filter($data, function ($item) use ($q) {

    $title = $item['title'] ?? '';
    $category = $item['category'] ?? '';

    // 🔥 شرط التصنيف التركي الحقيقي
    $isTurkishCategory = ($category === "مسلسلات تركية");

    // 🔥 إذا التصنيف فاضي نحاول نستنتج من الاسم
    $isMaybeTurkish = false;

    if (empty($category) || $category === null) {

        // كلمات تدل على تركي
        $keywords = [
            "تركي",
            "turk",
            "اشرف",
            "قيامة",
            "العثماني",
            "حريم",
            "الحب",
            "اسطنبول"
        ];

        foreach ($keywords as $word) {
            if (mb_stripos($title, $word) !== false) {
                $isMaybeTurkish = true;
                break;
            }
        }
    }

    // ================== SEARCH FILTER ==================

    $matchSearch = true;

    if ($q !== "") {
        $matchSearch = (mb_stripos($title, $q) !== false);
    }

    // ================== FINAL CONDITION ==================

    return ($isTurkishCategory || $isMaybeTurkish) && $matchSearch;
});

// إعادة ترتيب
$filtered = array_values($filtered);

// ================== OUTPUT ==================

echo json_encode([
    "status" => "success",
    "category" => "مسلسلات تركية (ذكي)",
    "search" => $q,
    "total" => count($filtered),
    "result" => $filtered
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>