<?php
include("includes/db.php");
require_once("includes/config.php");

$all_books   = $conn->query("SELECT * FROM book ORDER BY category ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$by_cat      = [];
foreach ($all_books as $b) $by_cat[$b['category']][] = $b;
$categories  = array_keys($by_cat);
$total_books = count($all_books);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Books Store — MEDDEMY</title>

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* ==================== GLOBAL SETUP ==================== */
:root {
    /* 🎨 Color Scheme (MEDDEMY Brand) */
    --golden: #FFD700;
    --golden-dark: #E6C200;
    --golden-light: #FFED4E;
    --black: #000000;
    --black-light: #1a1a1a;
    --black-card: #1e1e1e;
    --white: #ffffff;
    --gray: #6c757d;
    --gray-light: #adb5bd;
    --green: #22c55e;
    --whatsapp: #25D366;
    
    /* 🌈 Gradients */
    --gradient-gold: linear-gradient(135deg, var(--golden), var(--golden-light));
    --gradient-dark: linear-gradient(135deg, var(--black) 0%, var(--black-light) 100%);
    --gradient-card: linear-gradient(145deg, var(--black-card), #252525);
    --gradient-whatsapp: linear-gradient(135deg, var(--whatsapp), #128C7E);
    
    /* 🌓 Shadows */
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.2);
    --shadow-md: 0 4px 20px rgba(0,0,0,0.3);
    --shadow-lg: 0 8px 40px rgba(0,0,0,0.4);
    --shadow-gold: 0 5px 25px rgba(255,215,0,0.35);
    --shadow-whatsapp: 0 4px 20px rgba(37,211,102,0.4);
    
    /* 🔄 Transitions */
    --transition: all 0.3s ease;
    --transition-fast: all 0.2s ease;
    
    /* 📐 Border Radius */
    --radius-sm: 8px;
    --radius-md: 16px;
    --radius-lg: 24px;
    --radius-full: 50px;
    
    /* 📏 Spacing */
    --space-xs: 0.25rem;
    --space-sm: 0.5rem;
    --space-md: 1rem;
    --space-lg: 1.5rem;
    --space-xl: 2rem;
    --space-xxl: 3rem;
    
    /* 🔤 Typography */
    --font-primary: 'Poppins', sans-serif;
    
    /* 📚 Z-Index */
    --z-fixed: 1030;
}

*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
    font-size: 16px;
}

body {
    font-family: var(--font-primary);
    background: var(--gradient-dark);
    color: var(--white);
    line-height: 1.6;
    min-height: 100vh;
    overflow-x: hidden;
}

::selection {
    background: rgba(255,215,0,0.2);
    color: var(--black);
}

::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: var(--black-light); }
::-webkit-scrollbar-thumb { background: var(--golden); border-radius: 4px; }

a { text-decoration: none; color: inherit; transition: var(--transition); }
a:hover { color: var(--golden-light); }

/* 🧭 Utility Classes */
.text-golden { color: var(--golden) !important; }
.text-white { color: var(--white) !important; }
.text-green { color: var(--green) !important; }
.text-whatsapp { color: var(--whatsapp) !important; }
.text-center { text-align: center !important; }
.fw-bold { font-weight: 700 !important; }
.fw-semibold { font-weight: 600 !important; }
.d-flex { display: flex !important; }
.align-items-center { align-items: center !important; }
.justify-content-between { justify-content: space-between !important; }
.justify-content-center { justify-content: center !important; }
.gap-sm { gap: var(--space-sm) !important; }
.gap-md { gap: var(--space-md) !important; }
.gap-lg { gap: var(--space-lg) !important; }
.w-100 { width: 100% !important; }

.container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--space-lg);
}

/* ==================== NAVBAR ==================== */
.nav {
    position: sticky;
    top: 0;
    z-index: var(--z-fixed);
    background: rgba(0,0,0,0.95);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,215,0,0.1);
    padding: var(--space-sm) 0;
    transition: var(--transition);
}

.nav-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-md);
}

.nav-brand {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--white);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.nav-brand::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--golden);
}

.nav-brand:hover { color: var(--golden); }

.nav-links {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.nav-link {
    padding: var(--space-sm) var(--space-md);
    border-radius: var(--radius-full);
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--gray-light);
    transition: var(--transition);
    position: relative;
}

.nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background: var(--golden);
    transition: var(--transition);
    transform: translateX(-50%);
    border-radius: 2px;
}

.nav-link:hover::after,
.nav-link.active::after {
    width: 70%;
}

.nav-link:hover,
.nav-link.active {
    color: var(--golden) !important;
}

.nav-cta {
    background: var(--gradient-gold);
    color: var(--black) !important;
    font-weight: 600 !important;
    box-shadow: var(--shadow-gold);
}

.nav-cta:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-gold-hover);
    background: var(--white);
}

.nav-toggle {
    display: none;
    background: none;
    border: 2px solid var(--golden);
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-sm);
    color: var(--golden);
    font-size: 1.2rem;
    cursor: pointer;
}

/* Mobile Menu */
@media (max-width: 768px) {
    .nav-toggle { display: block; }
    .nav-links {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.98);
        flex-direction: column;
        padding: var(--space-lg);
        gap: var(--space-sm);
        transform: translateY(-150%);
        transition: var(--transition);
        border-bottom: 1px solid rgba(255,215,0,0.1);
    }
    .nav-links.active { transform: translateY(0); }
    .nav-link {
        width: 100%;
        text-align: center;
        padding: var(--space-md) 0;
        border-bottom: 1px solid rgba(255,215,0,0.1);
    }
    .nav-cta { width: 100%; justify-content: center; }
}

/* ==================== HERO SECTION ==================== */
.hero {
    padding: 100px 0 var(--space-xl);
    background: radial-gradient(ellipse at top, rgba(255,215,0,0.08), transparent 60%);
    position: relative;
    overflow: hidden;
    text-align: center;
}

.hero::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255,215,0,0.1), transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(20px, -20px); }
}

.hero-tag {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    background: rgba(255,215,0,0.15);
    border: 1px solid rgba(255,215,0,0.3);
    color: var(--golden);
    font-size: 0.75rem;
    font-weight: 700;
    padding: var(--space-xs) var(--space-md);
    border-radius: var(--radius-full);
    margin-bottom: var(--space-md);
    text-transform: uppercase;
    letter-spacing: 1px;
    animation: fadeInUp 0.6s ease;
}

.hero h1 {
    font-size: clamp(2rem, 6vw, 3.5rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: var(--space-sm);
    animation: fadeInUp 0.6s 0.1s ease both;
}

.hero h1 em {
    color: var(--golden);
    font-style: italic;
}

.hero-sub {
    font-size: 1.1rem;
    color: rgba(255,255,255,0.85);
    max-width: 550px;
    margin: 0 auto var(--space-xl);
    animation: fadeInUp 0.6s 0.2s ease both;
}

.hero-nums {
    display: flex;
    justify-content: center;
    gap: var(--space-lg);
    flex-wrap: wrap;
    animation: fadeInUp 0.6s 0.3s ease both;
}

.hero-num {
    text-align: center;
    padding: 0 var(--space-lg);
    border-right: 1px solid rgba(255,255,255,0.1);
}

.hero-num:last-child { border-right: none; }

.hero-num-val {
    font-size: clamp(1.5rem, 4vw, 2rem);
    font-weight: 800;
    color: var(--golden);
    line-height: 1;
}

.hero-num-lbl {
    font-size: 0.75rem;
    color: var(--gray-light);
    margin-top: var(--space-xs);
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

/* ==================== SEARCH BAR ==================== */
.search-wrap {
    max-width: 600px;
    margin: 0 auto var(--space-xl);
    padding: 0 var(--space-lg);
    animation: fadeInUp 0.6s 0.4s ease both;
}

.search-box {
    display: flex;
    align-items: center;
    background: var(--black-card);
    border: 1px solid rgba(255,215,0,0.2);
    border-radius: var(--radius-full);
    overflow: hidden;
    transition: var(--transition);
}

.search-box:focus-within {
    border-color: var(--golden);
    box-shadow: 0 0 0 3px rgba(255,215,0,0.15);
}

.search-box i {
    padding: 0 var(--space-md);
    color: var(--gray);
    font-size: 0.9rem;
}

.search-inp {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    color: var(--white);
    font-family: var(--font-primary);
    font-size: 0.95rem;
    padding: var(--space-md) 0;
}

.search-inp::placeholder { color: var(--gray); }

.search-x {
    padding: 0 var(--space-md);
    color: var(--gray);
    cursor: pointer;
    font-size: 0.8rem;
    display: none;
    transition: var(--transition);
}

.search-x:hover { color: var(--golden); }

/* ==================== CATEGORY PILLS ==================== */
.cat-row {
    padding: 0 var(--space-lg);
    margin-bottom: var(--space-xl);
    overflow-x: auto;
    scrollbar-width: none;
}

.cat-row::-webkit-scrollbar { display: none; }

.cat-pills {
    display: flex;
    gap: var(--space-xs);
    width: max-content;
    margin: 0 auto;
    padding: var(--space-xs);
    background: var(--black-card);
    border: 1px solid rgba(255,215,0,0.1);
    border-radius: var(--radius-full);
}

.cat-pill {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    padding: var(--space-sm) var(--space-lg);
    cursor: pointer;
    background: transparent;
    border: none;
    border-radius: var(--radius-full);
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--gray-light);
    transition: var(--transition);
    white-space: nowrap;
}

.cat-pill:hover {
    color: var(--white);
    background: rgba(255,215,0,0.1);
}

.cat-pill.active {
    background: var(--gradient-gold);
    color: var(--black);
    font-weight: 700;
}

.pill-count {
    background: rgba(0,0,0,0.2);
    padding: 2px 10px;
    border-radius: var(--radius-full);
    font-size: 0.7rem;
    font-weight: 600;
}

.cat-pill.active .pill-count {
    background: rgba(0,0,0,0.3);
    color: var(--black);
}

/* ==================== SECTION HEADER ==================== */
.section {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--space-lg) var(--space-xxl);
}

.sec-label {
    display: flex;
    align-items: center;
    gap: var(--space-lg);
    margin-bottom: var(--space-lg);
}

.sec-line {
    flex: 1;
    height: 1px;
    background: rgba(255,215,0,0.15);
}

.sec-title {
    font-size: 1.5rem;
    font-weight: 700;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.sec-title em {
    color: var(--golden);
    font-style: italic;
}

.sec-count {
    font-size: 0.75rem;
    color: var(--gray-light);
    letter-spacing: 0.5px;
    text-transform: uppercase;
    border: 1px solid rgba(255,215,0,0.2);
    padding: 3px 12px;
    border-radius: var(--radius-full);
}

/* ==================== BOOKS GRID ==================== */
.books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: var(--space-lg);
}

/* ==================== BOOK CARD ==================== */
.book-card {
    display: flex;
    flex-direction: column;
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: var(--gradient-card);
    border: 1px solid rgba(255,215,0,0.15);
    transition: var(--transition);
    animation: fadeInUp 0.5s ease;
    position: relative;
    text-decoration: none;
    color: inherit;
}

.book-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--gradient-gold);
    opacity: 0;
    transition: var(--transition);
}

.book-card:hover {
    transform: translateY(-8px);
    border-color: rgba(255,215,0,0.4);
    box-shadow: var(--shadow-gold);
}

.book-card:hover::before { opacity: 1; }

/* Discount Badge */
.disc-badge {
    position: absolute;
    top: var(--space-sm);
    right: var(--space-sm);
    z-index: 3;
    background: var(--gradient-gold);
    color: var(--black);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: var(--radius-full);
    box-shadow: var(--shadow-gold);
}

/* Book Image */
.book-img {
    aspect-ratio: 3/4;
    position: relative;
    overflow: hidden;
    background: var(--black-light);
}

.book-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.6s ease;
}

.book-card:hover .book-img img {
    transform: scale(1.08);
}

.book-no-img {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    color: var(--gray);
    background: var(--black-card);
}

.book-no-img i {
    font-size: 2.5rem;
    opacity: 0.4;
}

.book-no-img span {
    font-size: 0.7rem;
    opacity: 0.5;
    letter-spacing: 0.5px;
}

/* Image Sheen Effect */
.img-sheen {
    position: absolute;
    inset: 0;
    background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.08) 50%, transparent 60%);
    background-size: 200% 100%;
    transition: background-position 0.7s ease;
    pointer-events: none;
    opacity: 0;
}

.book-card:hover .img-sheen {
    background-position: 200% 0;
    opacity: 1;
}

/* Book Info */
.book-info {
    padding: var(--space-md);
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
}

.b-cat {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--golden);
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.b-title {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.b-author {
    font-size: 0.8rem;
    color: var(--gray-light);
}

/* Price Row */
.b-price-row {
    margin-top: auto;
    padding-top: var(--space-md);
    border-top: 1px solid rgba(255,215,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.b-price-disc {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--golden);
}

.b-price-real {
    font-size: 0.75rem;
    color: var(--gray);
    text-decoration: line-through;
}

.b-price-only {
    font-size: 1.1rem;
    font-weight: 700;
}

/* WhatsApp Dot */
.wa-dot {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(37,211,102,0.12);
    border: 1px solid rgba(37,211,102,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--whatsapp);
    font-size: 0.9rem;
    transition: var(--transition);
    flex-shrink: 0;
}

.book-card:hover .wa-dot {
    background: var(--gradient-whatsapp);
    color: var(--white);
    box-shadow: var(--shadow-whatsapp);
    transform: scale(1.1);
}

/* ==================== EMPTY STATE ==================== */
.empty-st {
    text-align: center;
    padding: var(--space-xxl) var(--space-lg);
    grid-column: 1 / -1;
    animation: fadeInUp 0.5s ease;
}

.empty-st i {
    font-size: 3rem;
    color: var(--gray);
    display: block;
    margin-bottom: var(--space-md);
    opacity: 0.4;
}

.empty-st p {
    color: var(--gray-light);
    font-size: 1rem;
}

/* ==================== FOOTER ==================== */
.footer {
    padding: var(--space-xl) 0;
    border-top: 1px solid rgba(255,215,0,0.1);
    text-align: center;
    font-size: 0.85rem;
    color: var(--gray);
}

.footer a { color: var(--golden); }

/* ==================== ANIMATIONS ==================== */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-in { animation: fadeInUp 0.4s ease forwards; }

/* ==================== RESPONSIVE BREAKPOINTS ==================== */

/* Tablet & Below (≤900px) */
@media (max-width: 900px) {
    .books-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: var(--space-md);
    }
    .hero { padding: 80px 0 var(--space-lg); }
    .hero-nums { gap: var(--space-md); }
    .hero-num { padding: 0 var(--space-md); }
}

/* Mobile (≤768px) */
@media (max-width: 768px) {
    .hero h1 { font-size: 1.8rem; }
    .hero-sub { font-size: 1rem; }
    .hero-nums { flex-direction: column; gap: var(--space-sm); }
    .hero-num { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.1); padding: var(--space-sm) 0; }
    .hero-num:last-child { border-bottom: none; }
    
    .books-grid { grid-template-columns: repeat(2, 1fr); gap: var(--space-md); }
    .book-info { padding: var(--space-sm); }
    .b-title { font-size: 0.9rem; }
    .b-price-row { padding-top: var(--space-sm); }
    
    .cat-pills { padding: var(--space-xs); }
    .cat-pill { padding: var(--space-xs) var(--space-md); font-size: 0.8rem; }
    
    .section { padding: 0 var(--space-lg) var(--space-xl); }
    .sec-title { font-size: 1.3rem; }
}

/* Small Mobile (≤480px) */
@media (max-width: 480px) {
    .books-grid { grid-template-columns: 1fr; max-width: 320px; margin: 0 auto; }
    .book-img { aspect-ratio: 4/5; }
    .b-title { font-size: 1rem; }
    .b-price-disc, .b-price-only { font-size: 1rem; }
    
    .hero h1 { font-size: 1.5rem; }
    .hero-tag { font-size: 0.7rem; padding: 4px 14px; }
    
    .nav-brand span { display: none; }
    .search-inp { font-size: 0.9rem; }
}

/* Large Screens (≥1400px) */
@media (min-width: 1400px) {
    .books-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
}
</style>
</head>
<body>

<!-- ✅ Navbar -->
<nav class="nav">
    <div class="container nav-inner">
        <a href="index.php" class="nav-brand">
            <img src="assets/images/logo44.png" alt="MEDDEMY" style="height:32px;" onerror="this.style.display='none'">
            <span>MEDDEMY</span>
        </a>
        
        <button class="nav-toggle" id="navToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="nav-links" id="navbarNav">
            <a href="index.php" class="nav-link">Home</a>
            <a href="announcements.php" class="nav-link">Updates</a>
            <a href="students/materials.php" class="nav-link">Free Notes</a>
            <a href="books.php" class="nav-link active">Books</a>
            <a href="login.php" class="nav-link nav-cta">Login</a>
        </div>
    </div>
</nav>

<!-- ✅ Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-tag">
            <i class="fas fa-book-open"></i>
            Official Book Store
        </div>
        <h1>Study Books &amp; <em>Resources</em></h1>
        <p class="hero-sub">
            Curated for AFNS, NCAT, BSN and all medical entrance exams.<br>
            Click any book to view details and order on WhatsApp.
        </p>
        <div class="hero-nums">
            <div class="hero-num">
                <div class="hero-num-val"><?php echo $total_books; ?></div>
                <div class="hero-num-lbl">Books</div>
            </div>
            <div class="hero-num">
                <div class="hero-num-val"><?php echo count($categories); ?></div>
                <div class="hero-num-lbl">Categories</div>
            </div>
            <div class="hero-num">
                <div class="hero-num-val text-whatsapp">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="hero-num-lbl">WhatsApp Order</div>
            </div>
        </div>
    </div>
</section>

<!-- ✅ Search Bar -->
<div class="search-wrap">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" class="search-inp" id="searchInput"
               placeholder="Search by title, author or category..."
               oninput="handleSearch(this.value)">
        <span class="search-x" id="searchClear" onclick="clearSearch()">
            <i class="fas fa-times"></i>
        </span>
    </div>
</div>

<!-- ✅ Category Pills -->
<?php if (count($categories) > 1): ?>
<div class="cat-row">
    <div class="cat-pills">
        <div class="cat-pill active" data-cat="all" onclick="filterCat('all',this)">
            All <span class="pill-count"><?php echo $total_books; ?></span>
        </div>
        <?php foreach ($categories as $cat): ?>
        <div class="cat-pill" data-cat="<?php echo htmlspecialchars($cat); ?>"
             onclick="filterCat(<?php echo json_encode($cat); ?>,this)">
            <?php echo htmlspecialchars($cat); ?>
            <span class="pill-count"><?php echo count($by_cat[$cat]); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ✅ Books Container -->
<div id="booksContainer" class="container">
<?php if (empty($all_books)): ?>
<div class="section">
    <div class="empty-st">
        <i class="fas fa-book-open"></i>
        <p>No books available yet. Check back soon!</p>
    </div>
</div>
<?php else: ?>
<?php foreach ($by_cat as $cat => $books): ?>
<section class="section book-section" data-cat="<?php echo htmlspecialchars($cat); ?>">
    <div class="sec-label">
        <div class="sec-line"></div>
        <div class="sec-title">
            <em><?php echo htmlspecialchars($cat); ?></em>
            <span class="sec-count"><?php echo count($books); ?> book<?php echo count($books)!=1?'s':''; ?></span>
        </div>
        <div class="sec-line"></div>
    </div>
    <div class="books-grid">
    <?php foreach ($books as $bi => $book):
        $hasDisc = $book['discount_price'] && $book['discount_price'] < $book['real_price'];
        $discPct = $hasDisc ? round((1 - $book['discount_price']/$book['real_price'])*100) : 0;
    ?>
    <a href="book_detail.php?id=<?php echo $book['book_id']; ?>"
       class="book-card animate-in"
       data-title="<?php echo strtolower(htmlspecialchars($book['title'])); ?>"
       data-author="<?php echo strtolower(htmlspecialchars($book['author'] ?? '')); ?>"
       data-cat="<?php echo strtolower(htmlspecialchars($book['category'])); ?>"
       style="animation-delay:<?php echo $bi * 0.05; ?>s">

        <?php if ($hasDisc): ?>
        <div class="disc-badge"><?php echo $discPct; ?>% OFF</div>
        <?php endif; ?>

        <div class="book-img">
            <?php if ($book['image']): ?>
            <img src="assets/uploads/books/<?php echo htmlspecialchars($book['image']); ?>"
                 alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy"
                 onerror="this.closest('.book-img').innerHTML='<div class=\'book-no-img\'><i class=\'fas fa-book-open\'></i><span>No Cover</span></div>'">
            <?php else: ?>
            <div class="book-no-img"><i class="fas fa-book-open"></i><span>No Cover</span></div>
            <?php endif; ?>
            <div class="img-sheen"></div>
        </div>

        <div class="book-info">
            <div class="b-cat">
                <i class="fas fa-circle" style="font-size:0.3rem;"></i>
                <?php echo htmlspecialchars($book['category']); ?>
            </div>
            <div class="b-title"><?php echo htmlspecialchars($book['title']); ?></div>
            <?php if ($book['author']): ?>
            <div class="b-author"><?php echo htmlspecialchars($book['author']); ?></div>
            <?php endif; ?>
            <div class="b-price-row">
                <div>
                    <?php if ($hasDisc): ?>
                    <div class="b-price-disc">Rs <?php echo number_format($book['discount_price'],0); ?></div>
                    <div class="b-price-real">Rs <?php echo number_format($book['real_price'],0); ?></div>
                    <?php else: ?>
                    <div class="b-price-only">Rs <?php echo number_format($book['real_price'],0); ?></div>
                    <?php endif; ?>
                </div>
                <div class="wa-dot"><i class="fab fa-whatsapp"></i></div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- ✅ No Results State -->
<div id="noResults" style="display:none;" class="container section">
    <div class="empty-st">
        <i class="fas fa-search"></i>
        <p>No books match your search.</p>
    </div>
</div>

<!-- ✅ Footer -->
<footer class="footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> MEDDEMY — Pakistan's Medical Exam Prep Platform</p>
        <p style="margin-top: var(--space-xs);">
            Need help? <a href="support.php">Contact Support</a>
        </p>
    </div>
</footer>

<!-- ✅ JavaScript -->
<script>
let curCat = 'all';

// Filter by Category
function filterCat(cat, el) {
    curCat = cat;
    document.querySelectorAll('.cat-pill').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    
    document.querySelectorAll('.book-section').forEach(s => {
        s.style.display = (cat === 'all' || s.dataset.cat === cat) ? '' : 'none';
    });
    
    const q = document.getElementById('searchInput').value.trim();
    if(q) handleSearch(q);
    
    document.getElementById('booksContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Search Functionality
function handleSearch(q) {
    const query = q.toLowerCase().trim();
    document.getElementById('searchClear').style.display = query ? 'block' : 'none';
    
    let anyVisible = false;
    
    document.querySelectorAll('.book-card').forEach(card => {
        const section = card.closest('.book-section');
        
        // Respect category filter
        if(curCat !== 'all' && section && section.dataset.cat !== curCat) {
            card.style.display = 'none';
            return;
        }
        
        // Search match
        const matches = !query || 
            card.dataset.title.includes(query) || 
            card.dataset.author.includes(query) || 
            card.dataset.cat.includes(query);
        
        card.style.display = matches ? '' : 'none';
        if(matches) anyVisible = true;
    });
    
    // Show/hide sections based on visible cards
    document.querySelectorAll('.book-section').forEach(sec => {
        if(curCat !== 'all' && sec.dataset.cat !== curCat) {
            sec.style.display = 'none';
            return;
        }
        const hasVisible = [...sec.querySelectorAll('.book-card')].some(c => c.style.display !== 'none');
        sec.style.display = hasVisible ? '' : 'none';
    });
    
    document.getElementById('noResults').style.display = anyVisible ? 'none' : 'block';
}

// Clear Search
function clearSearch() {
    const input = document.getElementById('searchInput');
    input.value = '';
    handleSearch('');
    input.focus();
    const active = document.querySelector('.cat-pill.active');
    if(active) filterCat(active.dataset.cat, active);
}

// Mobile Menu Toggle
document.getElementById('navToggle')?.addEventListener('click', () => {
    document.getElementById('navbarNav').classList.toggle('active');
});

// Close mobile menu on link click
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        document.getElementById('navbarNav').classList.remove('active');
    });
});

// Animate cards on scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if(entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

document.querySelectorAll('.book-card').forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = `opacity 0.4s ease ${index * 0.05}s, transform 0.4s ease ${index * 0.05}s`;
    observer.observe(card);
});
</script>

</body>
</html>