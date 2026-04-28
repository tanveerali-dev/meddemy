<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$sql = "SELECT course.* FROM course 
        INNER JOIN enrollment ON course.course_id = enrollment.course_id 
        WHERE enrollment.student_id = '$student_id'";
$result = $conn->query($sql);

$course_count = $result->num_rows;

// Latest announcements (3)
$latest_posts = $conn->query("SELECT * FROM post ORDER BY created_at DESC LIMIT 3");

// FREE materials
$free_mats = $conn->query("SELECT * FROM material WHERE type='free' ORDER BY created_at DESC LIMIT 6");
$free_count = $conn->query("SELECT COUNT(*) as c FROM material WHERE type='free'")->fetch_assoc()['c'];

// PAID materials this student has access to
$paid_stmt = $conn->prepare("SELECT m.* FROM material m JOIN material_access ma ON ma.material_id=m.material_id WHERE ma.student_id=? AND m.type='paid' ORDER BY m.created_at DESC LIMIT 6");
$paid_stmt->bind_param("i", $student_id);
$paid_stmt->execute();
$paid_mats   = $paid_stmt->get_result();
$paid_count  = $paid_mats->num_rows;

// Total paid materials (to show locked ones)
$total_paid_stmt = $conn->prepare("SELECT m.* FROM material m WHERE m.type='paid' AND m.material_id NOT IN (SELECT material_id FROM material_access WHERE student_id=?) ORDER BY m.created_at DESC");
$total_paid_stmt->bind_param("i", $student_id);
$total_paid_stmt->execute();
$locked_mats = $total_paid_stmt->get_result();

// Admin WhatsApp number
require_once("../includes/config.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — MEDDEMY</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold: #FFD700;
            --gold-soft: #ffed4e;
            --bg: #0c0c0c;
            --surface: #141414;
            --surface2: #1a1a1a;
            --border: rgba(255,215,0,0.12);
            --border2: #222;
            --text: #ffffff;
            --text-muted: rgba(255,255,255,0.45);
            --radius: 16px;
            --transition: all 0.28s cubic-bezier(0.4,0,0.2,1);
            --sidebar-w: 240px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .sidebar-brand img { height: 36px; }

        .sidebar-brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--gold);
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 10px 12px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: rgba(255,255,255,0.6);
            font-size: 0.88rem;
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
        }

        .nav-item i {
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .nav-item:hover {
            background: rgba(255,215,0,0.08);
            color: var(--gold);
        }

        .nav-item.active {
            background: rgba(255,215,0,0.12);
            color: var(--gold);
            font-weight: 600;
        }

        .nav-item.active i { color: var(--gold); }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid var(--border);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: rgba(255,100,100,0.7);
            font-size: 0.88rem;
            font-weight: 500;
            transition: var(--transition);
            width: 100%;
            background: none;
            border: none;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
        }

        .logout-btn:hover {
            background: rgba(255,80,80,0.1);
            color: #ff6b6b;
        }

        /* ── TOPBAR ── */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: 64px;
            background: rgba(12,12,12,0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            z-index: 99;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 6px;
        }

        .topbar-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .student-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 6px 14px 6px 6px;
        }

        .student-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-soft));
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.78rem; color: #111;
            flex-shrink: 0;
        }

        .student-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text);
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            margin-left: var(--sidebar-w);
            padding-top: 64px;
            min-height: 100vh;
        }

        .page-body {
            padding: 32px 28px;
            max-width: 1200px;
        }

        /* ── WELCOME BANNER ── */
        .welcome-banner {
            background: linear-gradient(135deg, #1a1500 0%, #1f1a00 50%, #111 100%);
            border: 1px solid rgba(255,215,0,0.2);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(255,215,0,0.1), transparent 65%);
            pointer-events: none;
        }

        .welcome-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.3rem, 3vw, 1.7rem);
            font-weight: 900;
            color: var(--text);
            margin-bottom: 6px;
        }

        .welcome-text h2 span { color: var(--gold); }

        .welcome-text p {
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .welcome-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,215,0,0.08);
            border: 1px solid rgba(255,215,0,0.2);
            border-radius: 50px;
            padding: 10px 20px;
            flex-shrink: 0;
        }

        .welcome-badge i { color: var(--gold); font-size: 1rem; }

        .welcome-badge span {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gold);
        }

        /* ── STAT CARDS ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
        }

        .stat-card:hover {
            border-color: rgba(255,215,0,0.25);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            background: rgba(255,215,0,0.1);
            border: 1px solid rgba(255,215,0,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            color: var(--gold);
            flex-shrink: 0;
        }

        .stat-info { flex: 1; min-width: 0; }

        .stat-val {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-lbl {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* ── SECTION HEADER ── */
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .section-head h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text);
        }

        .section-head span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* ── COURSE CARDS ── */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .course-card {
            background: var(--surface);
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            animation: fadeUp 0.4s ease both;
        }

        .course-card:nth-child(1) { animation-delay: 0.05s; }
        .course-card:nth-child(2) { animation-delay: 0.1s; }
        .course-card:nth-child(3) { animation-delay: 0.15s; }
        .course-card:nth-child(4) { animation-delay: 0.2s; }
        .course-card:nth-child(5) { animation-delay: 0.25s; }
        .course-card:nth-child(6) { animation-delay: 0.3s; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .course-card:hover {
            border-color: rgba(255,215,0,0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
        }

        .course-img-wrap {
            width: 100%;
            aspect-ratio: 16/9;
            overflow: hidden;
            background: #1a1a1a;
            flex-shrink: 0;
        }

        .course-img-wrap img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .course-card:hover .course-img-wrap img {
            transform: scale(1.06);
        }

        .course-body {
            padding: 18px 16px 14px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            gap: 8px;
        }

        .course-tag {
            display: inline-block;
            background: rgba(255,215,0,0.1);
            color: var(--gold);
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 50px;
            border: 1px solid rgba(255,215,0,0.2);
            width: fit-content;
        }

        .course-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text);
            line-height: 1.3;
        }

        .course-desc {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.6;
            flex-grow: 1;
        }

        /* Progress bar */
        .progress-wrap { margin-top: 4px; }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .progress-bar-bg {
            height: 4px;
            background: var(--border2);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--gold), var(--gold-soft));
            border-radius: 4px;
            width: 0%;
            transition: width 1s ease;
        }

        .course-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--border2);
            background: rgba(255,255,255,0.02);
        }

        .btn-view {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, var(--gold), var(--gold-soft));
            border: none;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.86rem;
            font-weight: 700;
            color: #111;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-view:hover {
            box-shadow: 0 4px 18px rgba(255,215,0,0.4);
            transform: translateY(-1px);
            color: #111;
        }

        .btn-view i { font-size: 0.8rem; transition: transform 0.25s; }
        .btn-view:hover i { transform: translateX(3px); }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 48px 28px 52px;
            background: linear-gradient(135deg, #0d0b00, #141000, #0c0c0c);
            border: 1px solid rgba(255,215,0,.15);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }
        .empty-state::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(255,215,0,.06), transparent 65%);
            pointer-events: none;
        }
        .empty-state::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -40px;
            width: 160px; height: 160px;
            background: radial-gradient(circle, rgba(37,211,102,.05), transparent 65%);
            pointer-events: none;
        }
        .empty-state i {
            font-size: 2.8rem;
            color: rgba(255,215,0,.3);
            margin-bottom: 16px;
            display: block;
        }
        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }
        .empty-state p {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-bottom: 24px;
            line-height: 1.6;
            max-width: 360px;
            margin-left: auto;
            margin-right: auto;
        }
        .empty-wa-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 13px 26px;
            background: linear-gradient(135deg, #25d366, #1aaa52);
            color: #fff;
            border-radius: 12px;
            font-size: .92rem;
            font-weight: 700;
            text-decoration: none;
            transition: all .28s ease;
            box-shadow: 0 6px 22px rgba(37,211,102,.25);
            position: relative;
            z-index: 1;
        }
        .empty-wa-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 32px rgba(37,211,102,.4);
        }
        .empty-wa-btn i { font-size: 1.1rem; }
        .empty-wa-note {
            font-size: .75rem;
            color: var(--text-muted);
            margin-top: 14px;
            position: relative;
            z-index: 1;
        }
        .empty-wa-note strong { color: rgba(255,215,0,.7); }

        /* ── FOOTER ── */
        .page-footer {
            margin-top: 48px;
            padding: 20px 28px;
            border-top: 1px solid var(--border);
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 991px) {
            .courses-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            :root { --sidebar-w: 240px; }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: 0 0 0 100vw rgba(0,0,0,0.5);
            }

            .topbar { left: 0; }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .page-body { padding: 24px 16px; }
        }

        @media (max-width: 540px) {
            .courses-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr; }
            .welcome-banner { padding: 22px 20px; }
        }

        /* ── Materials Section ── */
        .mat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 10px;
            margin-bottom: 6px;
        }
        .mat-card {
            background: var(--surface);
            border: 1px solid var(--border2);
            border-radius: 13px;
            overflow: hidden;
            transition: var(--transition);
        }
        .mat-card:hover { border-color: rgba(255,215,0,.25); transform: translateY(-2px); }
        .mat-card-inner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
        }
        .mat-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .mat-icon.pdf  { background: rgba(255,77,77,.1);  color: #ff4d4d; }
        .mat-icon.doc  { background: rgba(59,130,246,.1); color: #3b82f6; }
        .mat-icon.ppt  { background: rgba(249,115,22,.1); color: #f97316; }
        .mat-icon.other{ background: rgba(255,255,255,.06); color: rgba(255,255,255,.4); }
        .mat-info { flex: 1; min-width: 0; }
        .mat-title {
            font-size: .84rem; font-weight: 700;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .mat-cat { font-size: .7rem; color: var(--text-muted); margin-top: 2px; }
        .mat-dl-btn {
            width: 32px; height: 32px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: .78rem;
            flex-shrink: 0; transition: var(--transition);
        }
        .mat-dl-btn.free { background: rgba(34,197,94,.12); color: #22c55e; }
        .mat-dl-btn.free:hover { background: rgba(34,197,94,.25); transform: scale(1.1); }
        .mat-dl-btn.paid { background: rgba(255,215,0,.12); color: var(--gold); }
        .mat-dl-btn.paid:hover { background: rgba(255,215,0,.25); transform: scale(1.1); }
        .mat-lock-icon {
            width: 32px; height: 32px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,.05); color: rgba(255,255,255,.25);
            font-size: .78rem; flex-shrink: 0;
        }
        .mat-empty {
            display: flex; align-items: center; gap: 10px;
            padding: 16px 18px;
            background: var(--surface);
            border: 1px dashed var(--border2);
            border-radius: 13px;
            color: var(--text-muted);
            font-size: .83rem;
        }
        .mat-empty i { font-size: 1.1rem; opacity: .4; }
        .paid-unlocked { border-color: rgba(255,215,0,.18); }

        /* Locked section with WhatsApp CTA */
        .locked-section {
            background: linear-gradient(135deg, #0d0b00, #161200);
            border: 1px solid rgba(255,215,0,.18);
            border-radius: 16px;
            overflow: hidden;
        }
        .locked-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255,215,0,.1);
            flex-wrap: wrap;
        }
        .locked-header > i {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: rgba(255,215,0,.1);
            color: var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-size: .95rem; flex-shrink: 0;
        }
        .locked-header > i { display: flex; }
        .locked-header > div { flex: 1; min-width: 0; }
        .wa-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            background: linear-gradient(135deg, #25d366, #1fbe5a);
            color: #fff;
            border-radius: 10px;
            font-size: .83rem;
            font-weight: 700;
            text-decoration: none;
            flex-shrink: 0;
            transition: var(--transition);
            box-shadow: 0 4px 14px rgba(37,211,102,.25);
        }
        .wa-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37,211,102,.4); }
        .wa-btn i { font-size: 1rem; }
        .locked-grid { padding: 14px; gap: 8px; }
        .locked-card { opacity: .75; }
        .locked-card:hover { transform: none; border-color: var(--border2); }

        @media(max-width:600px) {
            .mat-grid { grid-template-columns: 1fr; }
            .locked-header { gap: 10px; }
            .wa-btn { width: 100%; justify-content: center; }
        }

    </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <a href="../index.php" class="sidebar-brand">
        <img src="../assets/images/logo44.png" alt="MEDDEMY">
        <span class="sidebar-brand-name">MEDDEMY</span>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="dashboard.php" class="nav-item active">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-book-open"></i> My Courses
        </a>

        <div class="nav-label" style="margin-top:10px;">Resources</div>
        <a href="materials.php" class="nav-item">
            <i class="fas fa-folder-open"></i> Study Materials
        </a>

        <div class="nav-label" style="margin-top:10px;">Account</div>
        <a href="#" class="nav-item">
            <i class="fas fa-user-circle"></i> Back to Home
        </a>
       
       
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<!-- ── TOPBAR ── -->
<header class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <span class="topbar-title">Student Dashboard</span>
    </div>
    <div class="topbar-right">
        <div class="student-chip">
            <div class="student-avatar">
                <?php echo strtoupper(substr($_SESSION['student_name'], 0, 1)); ?>
            </div>
            <span class="student-name"><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
        </div>
    </div>
</header>

<!-- ── MAIN ── -->
<main class="main-content">
    <div class="page-body">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>Welcome back, <span><?php echo htmlspecialchars(explode(' ', $_SESSION['student_name'])[0]); ?></span> 👋</h2>
                <p>Continue your learning journey — your success is our mission.</p>
            </div>
            <div class="welcome-badge">
                <i class="fas fa-graduation-cap"></i>
                <span><?php echo $course_count; ?> Course<?php echo $course_count != 1 ? 's' : ''; ?> Enrolled</span>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                <div class="stat-info">
                    <div class="stat-val"><?php echo $course_count; ?></div>
                    <div class="stat-lbl">Enrolled Courses</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-video"></i></div>
                <div class="stat-info">
                    <div class="stat-val">∞</div>
                    <div class="stat-lbl">Videos Watched</div>
                </div>
            </div>
          
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(255,215,0,.12);color:var(--gold);"><i class="fas fa-folder-open"></i></div>
                <div class="stat-info">
                    <div class="stat-val"><?php echo $free_count + $paid_count; ?></div>
                    <div class="stat-lbl">Study Materials</div>
                </div>
            </div>
        </div>

        <!-- Courses -->
        <div class="section-head">
            <h3>My Courses</h3>
            <span><?php echo $course_count; ?> course<?php echo $course_count != 1 ? 's' : ''; ?> enrolled</span>
        </div>

        <?php
        // Reset result pointer
        $result->data_seek(0);
        ?>

        <?php if ($course_count > 0): ?>
        <div class="courses-grid">
            <?php while($row = $result->fetch_assoc()) { ?>
            <div class="course-card">
                <div class="course-img-wrap">
                    <img src="../assets/images/<?php echo htmlspecialchars($row['image']); ?>"
                         alt="<?php echo htmlspecialchars($row['title']); ?>"
                         loading="lazy">
                </div>
                <div class="course-body">
                    <span class="course-tag">Enrolled</span>
                    <div class="course-title"><?php echo htmlspecialchars($row['title']); ?></div>
                    <div class="course-desc"><?php echo htmlspecialchars(substr($row['description'], 0, 85)) . '...'; ?></div>
                    <div class="progress-wrap">
                        <!-- <div class="progress-label">
                            <span>Progress</span>
                            <span>0%</span>
                        </div> -->
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width:0%"></div>
                        </div>
                    </div>
                </div>
                <div class="course-footer">
                    <a href="view_course.php?id=<?php echo $row['course_id']; ?>" class="btn-view">
                        Continue Learning <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php } ?>
        </div>

        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-graduation-cap"></i>
            <h3>No Courses Yet</h3>
            <p>You are registered but haven't been enrolled in any course yet. Contact admin on WhatsApp to get enrolled.</p>
            <a href="https://wa.me/<?php echo defined('ADMIN_WHATSAPP') ? ADMIN_WHATSAPP : '923001234567'; ?>?text=<?php echo urlencode('Assalam o Alaikum! I have registered on MEDDEMY. My name is '.(isset($_SESSION['student_name']) ? $_SESSION['student_name'] : 'Student').'. Please enroll me in a course.'); ?>"
               target="_blank" class="empty-wa-btn">
                <i class="fab fa-whatsapp"></i>
                Contact Admin on WhatsApp
            </a>
            <div class="empty-wa-note">
                Admin will enroll you in your selected course — <strong>usually within 1 hour</strong>
            </div>
        </div>
        <?php endif; ?>


        <!-- ══════════════════════════════
             STUDY MATERIALS SECTION
        ══════════════════════════════ -->

        <!-- ══ LATEST ANNOUNCEMENTS ══ -->
        <?php if ($latest_posts->num_rows > 0): ?>
        <div class="section-head" style="margin-top:32px;">
            <h3><i class="fas fa-bullhorn" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>Latest Announcements</h3>
            <a href="../announcements.php" style="font-size:.8rem;color:var(--gold);text-decoration:none;font-weight:600;">View All →</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:9px;margin-bottom:8px;">
        <?php
        $badge_colors = [
            'gold'  => ['bg'=>'rgba(255,215,0,.1)', 'border'=>'rgba(255,215,0,.2)', 'text'=>'#FFD700'],
            'green' => ['bg'=>'rgba(34,197,94,.1)',  'border'=>'rgba(34,197,94,.2)',  'text'=>'#22c55e'],
            'blue'  => ['bg'=>'rgba(59,130,246,.1)', 'border'=>'rgba(59,130,246,.2)', 'text'=>'#3b82f6'],
            'red'   => ['bg'=>'rgba(255,77,77,.1)',  'border'=>'rgba(255,77,77,.2)',  'text'=>'#ff4d4d'],
        ];
        while($post = $latest_posts->fetch_assoc()):
            $col = $badge_colors[$post['badge_color']] ?? $badge_colors['gold'];
        ?>
        <a href="../announcements.php?id=<?php echo $post['post_id']; ?>"
           style="display:flex;align-items:center;gap:13px;background:var(--surface);border:1px solid var(--border2);border-radius:13px;padding:13px 15px;text-decoration:none;color:inherit;transition:var(--transition);"
           onmouseover="this.style.borderColor='rgba(255,215,0,.25)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.borderColor='var(--border2)';this.style.transform='none'">
            <div style="width:36px;height:36px;border-radius:9px;background:<?php echo $col['bg']; ?>;border:1px solid <?php echo $col['border']; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-bullhorn" style="color:<?php echo $col['text']; ?>;font-size:.78rem;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.85rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($post['title']); ?></div>
                <div style="font-size:.71rem;color:var(--text-muted);margin-top:2px;"><?php echo date('d M Y', strtotime($post['created_at'])); ?></div>
            </div>
            <span style="font-size:.63rem;font-weight:700;letter-spacing:.7px;text-transform:uppercase;padding:2px 9px;border-radius:20px;background:<?php echo $col['bg']; ?>;color:<?php echo $col['text']; ?>;border:1px solid <?php echo $col['border']; ?>;flex-shrink:0;">
                <?php echo htmlspecialchars($post['badge']); ?>
            </span>
            <i class="fas fa-chevron-right" style="font-size:.65rem;color:var(--text-muted);flex-shrink:0;"></i>
        </a>
        <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- FREE MATERIALS -->
        <div class="section-head" style="margin-top:32px;">
            <h3><i class="fas fa-unlock" style="color:#22c55e;margin-right:8px;font-size:.85rem;"></i>Free Study Materials</h3>
            <a href="materials.php" style="font-size:.8rem;color:var(--gold);text-decoration:none;font-weight:600;">View All →</a>
        </div>

        <?php if ($free_count > 0): $free_mats->data_seek(0); ?>
        <div class="mat-grid">
            <?php while($m = $free_mats->fetch_assoc()):
                $ext = strtolower(pathinfo($m['file_name'], PATHINFO_EXTENSION));
                $iconClass = match($ext) { 'pdf'=>'pdf', 'doc','docx'=>'doc', 'ppt','pptx'=>'ppt', default=>'other' };
                $iconFA = match($ext) { 'pdf'=>'fa-file-pdf', 'doc','docx'=>'fa-file-word', 'ppt','pptx'=>'fa-file-powerpoint', default=>'fa-file' };
            ?>
            <div class="mat-card">
                <div class="mat-card-inner">
                    <div class="mat-icon <?php echo $iconClass; ?>">
                        <i class="fas <?php echo $iconFA; ?>"></i>
                    </div>
                    <div class="mat-info">
                        <div class="mat-title"><?php echo htmlspecialchars($m['title']); ?></div>
                        <div class="mat-cat"><?php echo htmlspecialchars($m['category']); ?> &middot; <?php echo strtoupper($ext); ?></div>
                    </div>
                    <a href="download_material.php?id=<?php echo $m['material_id']; ?>"
                       class="mat-dl-btn free" title="Download">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="mat-empty">
            <i class="fas fa-folder-open"></i>
            <span>No free materials yet — check back soon!</span>
        </div>
        <?php endif; ?>

        <!-- PAID MATERIALS -->
        <div class="section-head" style="margin-top:28px;">
            <h3><i class="fas fa-lock" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>Premium Materials</h3>
            <a href="materials.php#paid" style="font-size:.8rem;color:var(--gold);text-decoration:none;font-weight:600;">View All →</a>
        </div>

        <?php
        // Reset for display
        $paid_mats->data_seek(0);
        $locked_mats->data_seek(0);
        $has_paid   = $paid_mats->num_rows > 0;
        $has_locked = $locked_mats->num_rows > 0;
        ?>

        <?php if ($has_paid): ?>
        <div class="mat-grid" style="margin-bottom:14px;">
            <?php while($m = $paid_mats->fetch_assoc()):
                $ext = strtolower(pathinfo($m['file_name'], PATHINFO_EXTENSION));
                $iconFA = match($ext) { 'pdf'=>'fa-file-pdf', 'doc','docx'=>'fa-file-word', 'ppt','pptx'=>'fa-file-powerpoint', default=>'fa-file' };
                $iconClass = match($ext) { 'pdf'=>'pdf', 'doc','docx'=>'doc', 'ppt','pptx'=>'ppt', default=>'other' };
            ?>
            <div class="mat-card paid-unlocked">
                <div class="mat-card-inner">
                    <div class="mat-icon <?php echo $iconClass; ?>">
                        <i class="fas <?php echo $iconFA; ?>"></i>
                    </div>
                    <div class="mat-info">
                        <div class="mat-title"><?php echo htmlspecialchars($m['title']); ?></div>
                        <div class="mat-cat"><?php echo htmlspecialchars($m['category']); ?> &middot; <?php echo strtoupper($ext); ?></div>
                    </div>
                    <a href="download_material.php?id=<?php echo $m['material_id']; ?>"
                       class="mat-dl-btn paid" title="Download">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <?php if ($has_locked): ?>
        <!-- Locked paid materials with WhatsApp CTA -->
        <div class="locked-section">
            <div class="locked-header">
                <i class="fas fa-lock"></i>
                <div>
                    <div style="font-weight:700;font-size:.88rem;">
                        <?php echo $locked_mats->num_rows; ?> Premium Material<?php echo $locked_mats->num_rows > 1 ? 's' : ''; ?> Available
                    </div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;">
                        Contact admin on WhatsApp to get access
                    </div>
                </div>
                <a href="https://wa.me/<?php echo ADMIN_WHATSAPP; ?>?text=<?php echo urlencode('Assalam o Alaikum! I want to get access to premium study materials on MEDDEMY. My name is '.$_SESSION['student_name'].' and my email is '.(isset($_SESSION['student_email']) ? $_SESSION['student_email'] : '')); ?>"
                   target="_blank" class="wa-btn">
                    <i class="fab fa-whatsapp"></i> Request Access
                </a>
            </div>

            <!-- Show locked material cards -->
            <div class="mat-grid locked-grid">
                <?php $locked_mats->data_seek(0); while($m = $locked_mats->fetch_assoc()):
                    $ext = strtolower(pathinfo($m['file_name'], PATHINFO_EXTENSION));
                    $iconFA = match($ext) { 'pdf'=>'fa-file-pdf', 'doc','docx'=>'fa-file-word', 'ppt','pptx'=>'fa-file-powerpoint', default=>'fa-file' };
                    $iconClass = match($ext) { 'pdf'=>'pdf', 'doc','docx'=>'doc', 'ppt','pptx'=>'ppt', default=>'other' };
                ?>
                <div class="mat-card locked-card">
                    <div class="mat-card-inner">
                        <div class="mat-icon <?php echo $iconClass; ?>" style="opacity:.5;">
                            <i class="fas <?php echo $iconFA; ?>"></i>
                        </div>
                        <div class="mat-info">
                            <div class="mat-title" style="opacity:.55;"><?php echo htmlspecialchars($m['title']); ?></div>
                            <div class="mat-cat"><?php echo htmlspecialchars($m['category']); ?> &middot; <?php echo strtoupper($ext); ?></div>
                        </div>
                        <div class="mat-lock-icon"><i class="fas fa-lock"></i></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php elseif (!$has_paid): ?>
        <div class="mat-empty">
            <i class="fas fa-lock"></i>
            <span>No premium materials available yet.</span>
        </div>
        <?php endif; ?>


    </div>

    <footer class="page-footer">
        &copy; <?php echo date("Y"); ?> MEDDEMY. All rights reserved. Made with <span style="color:#e74c3c">♥</span> for students across Pakistan.
    </footer>
</main>

<script>
    // Sidebar toggle for mobile
    const toggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    // Close sidebar on outside click
    document.addEventListener('click', (e) => {
        if (sidebar.classList.contains('open') &&
            !sidebar.contains(e.target) &&
            !toggle.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });
</script>
</body>
</html>