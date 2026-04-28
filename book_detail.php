<?php
include("includes/db.php");
require_once("includes/config.php");

$book_id = (int)($_GET['id'] ?? 0);
if (!$book_id) { header("Location: books.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM book WHERE book_id=?");
$stmt->bind_param("i", $book_id); $stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) { header("Location: books.php"); exit(); }

$hasDisc  = $book['discount_price'] && $book['discount_price'] < $book['real_price'];
$discPct  = $hasDisc ? round((1 - $book['discount_price']/$book['real_price'])*100) : 0;
$savings  = $hasDisc ? ($book['real_price'] - $book['discount_price']) : 0;
$dispPrice = $hasDisc ? $book['discount_price'] : $book['real_price'];

$waMsg  = urlencode('Assalam o Alaikum! I want to order this book:' . "\n\n" .
    '📚 *' . $book['title'] . '*' .
    ($book['author'] ? "\n✍️ By: " . $book['author'] : '') .
    "\n🏷️ Category: " . $book['category'] .
    "\n💰 Price: Rs " . number_format($dispPrice, 0) .
    "\n\nPlease confirm availability and delivery. (MEDDEMY Book Store)");
$waLink = 'https://wa.me/' . ADMIN_WHATSAPP . '?text=' . $waMsg;

// Related books from same category
$related = $conn->query("SELECT * FROM book WHERE category='" . $conn->real_escape_string($book['category']) . "' AND book_id!=$book_id ORDER BY created_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($book['title']); ?> — MEDDEMY Books</title>
<meta name="description" content="<?php echo htmlspecialchars(substr($book['description'] ?? $book['title'], 0, 160)); ?>">

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* ==================== GLOBAL SETUP (Same as books.php) ==================== */
:root {
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
    
    --gradient-gold: linear-gradient(135deg, var(--golden), var(--golden-light));
    --gradient-dark: linear-gradient(135deg, var(--black) 0%, var(--black-light) 100%);
    --gradient-card: linear-gradient(145deg, var(--black-card), #252525);
    --gradient-whatsapp: linear-gradient(135deg, var(--whatsapp), #128C7E);
    
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.2);
    --shadow-md: 0 4px 20px rgba(0,0,0,0.3);
    --shadow-lg: 0 8px 40px rgba(0,0,0,0.4);
    --shadow-gold: 0 5px 25px rgba(255,215,0,0.35);
    --shadow-whatsapp: 0 4px 20px rgba(37,211,102,0.4);
    
    --transition: all 0.3s ease;
    --radius-sm: 8px;
    --radius-md: 16px;
    --radius-lg: 24px;
    --radius-full: 50px;
    --space-xs: 0.25rem;
    --space-sm: 0.5rem;
    --space-md: 1rem;
    --space-lg: 1.5rem;
    --space-xl: 2rem;
    --space-xxl: 3rem;
    --font-primary: 'Poppins', sans-serif;
    --z-fixed: 1030;
}

*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; font-size: 16px; }
body {
    font-family: var(--font-primary);
    background: var(--gradient-dark);
    color: var(--white);
    line-height: 1.6;
    min-height: 100vh;
    overflow-x: hidden;
}
::selection { background: rgba(255,215,0,0.2); color: var(--black); }
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: var(--black-light); }
::-webkit-scrollbar-thumb { background: var(--golden); border-radius: 4px; }
a { text-decoration: none; color: inherit; transition: var(--transition); }
a:hover { color: var(--golden-light); }

/* Utilities */
.text-golden { color: var(--golden) !important; }
.text-green { color: var(--green) !important; }
.text-whatsapp { color: var(--whatsapp) !important; }
.fw-bold { font-weight: 700 !important; }
.d-flex { display: flex !important; }
.align-items-center { align-items: center !important; }
.justify-content-center { justify-content: center !important; }
.gap-sm { gap: var(--space-sm) !important; }
.gap-md { gap: var(--space-md) !important; }
.container { width: 100%; max-width: 1400px; margin: 0 auto; padding: 0 var(--space-lg); }

/* ==================== NAVBAR ==================== */
.nav {
    position: sticky; top: 0; z-index: var(--z-fixed);
    background: rgba(0,0,0,0.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,215,0,0.1);
    padding: var(--space-sm) 0;
    transition: var(--transition);
}
.nav-inner {
    display: flex; align-items: center; justify-content: space-between;
    gap: var(--space-md);
}
.nav-brand {
    font-size: 1.4rem; font-weight: 800; color: var(--white);
    display: flex; align-items: center; gap: var(--space-sm);
}
.nav-brand::before {
    content: ''; width: 6px; height: 6px; border-radius: 50%;
    background: var(--golden);
}
.nav-back {
    display: inline-flex; align-items: center; gap: var(--space-sm);
    padding: var(--space-sm) var(--space-md);
    border: 1px solid rgba(255,215,0,0.2);
    border-radius: var(--radius-full);
    font-size: 0.9rem; color: var(--gray-light);
    transition: var(--transition);
}
.nav-back:hover {
    border-color: var(--golden); color: var(--golden);
}

/* ==================== BREADCRUMB ==================== */
.breadcrumb {
    padding: var(--space-md) 0;
    display: flex; align-items: center; gap: var(--space-xs);
    font-size: 0.8rem; color: var(--gray);
    flex-wrap: wrap;
}
.breadcrumb a { color: var(--gray-light); transition: var(--transition); }
.breadcrumb a:hover { color: var(--golden); }
.breadcrumb i { font-size: 0.6rem; color: var(--gray); }
.breadcrumb span { color: var(--white); font-weight: 500; }

/* ==================== DETAIL WRAPPER ==================== */
.detail-wrap {
    padding: var(--space-lg) 0 var(--space-xxl);
}

.detail-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: var(--space-xl);
    align-items: start;
}

/* ==================== BOOK COVER COLUMN ==================== */
.book-cover-col {
    position: sticky;
    top: 90px;
}

.cover-frame {
    position: relative;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow:
        0 0 0 1px rgba(255,215,0,0.15),
        0 30px 80px rgba(0,0,0,0.7),
        0 0 120px rgba(255,215,0,0.08);
    animation: coverReveal 0.8s ease both;
    background: var(--black-card);
}

@keyframes coverReveal {
    from { opacity: 0; transform: translateY(20px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.cover-img {
    width: 100%;
    aspect-ratio: 3/4;
    object-fit: cover;
    display: block;
}

.cover-placeholder {
    width: 100%;
    aspect-ratio: 3/4;
    background: var(--black-card);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--space-md);
    color: var(--gray);
}
.cover-placeholder i { font-size: 3.5rem; opacity: 0.3; }
.cover-placeholder span { font-size: 0.8rem; opacity: 0.5; }

/* Discount Badge on Cover */
.cover-disc {
    position: absolute;
    top: var(--space-sm);
    left: var(--space-sm);
    background: var(--gradient-gold);
    color: var(--black);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 5px 14px;
    border-radius: var(--radius-full);
    box-shadow: var(--shadow-gold);
    z-index: 2;
}

/* Cover Glow Effect */
.cover-glow {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 40px;
    background: radial-gradient(ellipse, rgba(255,215,0,0.2), transparent 70%);
    pointer-events: none;
}

/* Price Block */
.cover-price-block {
    margin-top: var(--space-lg);
    background: var(--black-card);
    border: 1px solid rgba(255,215,0,0.2);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    animation: fadeInUp 0.7s 0.3s ease both;
}

.price-main {
    font-size: clamp(1.8rem, 4vw, 2.5rem);
    font-weight: 800;
    color: var(--golden);
    line-height: 1;
}

.price-sub {
    font-size: 0.9rem;
    color: var(--gray);
    text-decoration: line-through;
    margin-top: var(--space-xs);
}

.savings-pill {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    background: rgba(34,197,94,0.12);
    border: 1px solid rgba(34,197,94,0.3);
    color: var(--green);
    font-size: 0.8rem;
    font-weight: 600;
    padding: 4px 14px;
    border-radius: var(--radius-full);
    margin-top: var(--space-sm);
}

.price-note {
    font-size: 0.75rem;
    color: var(--gray);
    margin-top: var(--space-sm);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

/* Order Button */
.btn-order {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    width: 100%;
    padding: var(--space-md) var(--space-lg);
    margin-top: var(--space-md);
    background: var(--gradient-whatsapp);
    color: var(--white);
    border: none;
    border-radius: var(--radius-full);
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: var(--transition);
    box-shadow: var(--shadow-whatsapp);
}

.btn-order:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 35px rgba(37,211,102,0.5);
}

.btn-order i { font-size: 1.2rem; }

.btn-order-pulse {
    animation: pulse 2.5s ease infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: var(--shadow-whatsapp); }
    50% { box-shadow: 0 8px 45px rgba(37,211,102,0.7); }
}

/* ==================== BOOK DETAILS COLUMN ==================== */
.book-details-col {
    animation: fadeInUp 0.7s 0.1s ease both;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.book-cat-tag {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    background: rgba(255,215,0,0.12);
    border: 1px solid rgba(255,215,0,0.25);
    color: var(--golden);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 16px;
    border-radius: var(--radius-full);
    margin-bottom: var(--space-md);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.book-main-title {
    font-size: clamp(1.5rem, 4vw, 2.2rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: var(--space-sm);
}

.book-author-line {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    font-size: 0.95rem;
    color: var(--gray-light);
    margin-bottom: var(--space-lg);
}

.book-author-line::before {
    content: '';
    width: 20px;
    height: 1px;
    background: rgba(255,215,0,0.3);
}

/* Divider */
.divider {
    height: 1px;
    background: rgba(255,215,0,0.15);
    margin: var(--space-lg) 0;
}

/* Description */
.book-desc-label {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--gray);
    margin-bottom: var(--space-sm);
}

.book-desc-text {
    font-size: 0.95rem;
    color: var(--gray-light);
    line-height: 1.8;
    white-space: pre-wrap;
}

/* Meta Grid */
.book-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-sm);
    margin: var(--space-lg) 0;
}

.meta-item {
    background: var(--black-card);
    border: 1px solid rgba(255,215,0,0.15);
    border-radius: var(--radius-md);
    padding: var(--space-md);
}

.meta-label {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: var(--gray);
    margin-bottom: var(--space-xs);
}

.meta-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--white);
}

/* Features List */
.features {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
    margin-top: var(--space-sm);
}

.feature {
    display: flex;
    align-items: flex-start;
    gap: var(--space-sm);
    font-size: 0.9rem;
    color: var(--gray-light);
}

.feature-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--golden);
    flex-shrink: 0;
    margin-top: 8px;
}

/* Order Steps */
.order-steps {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.order-step {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    font-size: 0.9rem;
    color: var(--gray-light);
}

.order-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255,215,0,0.15);
    border: 1px solid rgba(255,215,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--golden);
    flex-shrink: 0;
}

/* ==================== RELATED BOOKS ==================== */
.related-section {
    padding: 0 0 var(--space-xxl);
}

.rel-head {
    display: flex;
    align-items: center;
    gap: var(--space-lg);
    margin-bottom: var(--space-lg);
}

.rel-line {
    flex: 1;
    height: 1px;
    background: rgba(255,215,0,0.15);
}

.rel-title {
    font-size: 1.3rem;
    font-weight: 700;
    white-space: nowrap;
    color: var(--gray-light);
}

.rel-title em {
    color: var(--golden);
    font-style: italic;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: var(--space-lg);
}

.rel-card {
    display: flex;
    flex-direction: column;
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--gradient-card);
    border: 1px solid rgba(255,215,0,0.15);
    transition: var(--transition);
    text-decoration: none;
    color: inherit;
}

.rel-card:hover {
    transform: translateY(-6px);
    border-color: rgba(255,215,0,0.4);
    box-shadow: var(--shadow-gold);
}

.rel-img {
    aspect-ratio: 3/4;
    overflow: hidden;
    background: var(--black-light);
    position: relative;
}

.rel-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.rel-card:hover .rel-img img {
    transform: scale(1.06);
}

.rel-no-img {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray);
    font-size: 2rem;
    opacity: 0.3;
}

.rel-body {
    padding: var(--space-md);
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
}

.rel-title-text {
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.rel-price {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--golden);
    margin-top: auto;
    padding-top: var(--space-sm);
}

/* ==================== MOBILE ORDER BUTTON ==================== */
.mobile-order-wrap {
    display: none;
    padding: var(--space-md);
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 90;
    background: linear-gradient(to top, var(--black) 85%, transparent);
    border-top: 1px solid rgba(255,215,0,0.1);
}

.mobile-order-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-md);
    padding: var(--space-md) var(--space-lg);
    background: var(--gradient-whatsapp);
    color: var(--white);
    border-radius: var(--radius-full);
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    box-shadow: var(--shadow-whatsapp);
}

.mobile-order-btn:hover {
    transform: translateY(-2px);
}

/* ==================== FOOTER ==================== */
.site-footer {
    padding: var(--space-xl) 0;
    border-top: 1px solid rgba(255,215,0,0.1);
    text-align: center;
    font-size: 0.85rem;
    color: var(--gray);
}

.site-footer a { color: var(--golden); }

/* ==================== RESPONSIVE BREAKPOINTS ==================== */

/* Tablet & Below (≤900px) */
@media (max-width: 900px) {
    .detail-grid {
        grid-template-columns: 1fr;
        gap: var(--space-lg);
    }
    .book-cover-col {
        position: static;
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: var(--space-lg);
        align-items: start;
    }
    .cover-price-block {
        margin-top: 0;
        grid-column: 1 / -1;
    }
    .btn-order { display: none; }
    .mobile-order-wrap { display: block; }
}

/* Mobile (≤768px) */
@media (max-width: 768px) {
    .book-cover-col {
        grid-template-columns: 1fr;
        justify-items: center;
    }
    .cover-frame {
        max-width: 240px;
        width: 100%;
    }
    .cover-price-block {
        max-width: 100%;
        padding: var(--space-md);
    }
    .book-main-title { font-size: 1.5rem; }
    .book-meta-grid { grid-template-columns: 1fr; }
    .related-grid { grid-template-columns: repeat(2, 1fr); gap: var(--space-md); }
    .breadcrumb { font-size: 0.75rem; }
}

/* Small Mobile (≤480px) */
@media (max-width: 480px) {
    .cover-frame { max-width: 200px; }
    .price-main { font-size: 1.8rem; }
    .book-main-title { font-size: 1.3rem; }
    .meta-item { padding: var(--space-sm); }
    .meta-value { font-size: 0.9rem; }
    .related-grid { grid-template-columns: 1fr; max-width: 280px; margin: 0 auto; }
    .mobile-order-btn { font-size: 0.9rem; padding: var(--space-sm) var(--space-md); }
}

/* Large Screens (≥1400px) */
@media (min-width: 1400px) {
    .related-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
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
        <a href="books.php" class="nav-back">
            <i class="fas fa-arrow-left"></i> All Books
        </a>
    </div>
</nav>

<!-- ✅ Breadcrumb -->
<div class="container">
    <nav class="breadcrumb">
        <a href="index.php">Home</a>
        <i class="fas fa-chevron-right"></i>
        <a href="books.php">Books</a>
        <i class="fas fa-chevron-right"></i>
        <a href="books.php?cat=<?php echo urlencode($book['category']); ?>">
            <?php echo htmlspecialchars($book['category']); ?>
        </a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($book['title']); ?></span>
    </nav>
</div>

<!-- ✅ Main Content -->
<div class="container detail-wrap">
    <div class="detail-grid">
        
        <!-- LEFT: Cover + Price + Order -->
        <div class="book-cover-col">
            <div class="cover-frame">
                <?php if ($book['image']): ?>
                <img class="cover-img"
                     src="assets/uploads/books/<?php echo htmlspecialchars($book['image']); ?>"
                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                     onerror="this.outerHTML='<div class=\'cover-placeholder\'><i class=\'fas fa-book-open\'></i><span>No Cover</span></div>'">
                <?php else: ?>
                <div class="cover-placeholder">
                    <i class="fas fa-book-open"></i>
                    <span>No Cover Image</span>
                </div>
                <?php endif; ?>
                
                <?php if ($hasDisc): ?>
                <div class="cover-disc"><?php echo $discPct; ?>% OFF</div>
                <?php endif; ?>
                
                <div class="cover-glow"></div>
            </div>

            <!-- Price Block -->
            <div class="cover-price-block">
                <?php if ($hasDisc): ?>
                <div class="price-main">Rs <?php echo number_format($book['discount_price'],0); ?></div>
                <div class="price-sub">Rs <?php echo number_format($book['real_price'],0); ?></div>
                <div class="savings-pill">
                    <i class="fas fa-tag" style="font-size:0.7rem;"></i>
                    Save Rs <?php echo number_format($savings,0); ?> (<?php echo $discPct; ?>% off)
                </div>
                <?php else: ?>
                <div class="price-main">Rs <?php echo number_format($book['real_price'],0); ?></div>
                <?php endif; ?>
                
                <div class="price-note">
                    <i class="fas fa-info-circle"></i>
                    Final price confirmed on WhatsApp
                </div>

                <!-- Desktop Order Button -->
                <a href="<?php echo $waLink; ?>" target="_blank" class="btn-order btn-order-pulse">
                    <i class="fab fa-whatsapp"></i>
                    Order on WhatsApp
                </a>
            </div>
        </div>

        <!-- RIGHT: Details -->
        <div class="book-details-col">
            <div class="book-cat-tag">
                <i class="fas fa-bookmark" style="font-size:0.7rem;"></i>
                <?php echo htmlspecialchars($book['category']); ?>
            </div>

            <h1 class="book-main-title"><?php echo htmlspecialchars($book['title']); ?></h1>

            <?php if ($book['author']): ?>
            <div class="book-author-line">
                <i class="fas fa-feather-alt" style="font-size:0.7rem; opacity:0.6;"></i>
                <?php echo htmlspecialchars($book['author']); ?>
            </div>
            <?php endif; ?>

            <!-- Meta Grid -->
            <div class="book-meta-grid">
                <div class="meta-item">
                    <div class="meta-label">Category</div>
                    <div class="meta-value"><?php echo htmlspecialchars($book['category']); ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Price</div>
                    <div class="meta-value text-golden">
                        Rs <?php echo number_format($dispPrice,0); ?>
                        <?php if ($hasDisc): ?>
                        <span style="font-size:0.8rem; color:var(--gray); font-weight:400; margin-left:4px; text-decoration:line-through;">
                            Rs <?php echo number_format($book['real_price'],0); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($book['author']): ?>
                <div class="meta-item">
                    <div class="meta-label">Author</div>
                    <div class="meta-value"><?php echo htmlspecialchars($book['author']); ?></div>
                </div>
                <?php endif; ?>
                <div class="meta-item">
                    <div class="meta-label">Order Via</div>
                    <div class="meta-value text-whatsapp d-flex align-items-center gap-sm">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Description -->
            <?php if ($book['description']): ?>
            <div class="book-desc-label">About This Book</div>
            <div class="book-desc-text"><?php echo htmlspecialchars($book['description']); ?></div>
            <div class="divider"></div>
            <?php endif; ?>

            <!-- Features -->
            <div class="book-desc-label" style="margin-bottom:var(--space-sm);">Why Order From Us</div>
            <div class="features">
                <?php foreach([
                    'Authentic original books — no pirated copies',
                    'WhatsApp confirmation before payment',
                    'Delivery across Pakistan',
                    'Best prices for exam prep books',
                    'Fast response — usually within 1 hour',
                ] as $feat): ?>
                <div class="feature">
                    <div class="feature-dot"></div>
                    <?php echo $feat; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="divider"></div>

            <!-- How to Order -->
            <div class="book-desc-label" style="margin-bottom:var(--space-sm);">How to Order</div>
            <div class="order-steps">
                <?php foreach([
                    ['1','Click "Order on WhatsApp" button'],
                    ['2','Message is auto-filled — just send it'],
                    ['3','Admin confirms availability & price'],
                    ['4','Pay & receive delivery details'],
                ] as [$num, $step]): ?>
                <div class="order-step">
                    <div class="order-num"><?php echo $num; ?></div>
                    <?php echo $step; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Related Books -->
<?php if (!empty($related)): ?>
<div class="container related-section">
    <div class="rel-head">
        <div class="rel-line"></div>
        <div class="rel-title">More in <em><?php echo htmlspecialchars($book['category']); ?></em></div>
        <div class="rel-line"></div>
    </div>
    <div class="related-grid">
        <?php foreach ($related as $rb):
            $rHasDisc = $rb['discount_price'] && $rb['discount_price'] < $rb['real_price'];
            $rPrice   = $rHasDisc ? $rb['discount_price'] : $rb['real_price'];
        ?>
        <a href="book_detail.php?id=<?php echo $rb['book_id']; ?>" class="rel-card">
            <div class="rel-img">
                <?php if ($rb['image']): ?>
                <img src="assets/uploads/books/<?php echo htmlspecialchars($rb['image']); ?>"
                     alt="<?php echo htmlspecialchars($rb['title']); ?>"
                     onerror="this.outerHTML='<div class=\'rel-no-img\'><i class=\'fas fa-book-open\'></i></div>'">
                <?php else: ?>
                <div class="rel-no-img"><i class="fas fa-book-open"></i></div>
                <?php endif; ?>
            </div>
            <div class="rel-body">
                <div class="rel-title-text"><?php echo htmlspecialchars($rb['title']); ?></div>
                <div class="rel-price">Rs <?php echo number_format($rPrice,0); ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ✅ Mobile Fixed Order Button -->
<div class="mobile-order-wrap">
    <a href="<?php echo $waLink; ?>" target="_blank" class="mobile-order-btn">
        <div class="d-flex align-items-center gap-sm">
            <i class="fab fa-whatsapp" style="font-size:1.2rem;"></i>
            Order on WhatsApp
        </div>
        <div style="font-weight:800; color:rgba(255,255,255,0.95);">
            Rs <?php echo number_format($dispPrice,0); ?>
        </div>
    </a>
</div>

<!-- ✅ Footer -->
<footer class="site-footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> MEDDEMY — Pakistan's Medical Exam Prep Platform</p>
        <p style="margin-top: var(--space-xs);">
            Need help? <a href="support.php">Contact Support</a>
        </p>
    </div>
</footer>

<!-- ✅ JavaScript -->
<script>
// Parallax effect on cover (desktop only)
if (window.innerWidth > 900) {
    const frame = document.querySelector('.cover-frame');
    if (frame) {
        document.addEventListener('mousemove', e => {
            const rx = (e.clientY / window.innerHeight - 0.5) * 4;
            const ry = (e.clientX / window.innerWidth - 0.5) * -4;
            frame.style.transform = `perspective(800px) rotateX(${rx}deg) rotateY(${ry}deg)`;
        });
        document.addEventListener('mouseleave', () => {
            frame.style.transform = '';
        });
    }
}

// Animate related cards on scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if(entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.rel-card').forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(15px)';
    card.style.transition = `opacity 0.4s ease ${index * 0.1}s, transform 0.4s ease ${index * 0.1}s`;
    observer.observe(card);
});
</script>

</body>
</html>