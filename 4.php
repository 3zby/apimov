<?php

header("Content-Type: text/html; charset=UTF-8");

$file = __DIR__ . "/cache/all_series.json";

$data = [];

if (file_exists($file)) {
    $tmp = json_decode(file_get_contents($file), true);
    if (is_array($tmp)) {
        $data = $tmp;
    }
}

/* =========================
   🔥 API (مع فلترة التصنيف)
========================= */
if (isset($_GET['api'])) {

    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 40));
    $cat   = $_GET['cat'] ?? 'all';

    $filtered = $data;

    // 🔥 فلترة التصنيفات
    if ($cat !== 'all') {
        $filtered = array_values(array_filter($filtered, function($item) use ($cat) {
            return isset($item['category']) && $item['category'] === $cat;
        }));
    }

    $total = count($filtered);
    $offset = ($page - 1) * $limit;

    $items = array_slice($filtered, $offset, $limit);

    echo json_encode([
        "status" => "success",
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "has_more" => ($offset + $limit < $total),
        "result" => $items
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =========================
   استخراج التصنيفات
========================= */
$categories = [];

foreach ($data as $item) {
    if (!empty($item['category'])) {
        $categories[$item['category']] = true;
    }
}

$categories = array_keys($categories);

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Series Lazy</title>

<style>
body{
    margin:0;
    font-family:Arial;
    background:#0f0f0f;
    color:#fff;
}

/* ===== HEADER ===== */
header{
    padding:10px;
    background:#111;
    position:sticky;
    top:0;
    z-index:10;
}

/* ===== FILTERS ===== */
.filters{
    display:flex;
    gap:8px;
    overflow-x:auto;
    padding:10px;
    background:#0d0d0d;
}

.filters button{
    background:#222;
    color:#fff;
    border:none;
    padding:6px 10px;
    border-radius:20px;
    font-size:12px;
    cursor:pointer;
    white-space:nowrap;
}

.filters button.active{
    background:#ff3d00;
}

/* ===== GRID ===== */
.container{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(120px,1fr));
    gap:8px;
    padding:10px;
}

/* ===== CARD ===== */
.card{
    background:#1c1c1c;
    border-radius:10px;
    overflow:hidden;
}

.card img{
    width:100%;
    height:160px;
    object-fit:cover;
}

.card h4{
    font-size:12px;
    margin:6px;
    height:38px;
    overflow:hidden;
}

/* ===== LOADER ===== */
.loader{
    text-align:center;
    padding:20px;
    color:#aaa;
    font-size:13px;
}
</style>
</head>

<body>

<header>
    📺 المسلسلات: <?= count($data) ?>
</header>

<!-- 🔥 التصنيفات -->
<div class="filters">
    <button class="active" onclick="setCat('all', this)">الكل</button>

    <?php foreach($categories as $cat): ?>
        <button onclick="setCat('<?= $cat ?>', this)">
            <?= $cat ?>
        </button>
    <?php endforeach; ?>
</div>

<div class="container" id="list"></div>
<div class="loader" id="loader">جاري التحميل...</div>

<script>

let page = 1;
let loading = false;
let finished = false;
let currentCat = 'all';

const container = document.getElementById("list");
const loader = document.getElementById("loader");

/* =========================
   تغيير التصنيف
========================= */
function setCat(cat, btn){

    document.querySelectorAll(".filters button")
        .forEach(b => b.classList.remove("active"));

    btn.classList.add("active");

    currentCat = cat;
    page = 1;
    finished = false;
    container.innerHTML = "";

    loadMore();
}

/* =========================
   تحميل البيانات
========================= */
async function loadMore(){

    if(loading || finished) return;

    loading = true;

    let res = await fetch(`?api=1&page=${page}&limit=20&cat=${currentCat}`);
    let json = await res.json();

    if(!json.result || json.result.length === 0){
        loader.innerHTML = "انتهى المحتوى";
        finished = true;
        loading = false;
        return;
    }

    json.result.forEach(item => {

        container.insertAdjacentHTML("beforeend", `
        <div class="card">
            <img loading="lazy" src="${item.image || ''}">
            <h4>${item.title || ''}</h4>
        </div>`);
    });

    if(json.has_more === false){
        loader.innerHTML = "انتهى المحتوى";
        finished = true;
    }

    page++;
    loading = false;
}

/* =========================
   Scroll
========================= */
window.addEventListener("scroll", () => {

    if(window.innerHeight + window.scrollY >= document.body.offsetHeight - 200){
        loadMore();
    }
});

/* أول تحميل */
loadMore();

</script>

</body>
</html>