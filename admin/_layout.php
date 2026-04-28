<?php
/**
 * _layout.php  —  Shared admin shell
 * Usage:
 *   $page_title  = "Dashboard";
 *   $active_nav  = "dashboard";   // matches $nav items key
 *   include '_layout.php';        // outputs <head> + sidebar + topbar
 *   ... your page content ...
 *   include '_layout_end.php';    // closes divs + </body></html>
 */

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_init = strtoupper(substr($admin_name, 0, 1));

$nav = [
    'dashboard'       => ['icon'=>'fa-th-large',       'label'=>'Dashboard',       'href'=>'dashboard.php'],
    'add_course'      => ['icon'=>'fa-plus-circle',     'label'=>'Add Course',      'href'=>'add_course.php'],
    'manage_courses'  => ['icon'=>'fa-book-open',       'label'=>'Manage Courses',  'href'=>'manage_courses.php'],
    'add_video'       => ['icon'=>'fa-video',           'label'=>'Add Video',       'href'=>'add_video.php'],
    'manage_students' => ['icon'=>'fa-users',           'label'=>'Manage Students', 'href'=>'manage_students.php'],
    'enroll_student'  => ['icon'=>'fa-user-plus',       'label'=>'Enroll Student',  'href'=>'enroll_student.php'],
    'manage_videos'   => ['icon'=>'fa-film',           'label'=>'Manage Videos',   'href'=>'manage_videos.php'],
    'manage_materials' => ['icon'=>'fa-folder-open',   'label'=>'Materials',        'href'=>'manage_materials.php'],
    'manage_posts'     => ['icon'=>'fa-bullhorn',       'label'=>'Announcements',   'href'=>'manage_posts.php'],
    'manage_books'     => ['icon'=>'fa-book-open',     'label'=>'Books Store',      'href'=>'manage_books.php'],
    'manage_quizzes'   => ['icon'=>'fa-question-circle', 'label'=>'Quizzes',          'href'=>'manage_quizzes.php'],
    'manage_subjects'  => ['icon'=>'fa-layer-group',     'label'=>'Subjects',         'href'=>'manage_subjects.php'],
    'manage_units'     => ['icon'=>'fa-list-ol',        'label'=>'Units',            'href'=>'manage_units.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($page_title ?? 'Admin'); ?> — MEDDEMY Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ════════════════════════════════════════════════
   MEDDEMY ADMIN  —  Global Shell Styles
   ════════════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
    --gold:#FFD700; --gold2:#ffed4e; --gold-dim:rgba(255,215,0,.12);
    --bg:#080808; --s1:#111; --s2:#171717; --s3:#1e1e1e; --s4:#252525;
    --border:rgba(255,215,0,.1); --border2:#222; --border3:#2a2a2a;
    --text:#f0f0f0; --muted:rgba(255,255,255,.42); --muted2:rgba(255,255,255,.22);
    --red:#ff4d4d; --green:#22c55e; --blue:#3b82f6;
    --sidebar:220px;
    --radius:12px; --radius-lg:18px;
    --ease:.26s cubic-bezier(.4,0,.2,1);
    --shadow:0 8px 32px rgba(0,0,0,.45);
}

body{
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

/* ── SCROLLBAR ── */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--s1)}
::-webkit-scrollbar-thumb{background:#333;border-radius:5px}
::-webkit-scrollbar-thumb:hover{background:rgba(255,215,0,.3)}

/* ══ SIDEBAR ══ */
.adm-sidebar{
    position:fixed;top:0;left:0;
    width:var(--sidebar);height:100vh;
    background:var(--s1);
    border-right:1px solid var(--border);
    display:flex;flex-direction:column;
    z-index:200;
    transition:transform .3s ease;
}

.adm-brand{
    padding:20px 16px 18px;
    border-bottom:1px solid var(--border);
    display:flex;align-items:center;gap:10px;
    text-decoration:none;
}
.adm-brand img{height:30px;}
.adm-brand-name{
    font-family:'Playfair Display',serif;
    font-size:1.05rem;font-weight:900;
    color:var(--gold);letter-spacing:.3px;
}
.adm-brand-badge{
    font-size:.58rem;font-weight:700;letter-spacing:1.2px;
    background:rgba(255,215,0,.12);color:var(--gold);
    border:1px solid rgba(255,215,0,.25);
    padding:2px 7px;border-radius:20px;
    text-transform:uppercase;margin-left:auto;flex-shrink:0;
}

.adm-nav{flex:1;padding:12px 8px;overflow-y:auto;display:flex;flex-direction:column;gap:2px;}

.nav-section-label{
    font-size:.6rem;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;
    color:var(--muted2);padding:10px 10px 4px;
}

.adm-nav-item{
    display:flex;align-items:center;gap:10px;
    padding:9px 12px;border-radius:10px;
    text-decoration:none;
    color:rgba(255,255,255,.5);
    font-size:.83rem;font-weight:500;
    transition:var(--ease);
    position:relative;
}
.adm-nav-item i{width:16px;text-align:center;font-size:.82rem;flex-shrink:0;}
.adm-nav-item:hover{background:var(--gold-dim);color:var(--text);}
.adm-nav-item.active{
    background:rgba(255,215,0,.13);
    color:var(--gold);font-weight:600;
}
.adm-nav-item.active::before{
    content:'';position:absolute;left:0;top:20%;bottom:20%;
    width:3px;background:var(--gold);border-radius:0 3px 3px 0;
}

.adm-sidebar-footer{
    padding:10px 8px;border-top:1px solid var(--border);
}
.adm-logout{
    display:flex;align-items:center;gap:9px;
    padding:9px 12px;border-radius:10px;
    color:rgba(255,90,90,.65);font-size:.83rem;font-weight:500;
    text-decoration:none;transition:var(--ease);
    width:100%;background:none;border:none;cursor:pointer;
    font-family:'DM Sans',sans-serif;
}
.adm-logout:hover{background:rgba(255,60,60,.1);color:#ff6b6b;}

/* ══ TOPBAR ══ */
.adm-topbar{
    position:fixed;top:0;left:var(--sidebar);right:0;height:54px;
    background:rgba(8,8,8,.95);
    backdrop-filter:blur(16px);
    border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;
    padding:0 24px;z-index:199;gap:12px;
}
.adm-topbar-left{display:flex;align-items:center;gap:10px;min-width:0;}
.adm-menu-toggle{
    display:none;background:none;border:none;
    color:var(--text);font-size:1.1rem;cursor:pointer;padding:4px 6px;
    border-radius:8px;transition:var(--ease);
}
.adm-menu-toggle:hover{background:var(--s3);}
.adm-page-title{
    font-family:'Playfair Display',serif;
    font-size:1rem;font-weight:700;color:var(--text);
}
.adm-breadcrumb{
    display:flex;align-items:center;gap:6px;
    font-size:.77rem;color:var(--muted);
}
.adm-breadcrumb a{color:var(--muted);text-decoration:none;transition:color .2s;}
.adm-breadcrumb a:hover{color:var(--gold);}
.adm-breadcrumb i{font-size:.55rem;}

.adm-topbar-right{display:flex;align-items:center;gap:8px;flex-shrink:0;}
.adm-chip{
    display:flex;align-items:center;gap:8px;
    background:var(--s2);border:1px solid var(--border);
    border-radius:50px;padding:5px 14px 5px 5px;
}
.adm-avatar{
    width:28px;height:28px;border-radius:50%;
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    display:flex;align-items:center;justify-content:center;
    font-weight:800;font-size:.72rem;color:#111;
}
.adm-chip-name{font-size:.78rem;font-weight:600;}
.adm-chip-role{font-size:.65rem;color:var(--gold);font-weight:600;}

/* ══ MAIN WRAP ══ */
.adm-main{
    margin-left:var(--sidebar);
    padding-top:54px;
    min-height:100vh;
    display:flex;flex-direction:column;
}
.adm-body{
    flex:1;padding:28px 28px 40px;
}

/* ══ PAGE HEADER ══ */
.adm-page-header{
    display:flex;align-items:flex-start;justify-content:space-between;
    gap:16px;margin-bottom:28px;flex-wrap:wrap;
}
.adm-page-header-left h1{
    font-family:'Playfair Display',serif;
    font-size:clamp(1.3rem,3vw,1.7rem);font-weight:900;
    margin-bottom:4px;
}
.adm-page-header-left p{font-size:.85rem;color:var(--muted);}
.adm-page-header-actions{display:flex;gap:10px;flex-wrap:wrap;}

/* ══ CARDS ══ */
.adm-card{
    background:var(--s1);
    border:1px solid var(--border2);
    border-radius:var(--radius-lg);
    overflow:hidden;
}
.adm-card-header{
    padding:18px 22px 0;
    display:flex;align-items:center;justify-content:space-between;
    gap:12px;margin-bottom:18px;
}
.adm-card-header h2{
    font-family:'Playfair Display',serif;
    font-size:1rem;font-weight:700;
}
.adm-card-body{padding:0 22px 22px;}

/* ══ STATS GRID ══ */
.adm-stats{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;margin-bottom:24px;
}
.stat-card{
    background:var(--s1);
    border:1px solid var(--border2);
    border-radius:var(--radius-lg);
    padding:20px 22px;
    position:relative;overflow:hidden;
    transition:var(--ease);
}
.stat-card:hover{border-color:rgba(255,215,0,.25);transform:translateY(-2px);}
.stat-card::after{
    content:'';position:absolute;top:-40px;right:-40px;
    width:120px;height:120px;border-radius:50%;
    opacity:.06;pointer-events:none;
}
.stat-card.gold::after{background:var(--gold);}
.stat-card.blue::after{background:var(--blue);}
.stat-card.green::after{background:var(--green);}
.stat-card.red::after{background:var(--red);}

.stat-icon{
    width:38px;height:38px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    font-size:.95rem;margin-bottom:14px;
}
.stat-card.gold .stat-icon{background:rgba(255,215,0,.1);color:var(--gold);}
.stat-card.blue .stat-icon{background:rgba(59,130,246,.12);color:var(--blue);}
.stat-card.green .stat-icon{background:rgba(34,197,94,.12);color:var(--green);}
.stat-card.red .stat-icon{background:rgba(255,77,77,.12);color:var(--red);}

.stat-val{
    font-family:'Playfair Display',serif;
    font-size:1.9rem;font-weight:900;line-height:1;margin-bottom:4px;
}
.stat-label{font-size:.78rem;color:var(--muted);font-weight:500;}

/* ══ TABLE ══ */
.adm-table-wrap{overflow-x:auto;border-radius:var(--radius);}
.adm-table{width:100%;border-collapse:collapse;font-size:.84rem;}
.adm-table thead tr{border-bottom:1px solid var(--border3);}
.adm-table th{
    padding:11px 16px;text-align:left;
    font-size:.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;
    color:var(--muted);white-space:nowrap;
}
.adm-table td{
    padding:13px 16px;
    border-bottom:1px solid rgba(255,255,255,.04);
    vertical-align:middle;
    color:rgba(255,255,255,.8);
}
.adm-table tbody tr{transition:background .18s;}
.adm-table tbody tr:hover{background:rgba(255,255,255,.025);}
.adm-table tbody tr:last-child td{border-bottom:none;}

.adm-badge{
    display:inline-flex;align-items:center;gap:4px;
    padding:3px 10px;border-radius:20px;
    font-size:.7rem;font-weight:700;letter-spacing:.3px;
    white-space:nowrap;
}
.adm-badge.gold{background:rgba(255,215,0,.1);color:var(--gold);border:1px solid rgba(255,215,0,.2);}
.adm-badge.green{background:rgba(34,197,94,.1);color:var(--green);border:1px solid rgba(34,197,94,.2);}
.adm-badge.red{background:rgba(255,77,77,.1);color:var(--red);border:1px solid rgba(255,77,77,.2);}
.adm-badge.blue{background:rgba(59,130,246,.1);color:var(--blue);border:1px solid rgba(59,130,246,.2);}

/* ══ BUTTONS ══ */
.btn-adm{
    display:inline-flex;align-items:center;gap:7px;
    padding:9px 18px;border-radius:10px;
    font-size:.82rem;font-weight:600;
    border:none;cursor:pointer;text-decoration:none;
    font-family:'DM Sans',sans-serif;
    transition:var(--ease);white-space:nowrap;
}
.btn-adm-primary{
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    color:#111;
}
.btn-adm-primary:hover{box-shadow:0 4px 18px rgba(255,215,0,.35);transform:translateY(-1px);}
.btn-adm-ghost{
    background:var(--s3);color:var(--text);
    border:1px solid var(--border3);
}
.btn-adm-ghost:hover{background:var(--s4);border-color:rgba(255,215,0,.2);}
.btn-adm-danger{
    background:rgba(255,77,77,.1);color:var(--red);
    border:1px solid rgba(255,77,77,.2);
}
.btn-adm-danger:hover{background:rgba(255,77,77,.2);}
.btn-adm-sm{padding:6px 12px;font-size:.76rem;border-radius:8px;}

.btn-icon{
    width:32px;height:32px;border-radius:8px;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:.8rem;border:none;cursor:pointer;text-decoration:none;
    transition:var(--ease);
}
.btn-icon-danger{background:rgba(255,77,77,.1);color:var(--red);}
.btn-icon-danger:hover{background:rgba(255,77,77,.22);}
.btn-icon-edit{background:rgba(255,215,0,.08);color:var(--gold);}
.btn-icon-edit:hover{background:rgba(255,215,0,.16);}

/* ══ FORMS ══ */
.adm-form{display:flex;flex-direction:column;gap:20px;}
.form-group{display:flex;flex-direction:column;gap:7px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.form-label{
    font-size:.78rem;font-weight:600;color:rgba(255,255,255,.7);
    letter-spacing:.3px;
}
.form-label span{color:var(--red);margin-left:2px;}
.form-input,.form-select,.form-textarea{
    background:var(--s3);
    border:1px solid var(--border3);
    border-radius:10px;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    font-size:.87rem;
    padding:11px 14px;
    transition:border-color .2s,box-shadow .2s;
    width:100%;
    outline:none;
    -webkit-appearance:none;
}
.form-input:focus,.form-select:focus,.form-textarea:focus{
    border-color:rgba(255,215,0,.5);
    box-shadow:0 0 0 3px rgba(255,215,0,.07);
}
.form-input::placeholder,.form-textarea::placeholder{color:var(--muted2);}
.form-select option{background:var(--s3);color:var(--text);}
.form-textarea{resize:vertical;min-height:100px;line-height:1.6;}

.form-input-icon{position:relative;}
.form-input-icon i{
    position:absolute;left:13px;top:50%;transform:translateY(-50%);
    color:var(--muted);font-size:.82rem;pointer-events:none;
}
.form-input-icon .form-input{padding-left:36px;}

/* File upload */
.file-upload-area{
    border:2px dashed var(--border3);border-radius:12px;
    padding:30px 20px;text-align:center;cursor:pointer;
    transition:var(--ease);background:var(--s2);
    position:relative;overflow:hidden;
}
.file-upload-area:hover{border-color:rgba(255,215,0,.35);background:var(--s3);}
.file-upload-area input[type=file]{
    position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;
}
.file-upload-icon{
    width:44px;height:44px;border-radius:12px;
    background:var(--gold-dim);color:var(--gold);
    display:flex;align-items:center;justify-content:center;
    font-size:1.1rem;margin:0 auto 10px;
}
.file-upload-text{font-size:.83rem;color:var(--muted);line-height:1.5;}
.file-upload-text strong{color:var(--gold);font-weight:600;}
#fileNameDisplay{
    margin-top:8px;font-size:.78rem;color:var(--green);font-weight:600;
    display:none;
}

/* ══ ALERTS / TOASTS ══ */
.adm-alert{
    display:flex;align-items:flex-start;gap:12px;
    padding:14px 18px;border-radius:12px;
    font-size:.85rem;margin-bottom:22px;
    animation:slideDown .3s ease;
}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.adm-alert-success{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);color:var(--green);}
.adm-alert-danger{background:rgba(255,77,77,.08);border:1px solid rgba(255,77,77,.2);color:var(--red);}
.adm-alert-warn{background:rgba(255,215,0,.07);border:1px solid rgba(255,215,0,.2);color:var(--gold);}
.adm-alert i{margin-top:1px;font-size:.9rem;flex-shrink:0;}
.adm-alert a{color:inherit;font-weight:700;text-decoration:underline;}

/* ══ EMPTY STATE ══ */
.adm-empty{
    text-align:center;padding:50px 20px;
    border:1px dashed var(--border3);border-radius:var(--radius-lg);
}
.adm-empty i{font-size:2.2rem;color:var(--muted2);display:block;margin-bottom:12px;}
.adm-empty p{color:var(--muted);font-size:.88rem;}

/* ══ FOOTER ══ */
.adm-footer{
    padding:14px 28px;border-top:1px solid var(--border);
    font-size:.73rem;color:var(--muted2);
    display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
}

/* ══ RESPONSIVE ══ */
@media(max-width:1100px){.adm-stats{grid-template-columns:repeat(2,1fr);}}
@media(max-width:900px){.form-row{grid-template-columns:1fr;}}
@media(max-width:768px){
    .adm-sidebar{transform:translateX(-100%);}
    .adm-sidebar.open{transform:translateX(0);box-shadow:0 0 0 100vw rgba(0,0,0,.6);}
    .adm-topbar{left:0;}
    .adm-main{margin-left:0;}
    .adm-menu-toggle{display:block;}
    .adm-body{padding:18px 16px 32px;}
    .adm-stats{grid-template-columns:repeat(2,1fr);}
    .adm-page-header{flex-direction:column;gap:12px;}
}
@media(max-width:480px){
    .adm-stats{grid-template-columns:1fr 1fr;}
    .adm-chip-role{display:none;}
}
</style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="adm-sidebar" id="admSidebar">
    <a href="dashboard.php" class="adm-brand">
        <img src="../assets/images/logo44.png" alt="MEDDEMY" onerror="this.style.display='none'">
        <span class="adm-brand-name">MEDDEMY</span>
        <span class="adm-brand-badge">Admin</span>
    </a>
    <nav class="adm-nav">
        <div class="nav-section-label">Main</div>
        <?php foreach ($nav as $key => $item): ?>
        <?php if ($key === 'manage_subjects'): ?>
            <div class="nav-section-label" style="margin-top:6px">Subjects & Units</div>
        <?php elseif ($key === 'manage_quizzes'): ?>
            <div class="nav-section-label" style="margin-top:6px">Quizzes</div>
        <?php elseif ($key === 'manage_posts'): ?>
            <div class="nav-section-label" style="margin-top:6px">Announcements</div>
        <?php elseif ($key === 'manage_materials'): ?>
            <div class="nav-section-label" style="margin-top:6px">Materials</div>
        <?php elseif ($key === 'manage_videos'): ?>
            <div class="nav-section-label" style="margin-top:6px">Video Library</div>
        <?php elseif ($key === 'manage_students' || $key === 'enroll_student'): ?>
            <?php if ($key === 'manage_students'): ?>
            <div class="nav-section-label" style="margin-top:6px">Students</div>
            <?php endif; ?>
        <?php elseif ($key === 'add_course'): ?>
            <div class="nav-section-label" style="margin-top:6px">Courses & Videos</div>
        <?php endif; ?>
        <a href="<?php echo $item['href']; ?>"
           class="adm-nav-item <?php echo ($active_nav ?? '') === $key ? 'active' : ''; ?>">
            <i class="fas <?php echo $item['icon']; ?>"></i>
            <?php echo $item['label']; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="adm-sidebar-footer">
        <a href="../logout.php" class="adm-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<!-- ══ TOPBAR ══ -->
<header class="adm-topbar">
    <div class="adm-topbar-left">
        <button class="adm-menu-toggle" id="admMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div>
            <div class="adm-page-title"><?php echo htmlspecialchars($page_title ?? 'Dashboard'); ?></div>
            <div class="adm-breadcrumb">
                <a href="dashboard.php">Admin</a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($page_title ?? 'Dashboard'); ?></span>
            </div>
        </div>
    </div>
    <div class="adm-topbar-right">
        <div class="adm-chip">
            <div class="adm-avatar"><?php echo $admin_init; ?></div>
            <div>
                <div class="adm-chip-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="adm-chip-role">Administrator</div>
            </div>
        </div>
    </div>
</header>

<!-- ══ MAIN ══ -->
<div class="adm-main">
<div class="adm-body">