<?php
session_start();
include("../includes/db.php");

// 🎯 Page Purpose: Show ALL materials (Free + Paid)
// 🔓 Free: Direct Google Drive access
// 🔐 Paid: Locked cards with WhatsApp button on HOVER for access request

// 📦 Client's Google Drive Folder for FREE Materials
$free_drive_link = "https://drive.google.com/drive/folders/YOUR_FREE_MATERIALS_FOLDER_ID";

// 💬 Admin WhatsApp Number (with country code, no + sign)
$admin_whatsapp = "923001234567";

// User info
$student_id = isset($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : 0;
$is_logged_in = $student_id > 0;
$student_name = $is_logged_in ? htmlspecialchars($_SESSION['student_name']) : 'Student';

// 🔓 Fetch FREE materials (everyone can see)
$free_query = $conn->query("SELECT * FROM material WHERE type='free' ORDER BY created_at DESC");
$free_materials = [];
while($row = $free_query->fetch_assoc()) {
    $free_materials[] = $row;
}

// 🔐 Fetch PAID materials (everyone can SEE, but only access if granted)
$paid_query = $conn->query("SELECT m.*, 
    CASE WHEN ma.student_id IS NOT NULL THEN 1 ELSE 0 END as has_access
    FROM material m
    LEFT JOIN material_access ma ON ma.material_id = m.material_id AND ma.student_id = $student_id
    WHERE m.type='paid' 
    ORDER BY m.created_at DESC");
$paid_materials = [];
while($row = $paid_query->fetch_assoc()) {
    $paid_materials[] = $row;
}

// Combine & group by category for display
$all_materials = array_merge(
    array_map(fn($m) => [...$m, 'type' => 'free'], $free_materials),
    array_map(fn($m) => [...$m, 'type' => 'paid'], $paid_materials)
);

$materials_by_category = [];
foreach($all_materials as $mat) {
    $cat = $mat['category'] ?? 'General';
    $materials_by_category[$cat][] = $mat;
}

// Stats
$total_free = count($free_materials);
$total_paid = count($paid_materials);
$unlocked_paid = count(array_filter($paid_materials, fn($m) => $m['has_access']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Materials — MEDDEMY</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* ==================== GLOBAL SETUP ==================== */
    :root {
        /* 🎨 Color Scheme */
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
        --green-dark: #16a34a;
        --whatsapp: #25D366;
        
        /* 🌈 Gradients */
        --gradient-gold: linear-gradient(135deg, var(--golden), var(--golden-light));
        --gradient-dark: linear-gradient(135deg, var(--black) 0%, var(--black-light) 100%);
        --gradient-card: linear-gradient(145deg, var(--black-card), #252525);
        --gradient-drive: linear-gradient(135deg, var(--green), var(--green-dark));
        --gradient-whatsapp: linear-gradient(135deg, var(--whatsapp), #128C7E);
        
        /* 🌓 Shadows */
        --shadow-sm: 0 2px 8px rgba(0,0,0,0.2);
        --shadow-md: 0 4px 20px rgba(0,0,0,0.3);
        --shadow-lg: 0 8px 40px rgba(0,0,0,0.4);
        --shadow-gold: 0 5px 25px rgba(255,215,0,0.35);
        --shadow-drive: 0 4px 20px rgba(34,197,94,0.3);
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
    
    /* 🔗 Links */
    a { color: var(--golden); text-decoration: none; transition: var(--transition); }
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
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 var(--space-lg);
    }
    
    /* ==================== NAVBAR ==================== */
    .navbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        background: rgba(0,0,0,0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255,215,0,0.1);
        padding: var(--space-sm) 0;
        z-index: var(--z-fixed);
        transition: var(--transition);
    }
    .navbar.scrolled {
        padding: var(--space-xs) 0;
        box-shadow: var(--shadow-lg);
    }
    .navbar-brand {
        display: flex; align-items: center; gap: var(--space-sm);
        font-size: 1.3rem; font-weight: 800; color: var(--white);
    }
    .navbar-brand img { height: 36px; transition: var(--transition); }
    .navbar-brand:hover img { transform: scale(1.05); }
    
    .navbar-nav {
        display: flex; align-items: center; gap: var(--space-md);
        margin-left: auto;
    }
    .nav-link {
        color: var(--golden) !important;
        font-weight: 500; font-size: 0.95rem;
        padding: var(--space-sm) var(--space-md);
        position: relative; transition: var(--transition);
    }
    .nav-link::after {
        content: ''; position: absolute; bottom: 0; left: 50%;
        width: 0; height: 2px; background: var(--golden);
        transition: var(--transition); transform: translateX(-50%);
    }
    .nav-link:hover::after, .nav-link.active::after { width: 70%; }
    .nav-link:hover { color: var(--white) !important; }
    
    .btn-nav {
        background: var(--gradient-gold); color: var(--black) !important;
        border: none; border-radius: var(--radius-full);
        padding: var(--space-sm) var(--space-xl);
        font-weight: 600; font-size: 0.9rem;
        transition: var(--transition); box-shadow: var(--shadow-gold);
    }
    .btn-nav:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-gold-hover);
        background: var(--white);
    }
    
    .navbar-toggler {
        display: none; background: none;
        border: 2px solid var(--golden);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-sm);
        color: var(--golden); cursor: pointer;
    }
    
    @media (max-width: 991px) {
        .navbar-toggler { display: block; }
        .navbar-nav {
            position: fixed; top: 70px; left: 0; right: 0;
            background: rgba(0,0,0,0.98);
            flex-direction: column; padding: var(--space-lg);
            gap: var(--space-sm);
            transform: translateY(-150%);
            transition: var(--transition);
            border-bottom: 1px solid rgba(255,215,0,0.1);
        }
        .navbar-nav.active { transform: translateY(0); }
        .nav-link {
            width: 100%; text-align: center;
            padding: var(--space-md) 0;
            border-bottom: 1px solid rgba(255,215,0,0.1);
        }
        .btn-nav { width: 100%; justify-content: center; }
    }
    
    /* ==================== HERO SECTION ==================== */
    .hero-section {
        padding: 120px 0 var(--space-xl);
        background: radial-gradient(ellipse at top, rgba(255,215,0,0.08), transparent 60%);
        position: relative; overflow: hidden;
        text-align: center;
    }
    .hero-section::before {
        content: ''; position: absolute;
        top: -100px; right: -100px;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(255,215,0,0.1), transparent 70%);
        border-radius: 50%; pointer-events: none;
        animation: float 20s ease-in-out infinite;
    }
    @keyframes float {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(20px, -20px); }
    }
    
    .hero-badge {
        display: inline-flex; align-items: center; gap: var(--space-xs);
        background: rgba(255,215,0,0.15);
        border: 1px solid rgba(255,215,0,0.3);
        color: var(--golden);
        font-size: 0.8rem; font-weight: 700;
        padding: var(--space-xs) var(--space-md);
        border-radius: var(--radius-full);
        margin-bottom: var(--space-md);
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    
    .hero-title {
        font-size: clamp(1.8rem, 5vw, 2.5rem);
        font-weight: 800; margin-bottom: var(--space-sm);
        line-height: 1.3;
    }
    .hero-title .highlight {
        background: var(--gradient-gold);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .hero-subtitle {
        font-size: 1.1rem; color: rgba(255,255,255,0.85);
        margin: 0 auto var(--space-xl); max-width: 650px;
    }
    
    /* Stats Bar */
    .stats-bar {
        display: flex; justify-content: center; gap: var(--space-lg);
        flex-wrap: wrap; padding: var(--space-md);
        background: var(--black-card);
        border: 1px solid rgba(255,215,0,0.1);
        border-radius: var(--radius-md);
        margin: 0 auto var(--space-xl); max-width: 800px;
    }
    .stat-item {
        display: flex; align-items: center; gap: var(--space-sm);
        text-align: left; padding: var(--space-sm) var(--space-md);
        background: rgba(255,255,255,0.03);
        border-radius: var(--radius-sm);
    }
    .stat-item i {
        font-size: 1.2rem; color: var(--golden);
        width: 24px; text-align: center;
    }
    .stat-item span {
        font-size: 0.9rem; color: rgba(255,255,255,0.9);
    }
    .stat-item strong {
        color: var(--golden); font-weight: 700;
    }
    .stat-item.free i { color: var(--green); }
    .stat-item.paid i { color: var(--golden); }
    .stat-item.unlocked i { color: var(--whatsapp); }
    
    /* ==================== MAIN CTA BUTTONS ==================== */
    .hero-ctas {
        display: flex; justify-content: center; gap: var(--space-md);
        flex-wrap: wrap; margin-bottom: var(--space-xl);
    }
    
    .btn-drive-main {
        display: inline-flex; align-items: center; gap: var(--space-sm);
        background: var(--gradient-drive); color: var(--white);
        border: none; border-radius: var(--radius-full);
        padding: var(--space-md) var(--space-xl);
        font-weight: 700; font-size: 1rem;
        transition: var(--transition);
        text-decoration: none;
        box-shadow: var(--shadow-drive);
    }
    .btn-drive-main:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(34,197,94,0.5);
        color: var(--white);
    }
    .btn-drive-main i { font-size: 1.2rem; }
    
    .btn-whatsapp-main {
        display: inline-flex; align-items: center; gap: var(--space-sm);
        background: var(--gradient-whatsapp); color: var(--white);
        border: none; border-radius: var(--radius-full);
        padding: var(--space-md) var(--space-xl);
        font-weight: 700; font-size: 1rem;
        transition: var(--transition);
        text-decoration: none;
        box-shadow: var(--shadow-whatsapp);
    }
    .btn-whatsapp-main:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(37,211,102,0.5);
        color: var(--white);
    }
    .btn-whatsapp-main i { font-size: 1.2rem; }
    
    .cta-hint {
        font-size: 0.85rem; color: var(--gray-light);
        margin-top: var(--space-sm);
    }
    
    /* ==================== FILTER TABS ==================== */
    .filter-tabs {
        display: flex; justify-content: center; gap: var(--space-xs);
        margin-bottom: var(--space-xl); flex-wrap: wrap;
    }
    .filter-btn {
        padding: var(--space-sm) var(--space-lg);
        background: var(--black-card);
        border: 1px solid rgba(255,215,0,0.2);
        border-radius: var(--radius-full);
        color: var(--gray-light);
        font-size: 0.9rem; font-weight: 500;
        transition: var(--transition);
        cursor: pointer;
    }
    .filter-btn:hover, .filter-btn.active {
        background: var(--golden);
        border-color: var(--golden);
        color: var(--black);
    }
    .filter-btn .count {
        font-size: 0.75rem;
        background: rgba(255,255,255,0.1);
        padding: 2px 8px;
        border-radius: var(--radius-full);
        margin-left: var(--space-xs);
    }
    .filter-btn.active .count {
        background: rgba(0,0,0,0.2);
        color: var(--black);
    }
    
    /* ==================== MATERIALS GRID ==================== */
    .materials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xxl);
    }
    
    /* Material Card Base */
    .material-card {
        background: var(--gradient-card);
        border: 1px solid rgba(255,215,0,0.15);
        border-radius: var(--radius-lg);
        overflow: hidden;
        transition: var(--transition);
        display: flex; flex-direction: column;
        animation: fadeInUp 0.5s ease;
        position: relative;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Card Type Indicator (Top Border) */
    .material-card::before {
        content: ''; position: absolute;
        top: 0; left: 0; right: 0; height: 4px;
        transition: var(--transition);
    }
    .material-card.free::before {
        background: var(--gradient-drive);
    }
    .material-card.paid.locked::before {
        background: linear-gradient(90deg, var(--golden), var(--golden-dark));
    }
    .material-card.paid.unlocked::before {
        background: var(--gradient-gold);
    }
    
    .material-card:hover {
        transform: translateY(-6px);
        border-color: rgba(255,215,0,0.4);
        box-shadow: var(--shadow-gold);
    }
    
    /* Card Header */
    .card-header {
        padding: var(--space-lg);
        padding-bottom: var(--space-md);
        border-bottom: 1px solid rgba(255,215,0,0.1);
    }
    
    .card-type-badge {
        display: inline-flex; align-items: center; gap: var(--space-xs);
        font-size: 0.7rem; font-weight: 700;
        padding: 3px 12px; border-radius: var(--radius-full);
        text-transform: uppercase; letter-spacing: 0.5px;
        margin-bottom: var(--space-md);
    }
    .card-type-badge.free {
        background: rgba(34,197,94,0.15);
        border: 1px solid rgba(34,197,94,0.4);
        color: var(--green);
    }
    .card-type-badge.paid.locked {
        background: rgba(255,215,0,0.12);
        border: 1px solid rgba(255,215,0,0.25);
        color: var(--golden);
    }
    .card-type-badge.paid.unlocked {
        background: rgba(34,197,94,0.15);
        border: 1px solid rgba(34,197,94,0.4);
        color: var(--green);
    }
    
    .card-file-icon {
        width: 56px; height: 56px;
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        margin-bottom: var(--space-md); flex-shrink: 0;
    }
    .card-file-icon.pdf { background: rgba(255,77,77,0.12); color: #ff4d4d; }
    .card-file-icon.doc { background: rgba(59,130,246,0.12); color: #3b82f6; }
    .card-file-icon.ppt { background: rgba(249,115,22,0.12); color: #f97316; }
    .card-file-icon.zip { background: rgba(139,92,246,0.12); color: #8b5cf6; }
    .card-file-icon.other { background: rgba(255,255,255,0.08); color: var(--gray-light); }
    .card-file-icon i { font-size: 1.5rem; }
    
    .card-title {
        font-size: 1.1rem; font-weight: 700;
        margin-bottom: var(--space-xs); line-height: 1.4;
    }
    .card-category {
        font-size: 0.85rem; color: var(--gray-light);
        font-weight: 500;
    }
    
    /* Card Body */
    .card-body {
        padding: 0 var(--space-lg);
        flex-grow: 1;
    }
    .card-description {
        font-size: 0.9rem; color: rgba(255,255,255,0.75);
        line-height: 1.6; margin-bottom: var(--space-md);
    }
    
    .card-meta {
        display: flex; align-items: center; gap: var(--space-md);
        padding-bottom: var(--space-md);
        border-bottom: 1px solid rgba(255,215,0,0.1);
        margin-bottom: var(--space-md);
    }
    .meta-item {
        display: flex; align-items: center; gap: var(--space-xs);
        font-size: 0.8rem; color: var(--gray-light);
    }
    .meta-item i { color: var(--golden); font-size: 0.85rem; }
    
    /* Card Footer - FREE */
    .card-footer.free {
        padding: var(--space-md) var(--space-lg);
        background: rgba(34,197,94,0.05);
        border-top: 1px solid rgba(34,197,94,0.2);
        display: flex; align-items: center; justify-content: space-between;
        gap: var(--space-sm);
    }
    .file-ext {
        font-size: 0.75rem; font-weight: 700;
        color: var(--gray); text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .btn-download {
        display: inline-flex; align-items: center; gap: var(--space-xs);
        background: var(--gradient-drive); color: var(--white);
        border: none; border-radius: var(--radius-full);
        padding: var(--space-sm) var(--space-lg);
        font-weight: 600; font-size: 0.85rem;
        transition: var(--transition); text-decoration: none;
    }
    .btn-download:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-drive);
        color: var(--white);
    }
    .btn-download i {
        font-size: 0.9rem; transition: transform var(--transition);
    }
    .btn-download:hover i { transform: translateX(3px); }
    
    /* ✅ Card Footer - PAID LOCKED (WhatsApp on Hover) */
    .card-footer.paid.locked {
        padding: var(--space-md) var(--space-lg);
        background: rgba(255,215,0,0.05);
        border-top: 1px solid rgba(255,215,0,0.2);
        display: flex; flex-direction: column; gap: var(--space-sm);
        position: relative;
        overflow: hidden;
    }
    
    /* Default state - Show lock message, hide WhatsApp button */
    .locked-message {
        display: flex; align-items: center; gap: var(--space-xs);
        font-size: 0.85rem; color: var(--gray-light);
        transition: var(--transition);
    }
    .locked-message i { color: var(--golden); }
    
    .btn-whatsapp {
        display: inline-flex; align-items: center; justify-content: center;
        gap: var(--space-xs);
        background: var(--gradient-whatsapp); color: var(--white);
        border: none; border-radius: var(--radius-full);
        padding: var(--space-sm) var(--space-lg);
        font-weight: 600; font-size: 0.85rem;
        transition: var(--transition); text-decoration: none;
        width: 100%;
        opacity: 0;
        transform: translateY(20px);
        position: absolute;
        bottom: var(--space-md);
        left: var(--space-lg);
        right: var(--space-lg);
    }
    
    /* ✅ SHOW WhatsApp Button on Hover */
    .material-card.paid.locked:hover .btn-whatsapp {
        opacity: 1;
        transform: translateY(0);
    }
    
    .material-card.paid.locked:hover .locked-message {
        opacity: 0.5;
    }
    
    .btn-whatsapp:hover {
        transform: translateY(-2px) !important;
        box-shadow: var(--shadow-whatsapp);
        color: var(--white);
    }
    .btn-whatsapp i { font-size: 0.9rem; }
    
    .whatsapp-hint {
        font-size: 0.75rem; color: var(--gray);
        text-align: center; font-style: italic;
        transition: var(--transition);
    }
    
    .material-card.paid.locked:hover .whatsapp-hint {
        opacity: 0;
    }
    
    /* Card Footer - PAID UNLOCKED */
    .card-footer.paid.unlocked {
        padding: var(--space-md) var(--space-lg);
        background: rgba(34,197,94,0.05);
        border-top: 1px solid rgba(34,197,94,0.2);
        display: flex; align-items: center; justify-content: space-between;
        gap: var(--space-sm);
    }
    
    /* ✅ Enhanced Lock Overlay with WhatsApp CTA */
    .lock-overlay {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
        flex-direction: column; gap: var(--space-md);
        opacity: 0; transition: var(--transition);
        pointer-events: none;
        z-index: 10;
        padding: var(--space-lg);
    }
    
    /* ✅ Show Lock Overlay on Hover for Paid Locked Cards */
    .material-card.paid.locked:hover .lock-overlay {
        opacity: 1; pointer-events: auto;
    }
    
    .lock-overlay i {
        font-size: 3.5rem; color: var(--golden);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    .lock-overlay-text {
        text-align: center;
    }
    
    .lock-overlay-text h4 {
        font-size: 1.1rem; font-weight: 700;
        color: var(--white); margin-bottom: var(--space-xs);
    }
    
    .lock-overlay-text p {
        font-size: 0.85rem; color: var(--gray-light);
        margin: 0;
    }
    
    .lock-overlay .whatsapp-cta {
        display: inline-flex; align-items: center; gap: var(--space-xs);
        background: var(--gradient-whatsapp); color: var(--white);
        border: none; border-radius: var(--radius-full);
        padding: var(--space-sm) var(--space-lg);
        font-weight: 600; font-size: 0.9rem;
        text-decoration: none;
        margin-top: var(--space-md);
        opacity: 0;
        transform: translateY(10px);
        transition: var(--transition);
    }
    
    /* ✅ Show WhatsApp CTA in Overlay after delay */
    .material-card.paid.locked:hover .lock-overlay .whatsapp-cta {
        opacity: 1;
        transform: translateY(0);
        transition-delay: 0.15s;
    }
    
    .lock-overlay .whatsapp-cta:hover {
        transform: translateY(-2px) !important;
        box-shadow: var(--shadow-whatsapp);
        color: var(--white);
    }
    
    /* ==================== CATEGORY SECTIONS ==================== */
    .category-section {
        margin-bottom: var(--space-xxl);
        animation: fadeInUp 0.5s ease;
    }
    .category-header {
        display: flex; align-items: center; gap: var(--space-md);
        margin-bottom: var(--space-lg);
        padding-bottom: var(--space-md);
        border-bottom: 1px solid rgba(255,215,0,0.1);
    }
    .category-icon {
        width: 45px; height: 45px;
        background: rgba(255,215,0,0.12);
        border: 2px solid rgba(255,215,0,0.3);
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .category-icon i { font-size: 1.2rem; color: var(--golden); }
    .category-title {
        font-size: 1.4rem; font-weight: 700;
        margin-bottom: var(--space-xs);
    }
    .category-count {
        font-size: 0.9rem; color: var(--gray-light);
    }
    
    /* ==================== INFO BOX ==================== */
    .info-box {
        background: rgba(255,215,0,0.08);
        border: 1px solid rgba(255,215,0,0.25);
        border-radius: var(--radius-md);
        padding: var(--space-lg);
        margin-bottom: var(--space-xxl);
        display: flex; align-items: flex-start; gap: var(--space-md);
    }
    .info-box i {
        font-size: 1.5rem; color: var(--golden);
        flex-shrink: 0; margin-top: 3px;
    }
    .info-box-content h4 {
        font-size: 1.1rem; font-weight: 700;
        margin-bottom: var(--space-xs); color: var(--white);
    }
    .info-box-content p {
        color: var(--gray-light); font-size: 0.95rem;
        margin: 0; line-height: 1.6;
    }
    .info-box-content ul {
        margin: var(--space-sm) 0 0 var(--space-lg);
        color: var(--gray-light); font-size: 0.9rem;
    }
    .info-box-content li { margin-bottom: var(--space-xs); }
    
    /* ==================== FOOTER ==================== */
    .page-footer {
        padding: var(--space-xl) 0;
        border-top: 1px solid rgba(255,215,0,0.1);
        text-align: center; color: var(--gray);
        font-size: 0.9rem;
    }
    .page-footer a { color: var(--golden); }
    
    /* ==================== RESPONSIVE ==================== */
    @media (max-width: 768px) {
        .hero-title { font-size: 1.6rem; }
        .hero-subtitle { font-size: 1rem; }
        .hero-ctas { flex-direction: column; align-items: center; }
        .btn-drive-main, .btn-whatsapp-main { width: 100%; justify-content: center; }
        .stats-bar { flex-direction: column; gap: var(--space-sm); text-align: center; }
        .stat-item { justify-content: center; width: 100%; }
        .materials-grid { grid-template-columns: 1fr; }
        .info-box { flex-direction: column; text-align: center; }
        .info-box-content ul { margin-left: 0; }
        .category-header { flex-direction: column; text-align: center; }
        
        /* On mobile, always show WhatsApp button (no hover on touch) */
        .material-card.paid.locked .btn-whatsapp {
            opacity: 1;
            transform: translateY(0);
            position: relative;
            bottom: auto;
            left: auto;
            right: auto;
        }
        .material-card.paid.locked .lock-overlay {
            display: none;
        }
    }
    
    @media (max-width: 480px) {
        :root {
            --space-lg: 1.2rem;
            --space-xl: 1.5rem;
            --space-xxl: 2rem;
        }
        .navbar-brand span { display: none; }
        .hero-title { font-size: 1.4rem; }
        .card-title { font-size: 1rem; }
        .btn-download, .btn-whatsapp { font-size: 0.8rem; padding: var(--space-xs) var(--space-md); }
    }
    
    /* ==================== ANIMATIONS ==================== */
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .animate-in { animation: fadeIn 0.4s ease forwards; }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    </style>
</head>
<body>

<!-- ✅ Navbar -->
<nav class="navbar" id="mainNav">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="../index.php" class="navbar-brand">
            <img src="../assets/images/logo44.png" alt="MEDDEMY" onerror="this.style.display='none'">
            <span>MEDDEMY</span>
        </a>
        <button class="navbar-toggler" id="navToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="navbar-nav" id="navbarNav">
            <a href="../index.php" class="nav-link">Home</a>
            <?php if($is_logged_in): ?>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="../logout.php" class="btn-nav">Logout</a>
            <?php else: ?>
                <a href="../login.php" class="btn-nav">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ✅ Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-badge">
            <i class="fas fa-book-open"></i> Study Materials Library
        </div>
        <h1 class="hero-title">
            All <span class="highlight">Study Resources</span> in One Place
        </h1>
        <p class="hero-subtitle">
            Browse free materials (instant download) and premium content (request access via WhatsApp). 
            Everything you need to ace your exams.
        </p>
        
        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat-item free">
                <i class="fas fa-unlock"></i>
                <span><strong><?php echo $total_free; ?></strong> Free Files</span>
            </div>
            <div class="stat-item paid">
                <i class="fas fa-lock"></i>
                <span><strong><?php echo $total_paid; ?></strong> Premium Files</span>
            </div>
            <?php if($is_logged_in && $unlocked_paid > 0): ?>
            <div class="stat-item unlocked">
                <i class="fas fa-key"></i>
                <span><strong><?php echo $unlocked_paid; ?></strong> Unlocked for You</span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Main CTAs -->
        <div class="hero-ctas">
            <a href="<?php echo $free_drive_link; ?>" class="btn-drive-main" target="_blank" rel="noopener">
                <i class="fab fa-google-drive"></i>
                Open Free Materials Drive
            </a>
            <a href="https://wa.me/<?php echo $admin_whatsapp; ?>?text=Assalam%20ualikum!%20I%20want%20to%20get%20access%20to%20premium%20materials%20on%20MEDDEMY.%20Please%20guide%20me." 
               class="btn-whatsapp-main" target="_blank" rel="noopener">
                <i class="fab fa-whatsapp"></i>
                Request Premium Access
            </a>
        </div>
        <p class="cta-hint">
            <i class="fas fa-info-circle text-golden"></i>
            Free materials: No login needed • Premium: Hover & click WhatsApp for access
        </p>
    </div>
</section>

<main class="container">

    <!-- 💡 Info Box -->
    <div class="info-box">
        <i class="fas fa-lightbulb"></i>
        <div class="info-box-content">
            <h4>How to Access Materials</h4>
            <p>Materials are divided into two types:</p>
            <ul>
                <li>🟢 <strong>Free:</strong> Click "Open" → Opens Google Drive → Save to your device</li>
                <li>🔒 <strong>Premium:</strong> <strong>Hover over card</strong> → Click "Request on WhatsApp" → Message admin → Get access granted → Download</li>
            </ul>
        </div>
    </div>

    <!-- 🔀 Filter Tabs -->
    <div class="filter-tabs">
        <button class="filter-btn active" onclick="filterMaterials('all', this)">
            All <span class="count"><?php echo count($all_materials); ?></span>
        </button>
        <button class="filter-btn" onclick="filterMaterials('free', this)">
            <i class="fas fa-unlock text-green"></i> Free <span class="count"><?php echo $total_free; ?></span>
        </button>
        <button class="filter-btn" onclick="filterMaterials('paid', this)">
            <i class="fas fa-lock text-golden"></i> Premium <span class="count"><?php echo $total_paid; ?></span>
        </button>
        <?php if($is_logged_in): ?>
        <button class="filter-btn" onclick="filterMaterials('unlocked', this)">
            <i class="fas fa-key text-whatsapp"></i> My Access <span class="count"><?php echo $unlocked_paid; ?></span>
        </button>
        <?php endif; ?>
    </div>

    <!-- 📚 Materials by Category -->
    <?php foreach($materials_by_category as $category => $files): 
        $category_icons = [
            'Biology' => 'fa-dna', 'Chemistry' => 'fa-flask', 'Physics' => 'fa-atom',
            'English' => 'fa-book', 'Mathematics' => 'fa-calculator',
            'Nursing Notes' => 'fa-user-nurse', 'Past Papers' => 'fa-file-alt',
            'General' => 'fa-folder'
        ];
        $icon = $category_icons[$category] ?? 'fa-folder';
    ?>
    <section class="category-section" data-category="<?php echo strtolower(htmlspecialchars($category)); ?>">
        <div class="category-header">
            <div class="category-icon">
                <i class="fas <?php echo $icon; ?>"></i>
            </div>
            <div>
                <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
                <span class="category-count"><?php echo count($files); ?> material<?php echo count($files) > 1 ? 's' : ''; ?></span>
            </div>
        </div>
        
        <div class="materials-grid">
            <?php foreach($files as $mat): 
                $ext = strtolower(pathinfo($mat['file_name'], PATHINFO_EXTENSION));
                $iconClass = match($ext) {
                    'pdf' => 'pdf', 'doc', 'docx' => 'doc',
                    'ppt', 'pptx' => 'ppt', 'zip', 'rar' => 'zip',
                    default => 'other'
                };
                $iconFA = match($ext) {
                    'pdf' => 'fa-file-pdf', 'doc', 'docx' => 'fa-file-word',
                    'ppt', 'pptx' => 'fa-file-powerpoint', 'zip', 'rar' => 'fa-file-zipper',
                    default => 'fa-file'
                };
                $is_paid = $mat['type'] === 'paid';
                $is_locked = $is_paid && empty($mat['has_access']);
                $is_unlocked = $is_paid && !empty($mat['has_access']);
                
                // WhatsApp message for this specific material
         $wa_message = urlencode("Assalam ualikum! I want access to this premium material:\n\n📚 *{$mat['title']}*\n📁 Category: {$mat['category']}\n\nPlease grant me access.");
$wa_link = "https://wa.me/{$admin_whatsapp}?text={$wa_message}";

            ?>
            <article class="material-card <?php echo $is_paid ? 'paid' : 'free'; ?> <?php echo $is_locked ? 'locked' : ($is_unlocked ? 'unlocked' : ''); ?> animate-in"
                     data-type="<?php echo $mat['type']; ?>"
                     data-access="<?php echo $is_unlocked ? 'yes' : 'no'; ?>">
                
                <!-- ✅ Lock Overlay with WhatsApp CTA (Shows on Hover) -->
                <?php if($is_locked): ?>
                <div class="lock-overlay">
                    <i class="fas fa-lock"></i>
                    <div class="lock-overlay-text">
                        <h4>Premium Content Locked</h4>
                        <p>Contact admin to unlock this material</p>
                    </div>
                    <a href="<?php echo $wa_link; ?>" 
                       class="whatsapp-cta" 
                       target="_blank" 
                       rel="noopener">
                        <i class="fab fa-whatsapp"></i>
                        Request Access on WhatsApp
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="card-header">
                    <span class="card-type-badge <?php echo $is_paid ? 'paid ' . ($is_locked ? 'locked' : 'unlocked') : 'free'; ?>">
                        <i class="fas fa-<?php echo $is_locked ? 'lock' : ($is_unlocked ? 'key' : 'unlock'); ?>"></i>
                        <?php echo $is_locked ? 'Premium • Locked' : ($is_unlocked ? 'Premium • Unlocked' : 'Free'); ?>
                    </span>
                    
                    <div class="card-file-icon <?php echo $iconClass; ?>">
                        <i class="fas <?php echo $iconFA; ?>"></i>
                    </div>
                    
                    <h3 class="card-title"><?php echo htmlspecialchars($mat['title']); ?></h3>
                    <span class="card-category"><?php echo htmlspecialchars($mat['category']); ?></span>
                </div>
                
                <div class="card-body">
                    <?php if(!empty($mat['description'])): ?>
                    <p class="card-description">
                        <?php echo htmlspecialchars(substr($mat['description'], 0, 100)) . (strlen($mat['description']) > 100 ? '...' : ''); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="card-meta">
                        <span class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($mat['created_at'])); ?>
                        </span>
                        <?php if($is_paid && !empty($mat['enrolled_count'])): ?>
                        <span class="meta-item">
                            <i class="fas fa-users"></i>
                            <?php echo $mat['enrolled_count']; ?> students
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Footer: FREE -->
                <?php if(!$is_paid): ?>
                <div class="card-footer free">
                    <span class="file-ext"><?php echo strtoupper($ext); ?></span>
                    <a href="<?php echo $free_drive_link; ?>" 
                       class="btn-download" 
                       target="_blank" 
                       rel="noopener"
                       title="Opens Google Drive">
                        <i class="fas fa-download"></i>
                        Open
                    </a>
                </div>
                
                <!-- ✅ Footer: PAID LOCKED (WhatsApp Button on Hover) -->
                <?php elseif($is_locked): ?>
                <div class="card-footer paid locked">
                    <div class="locked-message">
                        <i class="fas fa-lock"></i>
                        <span>Hover to request access</span>
                    </div>
                    <a href="<?php echo $wa_link; ?>" 
                       class="btn-whatsapp" 
                       target="_blank" 
                       rel="noopener"
                       title="Request access via WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                        Request Access on WhatsApp
                    </a>
                    <span class="whatsapp-hint">Admin will grant access after verification</span>
                </div>
                
                <!-- Footer: PAID UNLOCKED -->
                <?php else: ?>
                <div class="card-footer paid unlocked">
                    <span class="file-ext"><?php echo strtoupper($ext); ?></span>
                    <a href="download_material.php?id=<?php echo $mat['material_id']; ?>" 
                       class="btn-download"
                       title="Download your premium material">
                        <i class="fas fa-download"></i>
                        Download
                    </a>
                </div>
                <?php endif; ?>
                
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <!-- 🔔 Final CTA -->
    <div class="text-center" style="margin-top: var(--space-xl); padding-bottom: var(--space-xl);">
        <p style="color: var(--gray-light); margin-bottom: var(--space-md);">
            Need help or have questions about premium access?
        </p>
        <a href="https://wa.me/<?php echo $admin_whatsapp; ?>?text=Assalam%20ualikum!%20I%20have%20a%20question%20about%20MEDDEMY%20materials." 
           class="btn-whatsapp-main" 
           style="padding: var(--space-sm) var(--space-lg); font-size: 0.95rem;"
           target="_blank" 
           rel="noopener">
            <i class="fab fa-whatsapp"></i>
            Chat with Admin
        </a>
    </div>

</main>

<!-- ✅ Footer -->
<footer class="page-footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> MEDDEMY. All rights reserved.</p>
        <p>Free materials provided with ❤️ • Premium access via WhatsApp</p>
        <p style="margin-top: var(--space-sm);">
            Having trouble? <a href="../support.php">Contact Support</a>
        </p>
    </div>
</footer>

<!-- ✅ JavaScript -->
<script>
// Navbar scroll effect
window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
});

// Mobile menu toggle
document.getElementById('navToggle').addEventListener('click', () => {
    document.getElementById('navbarNav').classList.toggle('active');
});

// Close mobile menu on link click
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        document.getElementById('navbarNav').classList.remove('active');
    });
});

// Filter Materials by Type
function filterMaterials(type, btn) {
    // Update active button
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    // Show/hide cards
    document.querySelectorAll('.material-card').forEach(card => {
        const cardType = card.dataset.type;
        const hasAccess = card.dataset.access;
        
        if(type === 'all') {
            card.style.display = 'flex';
        } else if(type === 'free' && cardType === 'free') {
            card.style.display = 'flex';
        } else if(type === 'paid' && cardType === 'paid') {
            card.style.display = 'flex';
        } else if(type === 'unlocked' && cardType === 'paid' && hasAccess === 'yes') {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide category sections if all cards hidden
    document.querySelectorAll('.category-section').forEach(section => {
        const visibleCards = section.querySelectorAll('.material-card[style="display: flex;"], .material-card:not([style])');
        let hasVisible = false;
        visibleCards.forEach(card => {
            if(card.style.display !== 'none') hasVisible = true;
        });
        section.style.display = hasVisible ? 'block' : 'none';
    });
}

// Animate cards on scroll
const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if(entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

document.querySelectorAll('.material-card').forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = `opacity 0.4s ease ${index * 0.05}s, transform 0.4s ease ${index * 0.05}s`;
    observer.observe(card);
});

// Track clicks (optional analytics)
document.querySelectorAll('a[href*="drive.google.com"], a[href*="wa.me"]').forEach(link => {
    link.addEventListener('click', (e) => {
        console.log('Link clicked:', link.href);
        // Add Google Analytics: gtag('event', 'click', { ... })
    });
});
</script>

</body>
</html>