<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$course_id  = (int)($_GET['id'] ?? 0);

// Enrollment check
$enroll_check = $conn->prepare("SELECT 1 FROM enrollment WHERE student_id=? AND course_id=?");
$enroll_check->bind_param("ii", $student_id, $course_id);
$enroll_check->execute();
if ($enroll_check->get_result()->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

// Course details
$stmt = $conn->prepare("SELECT * FROM course WHERE course_id=?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

// Videos — fetch with subject + unit info, NEVER send youtube_link to frontend
$vStmt = $conn->prepare("
    SELECT v.video_id, v.title, v.subject_id, v.unit_id,
           s.name as subject_name, s.sort_order as s_order,
           u.name as unit_name, u.sort_order as u_order
    FROM video v
    LEFT JOIN subject s ON s.subject_id = v.subject_id
    LEFT JOIN unit    u ON u.unit_id    = v.unit_id
    WHERE v.course_id=?
    ORDER BY ISNULL(v.subject_id) ASC, s.sort_order ASC, s.subject_id ASC,
             ISNULL(v.unit_id) ASC, u.sort_order ASC, u.unit_id ASC,
             v.video_id ASC");
$vStmt->bind_param("i", $course_id);
$vStmt->execute();
$vResult      = $vStmt->get_result();
$total_videos = $vResult->num_rows;

$videos = [];
// 3-level structure: subject => unit => [videos]
// subjects_group[subject_key][unit_key][] = video
$subjects_group = [];
while ($v = $vResult->fetch_assoc()) {
    $videos[] = [
        'id'         => (int)$v['video_id'],
        'title'      => $v['title'],
        'subject_id' => $v['subject_id'],
        'subject'    => $v['subject_name'] ?? null,
        'unit_id'    => $v['unit_id'],
        'unit'       => $v['unit_name'] ?? null,
    ];
    $skey = $v['subject_name'] ?? '__general__';
    $ukey = $v['unit_name']    ?? '__nounit__';
    $subjects_group[$skey][$ukey][] = [
        'id'    => (int)$v['video_id'],
        'title' => $v['title'],
    ];
}

// Subjects for this course (for header display)
$course_subjects = $conn->query("SELECT subject_id, name, description,
    (SELECT COUNT(*) FROM video v WHERE v.subject_id=s.subject_id) as vlcount
    FROM subject s WHERE s.course_id=$course_id ORDER BY s.sort_order ASC, s.subject_id ASC");
$subjects_list = $course_subjects->fetch_all(MYSQLI_ASSOC);

// Quiz map — video_id => quiz_id (for all videos in this course)
$quiz_map = [];
$qMapRes = $conn->query("SELECT q.quiz_id, q.video_id FROM quiz q JOIN video v ON v.video_id=q.video_id WHERE v.course_id=$course_id");
while ($qr = $qMapRes->fetch_assoc()) {
    $quiz_map[(int)$qr['video_id']] = (int)$qr['quiz_id'];
}
// Student best scores
$score_map = [];
foreach ($quiz_map as $vid => $qid) {
    $sc = $conn->prepare("SELECT score, total FROM quiz_attempt WHERE student_id=? AND quiz_id=? ORDER BY score DESC LIMIT 1");
    $sc->bind_param("ii", $student_id, $qid); $sc->execute();
    $sr = $sc->get_result()->fetch_assoc();
    if ($sr) $score_map[$qid] = $sr;
}

// Generate CSRF token for this session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Security headers -->
<meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
<meta name="referrer" content="no-referrer">
<title><?php echo htmlspecialchars($course['title']); ?> — MEDDEMY</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
    --gold:#FFD700;--gold-soft:#ffed4e;
    --bg:#0c0c0c;--surface:#141414;--surface2:#1c1c1c;
    --border:rgba(255,215,0,.13);--border2:#252525;
    --text:#fff;--muted:rgba(255,255,255,.45);
    --radius:14px;--ease:all .28s cubic-bezier(.4,0,.2,1);
    --sidebar-w:240px;
}

body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;-webkit-user-select:none;user-select:none;}
.sel{-webkit-user-select:text;user-select:text;}

/* ── SIDEBAR ── */
.sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;transition:transform .3s ease;}
.sidebar-brand{padding:22px 18px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;text-decoration:none;}
.sidebar-brand img{height:32px;}
.sidebar-brand-name{font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:900;color:var(--gold);}
.sidebar-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:3px;}
.nav-label{font-size:.65rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);padding:10px 12px 5px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;text-decoration:none;color:rgba(255,255,255,.55);font-size:.85rem;font-weight:500;transition:var(--ease);}
.nav-item i{width:16px;text-align:center;font-size:.85rem;}
.nav-item:hover{background:rgba(255,215,0,.07);color:var(--gold);}
.nav-item.active{background:rgba(255,215,0,.12);color:var(--gold);font-weight:600;}
.sidebar-footer{padding:12px 10px;border-top:1px solid var(--border);}
.logout-btn{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;text-decoration:none;color:rgba(255,100,100,.7);font-size:.85rem;font-weight:500;transition:var(--ease);width:100%;background:none;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;}
.logout-btn:hover{background:rgba(255,80,80,.1);color:#ff6b6b;}

/* ── TOPBAR ── */
.topbar{position:fixed;top:0;left:var(--sidebar-w);right:0;height:56px;background:rgba(12,12,12,.96);backdrop-filter:blur(14px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 22px;z-index:99;gap:12px;}
.topbar-left{display:flex;align-items:center;gap:12px;min-width:0;}
.menu-toggle{display:none;background:none;border:none;color:var(--text);font-size:1.1rem;cursor:pointer;padding:4px;}
.breadcrumb{display:flex;align-items:center;gap:7px;font-size:.82rem;color:var(--muted);min-width:0;}
.breadcrumb a{color:var(--muted);text-decoration:none;transition:color .2s;flex-shrink:0;}
.breadcrumb a:hover{color:var(--gold);}
.breadcrumb i{font-size:.58rem;flex-shrink:0;}
.breadcrumb span{color:var(--text);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;}
.topbar-right{display:flex;align-items:center;gap:10px;flex-shrink:0;}
.student-chip{display:flex;align-items:center;gap:8px;background:var(--surface2);border:1px solid var(--border);border-radius:50px;padding:5px 14px 5px 5px;}
.s-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-soft));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.75rem;color:#111;}
.s-name{font-size:.8rem;font-weight:600;}

/* ── LAYOUT ── */
.main-wrap{margin-left:var(--sidebar-w);padding-top:56px;min-height:100vh;display:flex;flex-direction:column;}
.course-strip{background:linear-gradient(135deg,#131000,#1c1700 60%,#111);border-bottom:1px solid rgba(255,215,0,.15);padding:22px 28px;position:relative;overflow:hidden;}
.course-strip::after{content:'';position:absolute;top:-80px;right:-80px;width:260px;height:260px;background:radial-gradient(circle,rgba(255,215,0,.07),transparent 65%);pointer-events:none;}
.strip-inner{max-width:900px;position:relative;z-index:1;}
.strip-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(255,215,0,.1);border:1px solid rgba(255,215,0,.22);color:var(--gold);font-size:.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:3px 12px;border-radius:50px;margin-bottom:8px;}
.strip-inner h1{font-family:'Playfair Display',serif;font-size:clamp(1.2rem,3vw,1.65rem);font-weight:900;margin-bottom:6px;line-height:1.25;}
.strip-inner p{font-size:.87rem;color:var(--muted);line-height:1.6;max-width:680px;margin-bottom:12px;}
.strip-meta{display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
.meta-pill{display:flex;align-items:center;gap:6px;font-size:.77rem;color:rgba(255,255,255,.5);}
.meta-pill i{color:var(--gold);font-size:.77rem;}

/* ── CONTENT ── */
.content-body{flex:1;padding:24px 28px;display:grid;grid-template-columns:1fr 320px;gap:22px;max-width:1280px;width:100%;}

/* ── PLAYER BOX ── */
.player-box{background:#000;border-radius:18px;overflow:hidden;border:1px solid var(--border2);box-shadow:0 20px 60px rgba(0,0,0,.55);}
.video-frame-wrap{position:relative;padding-bottom:56.25%;height:0;background:#000;}
.video-frame-wrap iframe,.video-frame-wrap video{position:absolute;top:0;left:0;width:100%;height:100%;border:none;display:block;}
.video-frame-wrap video{object-fit:contain;}

/* Loading overlay */
.player-loading{position:absolute;inset:0;background:#000;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;z-index:10;}
.loader-ring{width:44px;height:44px;border:3px solid rgba(255,215,0,.2);border-top-color:var(--gold);border-radius:50%;animation:spin .8s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
.loader-text{font-size:.82rem;color:var(--muted);}

/* Overlay — blocks YouTube share/info buttons, enables click-seek */
.yt-overlay{
    position:absolute;inset:0;z-index:5;
    cursor:pointer;
    /* Invisible — just sits on top of iframe */
    background:transparent;
}
/* Left / Right click zones — shown as subtle flash on click */
.yt-overlay .zone{
    position:absolute;top:0;bottom:0;width:50%;
    display:flex;align-items:center;justify-content:center;
    pointer-events:none;
}
.yt-overlay .zone-left{left:0;}
.yt-overlay .zone-right{right:0;}

/* Seek ripple animation */
.seek-ripple{
    display:flex;align-items:center;justify-content:center;gap:6px;
    background:rgba(0,0,0,.55);color:#fff;
    padding:10px 18px;border-radius:40px;
    font-size:.85rem;font-weight:700;font-family:'DM Sans',sans-serif;
    opacity:0;transform:scale(.85);
    transition:opacity .15s,transform .15s;
    pointer-events:none;
    white-space:nowrap;
}
.seek-ripple.show{opacity:1;transform:scale(1);}
.seek-ripple i{font-size:.9rem;}

/* ── CUSTOM CONTROLS ── */
.custom-controls{background:#0f0f0f;border-top:1px solid #1c1c1c;padding:10px 16px 12px;}
.seek-row{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.time-lbl{font-size:.72rem;color:var(--muted);font-weight:600;flex-shrink:0;min-width:36px;font-variant-numeric:tabular-nums;}
.seek-bg{flex:1;height:5px;background:#2a2a2a;border-radius:5px;position:relative;cursor:pointer;transition:height .15s;}
.seek-bg:hover{height:7px;}
.seek-fill{height:100%;background:linear-gradient(90deg,var(--gold),var(--gold-soft));border-radius:5px;width:0%;pointer-events:none;position:relative;}
.seek-thumb{width:14px;height:14px;background:var(--gold);border-radius:50%;position:absolute;right:-7px;top:50%;transform:translateY(-50%);opacity:0;transition:opacity .15s;pointer-events:none;}
.seek-bg:hover .seek-thumb{opacity:1;}

.ctrl-row{display:flex;align-items:center;justify-content:space-between;gap:8px;}
.ctrl-group{display:flex;align-items:center;gap:4px;}
.ctrl-btn{background:none;border:none;color:rgba(255,255,255,.65);cursor:pointer;padding:7px 9px;border-radius:8px;font-size:.9rem;transition:var(--ease);display:flex;align-items:center;gap:4px;font-family:'DM Sans',sans-serif;}
.ctrl-btn:hover:not(:disabled){background:rgba(255,255,255,.07);color:var(--text);}
.ctrl-btn:disabled{opacity:.3;cursor:not-allowed;}

.play-pause-btn{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-soft));color:#111;font-size:1rem;justify-content:center;padding:0;}
.play-pause-btn:hover:not(:disabled){box-shadow:0 0 0 4px rgba(255,215,0,.25);color:#111;}

.skip-label{font-size:.62rem;font-weight:700;line-height:1;}
.vol-wrap{display:flex;align-items:center;gap:4px;}
.vol-slider{-webkit-appearance:none;appearance:none;width:70px;height:4px;border-radius:4px;background:#2a2a2a;outline:none;cursor:pointer;}
.vol-slider::-webkit-slider-thumb{-webkit-appearance:none;width:12px;height:12px;border-radius:50%;background:var(--gold);cursor:pointer;}
.vol-slider::-moz-range-thumb{width:12px;height:12px;border-radius:50%;background:var(--gold);cursor:pointer;border:none;}

.speed-wrap{position:relative;}
.speed-btn{font-size:.78rem;padding:6px 10px;border:1px solid #2a2a2a;border-radius:8px;}
.speed-menu{position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:#1a1a1a;border:1px solid #333;border-radius:10px;padding:6px;display:none;z-index:20;min-width:70px;}
.speed-menu.open{display:block;}
.speed-opt{padding:6px 12px;font-size:.8rem;color:var(--muted);cursor:pointer;border-radius:7px;text-align:center;transition:var(--ease);}
.speed-opt:hover,.speed-opt.active{background:rgba(255,215,0,.12);color:var(--gold);font-weight:600;}

.nav-vid-btn{border:1px solid #2a2a2a;font-size:.8rem;padding:7px 12px;border-radius:8px;gap:5px;}
.nav-vid-btn.primary{background:linear-gradient(135deg,var(--gold),var(--gold-soft));color:#111;border-color:transparent;font-weight:700;}
.nav-vid-btn.primary:hover:not(:disabled){box-shadow:0 3px 14px rgba(255,215,0,.4);color:#111;}
.fs-btn{border:1px solid #2a2a2a;border-radius:8px;}

.player-title-bar{padding:13px 16px;background:var(--surface);border:1px solid var(--border2);border-top:none;border-radius:0 0 18px 18px;margin-top:-1px;}
.player-title-bar h3{font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:2px;}
.player-title-bar .vid-counter{font-size:.75rem;color:var(--muted);}

/* ══════════════════════════════════════════════
   PLAYLIST — Professional Redesign
══════════════════════════════════════════════ */

/* Playlist column — sticky full height on desktop */
.playlist-col{
    display:flex;flex-direction:column;
    height:calc(100vh - 90px);
    position:sticky;top:80px;
    overflow:hidden;
    background:var(--surface);
    border:1px solid var(--border2);
    border-radius:18px;
}

/* Playlist header strip */
.playlist-header{
    flex-shrink:0;
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 16px;
    border-bottom:1px solid var(--border2);
    background:linear-gradient(135deg,rgba(255,215,0,.06),rgba(255,215,0,.02));
}
.playlist-header h2{
    font-family:'Playfair Display',serif;
    font-size:.95rem;font-weight:700;
    display:flex;align-items:center;gap:8px;
}
.playlist-header h2 i{color:var(--gold);font-size:.82rem;}
.playlist-lect-count{
    font-size:.72rem;font-weight:600;color:var(--muted);
    background:rgba(255,255,255,.06);
    padding:3px 10px;border-radius:20px;
    border:1px solid rgba(255,255,255,.07);
}

/* Scroll container — only this scrolls */
.video-list{
    flex:1;min-height:0;
    overflow-y:auto;
    padding:10px 8px 16px;
    display:flex;flex-direction:column;gap:0;
    scroll-behavior:smooth;
}
/* Gold scrollbar */
.video-list::-webkit-scrollbar{width:4px;}
.video-list::-webkit-scrollbar-track{background:transparent;}
.video-list::-webkit-scrollbar-thumb{background:rgba(255,215,0,.2);border-radius:10px;}
.video-list::-webkit-scrollbar-thumb:hover{background:rgba(255,215,0,.4);}
.video-list{scrollbar-width:thin;scrollbar-color:rgba(255,215,0,.2) transparent;}

/* ══ SUBJECT ITEM ══ */
.subj-item{margin-bottom:6px;}

/* Subject header button */
.subj-btn{
    width:100%;
    display:flex;align-items:center;gap:10px;
    padding:10px 12px;
    background:linear-gradient(135deg,rgba(255,215,0,.08),rgba(255,215,0,.04));
    border:1px solid rgba(255,215,0,.18);
    border-radius:11px;
    cursor:pointer;
    transition:.22s;
    user-select:none;
    text-align:left;
}
.subj-btn:hover{
    background:linear-gradient(135deg,rgba(255,215,0,.14),rgba(255,215,0,.07));
    border-color:rgba(255,215,0,.35);
}
.subj-btn.active{
    background:linear-gradient(135deg,rgba(255,215,0,.16),rgba(255,215,0,.08));
    border-color:rgba(255,215,0,.4);
    box-shadow:0 2px 12px rgba(255,215,0,.1);
}
.subj-icon-wrap{
    width:28px;height:28px;border-radius:8px;
    background:rgba(255,215,0,.12);
    display:flex;align-items:center;justify-content:center;
    color:var(--gold);font-size:.72rem;flex-shrink:0;
}
.subj-btn.active .subj-icon-wrap{background:rgba(255,215,0,.2);}
.subj-btn-name{flex:1;font-size:.82rem;font-weight:700;color:var(--gold);letter-spacing:.2px;}
.subj-btn-count{
    font-size:.62rem;font-weight:700;color:rgba(255,215,0,.6);
    background:rgba(255,215,0,.1);
    padding:2px 8px;border-radius:20px;flex-shrink:0;
}
.subj-chevron{
    font-size:.65rem;color:rgba(255,215,0,.5);
    transition:transform .25s ease;flex-shrink:0;margin-left:2px;
}
.subj-btn.active .subj-chevron{transform:rotate(0deg);}

/* Subject body — hidden by default */
.subj-body{
    display:none;
    flex-direction:column;gap:4px;
    padding:6px 0 4px 8px;
    border-left:2px solid rgba(255,215,0,.15);
    margin:4px 0 4px 14px;
}
.subj-body.active{display:flex;}

/* ══ UNIT ITEM ══ */
.unit-item{margin-bottom:4px;}

/* Unit header */
.unit-btn{
    width:100%;
    display:flex;align-items:center;gap:8px;
    padding:8px 10px;
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.07);
    border-radius:9px;
    cursor:pointer;
    transition:.2s;
    user-select:none;
    text-align:left;
}
.unit-btn:hover{
    background:rgba(255,215,0,.05);
    border-color:rgba(255,215,0,.15);
}
.unit-btn.active{
    background:rgba(255,215,0,.06);
    border-color:rgba(255,215,0,.2);
}
.unit-dot{
    width:22px;height:22px;border-radius:6px;
    background:rgba(255,255,255,.06);
    display:flex;align-items:center;justify-content:center;
    color:rgba(255,215,0,.5);font-size:.6rem;flex-shrink:0;
}
.unit-btn.active .unit-dot{background:rgba(255,215,0,.1);color:var(--gold);}
.unit-btn-name{flex:1;font-size:.76rem;font-weight:600;color:rgba(255,255,255,.6);}
.unit-btn.active .unit-btn-name{color:rgba(255,255,255,.85);}
.unit-btn-count{
    font-size:.6rem;font-weight:700;color:var(--muted);
    background:rgba(255,255,255,.05);
    padding:1px 7px;border-radius:20px;flex-shrink:0;
}
.unit-chevron{font-size:.6rem;color:var(--muted);transition:transform .22s;flex-shrink:0;}
.unit-btn.active .unit-chevron{transform:rotate(0deg);color:rgba(255,215,0,.5);}

/* Unit body */
.unit-body{
    display:none;
    flex-direction:column;gap:4px;
    padding:4px 0 2px 8px;
    border-left:2px solid rgba(255,255,255,.06);
    margin:2px 0 2px 10px;
}
.unit-body.active{display:flex;}

/* ══ VIDEO CARD ══ */
.video-item{
    display:flex;align-items:center;gap:0;
    background:transparent;
    border:1px solid transparent;
    border-radius:10px;
    overflow:hidden;cursor:pointer;
    transition:.2s;
    margin-bottom:3px;
}
.video-item:hover{
    background:rgba(255,255,255,.04);
    border-color:rgba(255,255,255,.08);
}
.video-item.playing{
    background:rgba(255,215,0,.06);
    border-color:rgba(255,215,0,.25);
    box-shadow:0 2px 12px rgba(255,215,0,.08);
}
.video-item.loading-item{opacity:.5;pointer-events:none;}

/* Play button circle */
.vi-play-btn{
    width:34px;height:34px;border-radius:50%;
    background:rgba(255,255,255,.07);
    border:1px solid rgba(255,255,255,.1);
    display:flex;align-items:center;justify-content:center;
    font-size:.72rem;color:var(--muted);
    flex-shrink:0;margin:0 10px;
    transition:.2s;
}
.video-item:hover .vi-play-btn{background:rgba(255,215,0,.1);border-color:rgba(255,215,0,.3);color:var(--gold);}
.video-item.playing .vi-play-btn{background:var(--gold);border-color:var(--gold);color:#111;}
.video-item.playing .vi-play-btn i{animation:pulseGold .9s ease infinite alternate;}
@keyframes pulseGold{from{filter:none}to{filter:drop-shadow(0 0 6px rgba(255,215,0,.8))}}

.vi-info{flex:1;padding:10px 12px 10px 0;min-width:0;}
.vi-title{font-size:.82rem;font-weight:600;color:rgba(255,255,255,.8);line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.video-item.playing .vi-title{color:var(--gold);}
.vi-num{font-size:.65rem;color:var(--muted);margin-top:3px;}
.now-tag{
    display:inline-flex;align-items:center;gap:4px;
    background:rgba(255,215,0,.1);border:1px solid rgba(255,215,0,.22);
    color:var(--gold);font-size:.62rem;font-weight:700;
    padding:2px 8px;border-radius:20px;margin-top:4px;
    width:fit-content;
}
.now-tag i{font-size:.55rem;animation:blink 1s steps(1) infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0;}}

/* ══ QUIZ CARD ══ */
.quiz-item{
    display:flex;align-items:center;gap:10px;
    padding:8px 10px 8px 12px;
    margin:2px 0 6px 0;
    background:linear-gradient(135deg,rgba(255,215,0,.05),rgba(255,215,0,.02));
    border:1px solid rgba(255,215,0,.15);
    border-radius:9px;
    text-decoration:none;color:var(--text);
    cursor:pointer;transition:.2s;
    position:relative;overflow:hidden;
}
.quiz-item::before{
    content:'';position:absolute;left:0;top:0;bottom:0;
    width:3px;background:linear-gradient(180deg,var(--gold),rgba(255,215,0,.3));
    border-radius:3px 0 0 3px;
}
.quiz-item:hover{
    border-color:rgba(255,215,0,.35);
    background:linear-gradient(135deg,rgba(255,215,0,.09),rgba(255,215,0,.04));
}
.qi-icon{
    width:28px;height:28px;border-radius:8px;
    background:rgba(255,215,0,.1);
    display:flex;align-items:center;justify-content:center;
    color:var(--gold);font-size:.8rem;flex-shrink:0;
}
.qi-info{flex:1;min-width:0;}
.qi-label{font-size:.6rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:rgba(255,215,0,.6);margin-bottom:1px;}
.qi-title{font-size:.76rem;font-weight:600;color:rgba(255,255,255,.6);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.qi-right{flex-shrink:0;}
.qi-score{font-size:.7rem;font-weight:800;padding:2px 9px;border-radius:20px;border:1px solid;white-space:nowrap;}
.qi-start{font-size:.7rem;font-weight:700;color:rgba(255,215,0,.7);display:flex;align-items:center;gap:4px;}
.quiz-item:hover .qi-start{color:var(--gold);}

/* ══ MOBILE TOGGLE ══ */
.playlist-toggle{
    display:none;
    align-items:center;justify-content:space-between;
    padding:11px 14px;
    background:var(--surface2);
    border:1px solid var(--border2);
    border-radius:11px;
    cursor:pointer;
    font-size:.83rem;font-weight:700;
    margin-bottom:10px;
    color:var(--text);
    user-select:none;
}
.playlist-toggle:hover{border-color:rgba(255,215,0,.3);}
.playlist-toggle i{color:var(--gold);}

/* ══ SUBJECT CHIPS (course header) ══ */
.subjects-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;}
.subj-chip{
    display:inline-flex;align-items:center;gap:7px;
    padding:7px 14px;border-radius:30px;cursor:pointer;
    background:rgba(255,215,0,.07);
    border:1px solid rgba(255,215,0,.2);
    font-size:.78rem;font-weight:700;color:rgba(255,255,255,.75);
    transition:.22s;user-select:none;
}
.subj-chip:hover{background:rgba(255,215,0,.15);border-color:rgba(255,215,0,.45);color:#fff;transform:translateY(-2px);}
.subj-chip i{color:var(--gold);font-size:.7rem;}
.subj-chip-count{background:rgba(255,215,0,.15);color:var(--gold);font-size:.65rem;font-weight:800;padding:1px 7px;border-radius:20px;}

/* ══ RESPONSIVE ══ */
@media(min-width:1081px){
    .playlist-col{position:sticky;top:80px;}
}
@media(max-width:1080px){
    .content-body{grid-template-columns:1fr;}
    .playlist-col{
        order:2;position:static;
        height:auto;max-height:none;
        overflow:visible;
        border-radius:14px;
    }
    .player-col{order:1;}
    .video-list{max-height:none;overflow:visible;}
}
@media(max-width:768px){
    .sidebar{transform:translateX(-100%);}
    .sidebar.open{transform:translateX(0);box-shadow:0 0 0 100vw rgba(0,0,0,.55);}
    .topbar{left:0;}.main-wrap{margin-left:0;}.menu-toggle{display:block;}
    .course-strip{padding:14px 16px;}
    .content-body{padding:12px 14px;gap:14px;}
    .page-footer{padding:14px;}
    .breadcrumb span{max-width:110px;}
    .vol-slider{width:50px;}
    .subjects-row{gap:6px;}
    .subj-chip{padding:5px 11px;font-size:.73rem;}
    .playlist-toggle{display:flex;}
    .video-list{display:none;max-height:none;}
    .video-list.mobile-open{display:flex;}
    .playlist-col{border-radius:12px;}
}
@media(max-width:560px){
    .skip-label{display:none;}
    .vol-slider{display:none;}
    .subj-chip-count{display:none;}
    .content-body{padding:10px;}
    .playlist-header{padding:12px 14px;}
}
@media(max-width:400px){
    .subjects-row{gap:5px;}
    .subj-chip{font-size:.68rem;padding:5px 9px;}
}

/* ── FULLSCREEN — proper mobile landscape ── */
.player-box:-webkit-full-screen{width:100vw!important;height:100vh!important;border-radius:0!important;border:none!important;background:#000!important;display:flex;flex-direction:column;}
.player-box:-webkit-full-screen .video-frame-wrap{flex:1!important;padding-bottom:0!important;height:auto!important;}
.player-box:-webkit-full-screen iframe,.player-box:-webkit-full-screen video{width:100%!important;height:100%!important;object-fit:contain;}
.player-box:-webkit-full-screen .custom-controls{display:none;}
.player-box:fullscreen{width:100vw!important;height:100vh!important;border-radius:0!important;border:none!important;background:#000!important;display:flex;flex-direction:column;}
.player-box:fullscreen .video-frame-wrap{flex:1!important;padding-bottom:0!important;height:auto!important;}
.player-box:fullscreen iframe,.player-box:fullscreen video{width:100%!important;height:100%!important;object-fit:contain;}
.player-box:fullscreen .custom-controls{display:none;}
.player-box:fullscreen .yt-overlay,.player-box:-webkit-full-screen .yt-overlay{z-index:10;}
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <a href="../index.php" class="sidebar-brand">
        <img src="../assets/images/logo44.png" alt="MEDDEMY">
        <span class="sidebar-brand-name">MEDDEMY</span>
    </a>
    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-book-open"></i> My Courses</a>
        <div class="nav-label" style="margin-top:10px">Account</div>
        <a href="#" class="nav-item"><i class="fas fa-user-circle"></i> My Profile</a>
        <a href="#" class="nav-item"><i class="fas fa-headset"></i> Support</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>

<header class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <i class="fas fa-chevron-right"></i>
            <span class="sel"><?php echo htmlspecialchars($course['title']); ?></span>
        </div>
    </div>
    <div class="topbar-right">
        <div class="student-chip">
            <div class="s-avatar"><?php echo strtoupper(substr($_SESSION['student_name'],0,1)); ?></div>
            <span class="s-name"><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
        </div>
    </div>
</header>

<div class="main-wrap">

    <div class="course-strip">
        <div class="strip-inner">
            <div class="strip-tag"><i class="fas fa-play-circle"></i> Video Course</div>
            <h1 class="sel"><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="sel"><?php echo htmlspecialchars($course['description']); ?></p>
            <div class="strip-meta">
                <div class="meta-pill"><i class="fas fa-video"></i><?php echo $total_videos; ?> Lecture<?php echo $total_videos!=1?'s':''; ?></div>
                <?php if (!empty($subjects_list)): ?>
                <div class="meta-pill"><i class="fas fa-layer-group"></i><?php echo count($subjects_list); ?> Subject<?php echo count($subjects_list)!=1?'s':''; ?></div>
                <?php endif; ?>
                <div class="meta-pill"><i class="fas fa-lock"></i>Enrolled Access Only</div>
                <div class="meta-pill"><i class="fas fa-infinity"></i>Lifetime Access</div>
            </div>

            <?php if (!empty($subjects_list)): ?>
            <!-- Subjects chips -->
            <div class="subjects-row">
                <?php foreach($subjects_list as $subj): ?>
                <div class="subj-chip" onclick="scrollToSubject('<?php echo htmlspecialchars(addslashes($subj['name'])); ?>')">
                    <i class="fas fa-book"></i>
                    <span><?php echo htmlspecialchars($subj['name']); ?></span>
                    <span class="subj-chip-count"><?php echo $subj['vlcount']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="content-body">

        <!-- PLAYER COLUMN -->
        <div class="player-col">
            <?php if (count($videos) > 0): ?>

            <div class="player-box">
                <div class="video-frame-wrap" id="videoFrameWrap">
                    <!-- Loading state -->
                    <div class="player-loading" id="playerLoading">
                        <div class="loader-ring"></div>
                        <div class="loader-text">Loading video...</div>
                    </div>

                    <!-- Overlay: blocks YouTube share/info/logo buttons.
                         Left half click = -10s | Right half click = +10s -->
                    <div class="yt-overlay" id="ytOverlay" oncontextmenu="return false;">
                        <div class="zone zone-left">
                            <div class="seek-ripple" id="rippleLeft">
                                <i class="fas fa-undo"></i> -10s
                            </div>
                        </div>
                        <div class="zone zone-right">
                            <div class="seek-ripple" id="rippleRight">
                                <i class="fas fa-redo"></i> +10s
                            </div>
                        </div>
                    </div>
                </div>

                <div class="custom-controls" id="customControls">
                    <div class="seek-row">
                        <span class="time-lbl" id="timeCurrent">0:00</span>
                        <div class="seek-bg" id="seekBg">
                            <div class="seek-fill" id="seekFill">
                                <div class="seek-thumb"></div>
                            </div>
                        </div>
                        <span class="time-lbl" id="timeDuration">0:00</span>
                    </div>
                    <div class="ctrl-row">
                        <div class="ctrl-group">
                            <button class="ctrl-btn" onclick="seekOffset(-10)" title="Rewind 10s">
                                <i class="fas fa-undo"></i><span class="skip-label">10</span>
                            </button>
                            <button class="ctrl-btn play-pause-btn" id="btnPlayPause" onclick="togglePlayPause()">
                                <i class="fas fa-play" id="ppIcon"></i>
                            </button>
                            <button class="ctrl-btn" onclick="seekOffset(10)" title="Forward 10s">
                                <i class="fas fa-redo"></i><span class="skip-label">10</span>
                            </button>
                            <div class="vol-wrap">
                                <button class="ctrl-btn" onclick="toggleMute()">
                                    <i class="fas fa-volume-up" id="volIcon"></i>
                                </button>
                                <input type="range" id="volSlider" class="vol-slider" min="0" max="100" value="100" oninput="setVolume(this.value)">
                            </div>
                            <div class="speed-wrap">
                                <button class="ctrl-btn speed-btn" onclick="toggleSpeedMenu()">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span id="speedLabel">1x</span>
                                </button>
                                <div class="speed-menu" id="speedMenu">
                                    <?php foreach ([0.5,0.75,1,1.25,1.5,1.75,2] as $s): ?>
                                    <div class="speed-opt <?php echo $s==1?'active':''; ?>" onclick="setSpeed(<?php echo $s; ?>)"><?php echo $s; ?>x</div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="ctrl-group">
                            <button class="ctrl-btn nav-vid-btn" id="btnPrev" onclick="navigateVideo(-1)" disabled>
                                <i class="fas fa-step-backward"></i> Prev
                            </button>
                            <button class="ctrl-btn nav-vid-btn primary" id="btnNext" onclick="navigateVideo(1)" <?php echo $total_videos<=1?'disabled':''; ?>>
                                Next <i class="fas fa-step-forward"></i>
                            </button>
                            <button class="ctrl-btn fs-btn" onclick="toggleFullscreen()">
                                <i class="fas fa-expand" id="fsIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="player-title-bar">
                <h3 class="sel" id="activeVideoTitle">Loading...</h3>
                <div class="vid-counter" id="vidCounter">Video — of <?php echo $total_videos; ?></div>
            </div>

            <!-- QUIZ BANNER — shown after video ends or on demand -->
            <div id="quizBanner" style="display:none;margin-top:14px;border-radius:16px;overflow:hidden;">
                <!-- Has quiz -->
                <div id="quizBannerInner" style="display:none;background:linear-gradient(135deg,#0d0b00,#1c1600);border:1px solid rgba(255,215,0,.25);border-radius:16px;padding:20px 22px;">
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                        <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,215,0,.1);border:1px solid rgba(255,215,0,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-question-circle" style="color:var(--gold);font-size:1.1rem;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;" id="quizBannerTitle">Lecture Quiz</div>
                            <div style="font-size:.78rem;color:rgba(255,255,255,.5);margin-top:3px;" id="quizBannerSub">Test your knowledge from this lecture</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex-shrink:0;">
                            <div id="quizScorePill" style="display:none;padding:5px 14px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:20px;font-size:.78rem;font-weight:700;color:#22c55e;"></div>
                            <a id="quizBannerBtn" href="#"
                               style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:linear-gradient(135deg,var(--gold),var(--gold2));color:#111;border-radius:11px;font-size:.85rem;font-weight:800;text-decoration:none;transition:.2s;"
                               onmouseover="this.style.boxShadow='0 6px 20px rgba(255,215,0,.35)'"
                               onmouseout="this.style.boxShadow='none'">
                                <i class="fas fa-play"></i> <span id="quizBannerBtnText">Start Quiz</span>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- No quiz for this video -->
                <div id="quizNoneInner" style="display:none;background:var(--surface);border:1px solid var(--border2);border-radius:16px;padding:14px 20px;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-info-circle" style="color:var(--muted);font-size:.9rem;"></i>
                    <span style="font-size:.82rem;color:var(--muted);">No quiz for this lecture yet.</span>
                </div>
            </div>

            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-video-slash"></i>
                <p>No videos added to this course yet.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- PLAYLIST COLUMN -->
        <div class="playlist-col">
            <div class="playlist-header">
                <h2><i class="fas fa-layer-group"></i>Course Playlist</h2>
                <span class="playlist-lect-count"><?php echo $total_videos; ?> lectures</span>
            </div>

            <!-- Mobile toggle -->
            <div class="playlist-toggle" id="playlistToggle" onclick="togglePlaylist()">
                <span><i class="fas fa-list" style="margin-right:6px;color:var(--gold);"></i>Show Lectures</span>
                <i class="fas fa-chevron-down" id="toggleChevron"></i>
            </div>

            <div class="video-list" id="videoList">
                <?php
                $global_i = 0;
                $subj_idx = 0;
                foreach ($subjects_group as $subj_name => $units_in_subj):
                    $is_general = ($subj_name === '__general__');
                    $subj_label = $is_general ? 'General' : $subj_name;
                    $subj_count = 0;
                    foreach ($units_in_subj as $uvids) $subj_count += count($uvids);
                    $subj_slug  = 'subj-' . $subj_idx;
                    $is_first   = ($subj_idx === 0);
                    $subj_idx++;
                ?>

                <!-- ══ SUBJECT ══ -->
                <div class="subj-item">
                    <button class="subj-btn <?php echo $is_first ? 'active' : ''; ?>"
                            id="sbtn-<?php echo $subj_slug; ?>"
                            onclick="toggleSubject('<?php echo $subj_slug; ?>')">
                        <div class="subj-icon-wrap">
                            <i class="fas <?php echo $is_general ? 'fa-folder-open' : 'fa-book'; ?>"></i>
                        </div>
                        <span class="subj-btn-name"><?php echo htmlspecialchars($subj_label); ?></span>
                        <span class="subj-btn-count"><?php echo $subj_count; ?></span>
                        <i class="fas fa-chevron-down subj-chevron" id="sc-<?php echo $subj_slug; ?>"
                           style="<?php echo $is_first ? 'transform:rotate(180deg);color:rgba(255,215,0,.7);' : ''; ?>"></i>
                    </button>

                    <div class="subj-body <?php echo $is_first ? 'active' : ''; ?>"
                         id="sb-<?php echo $subj_slug; ?>">

                    <?php $unit_idx=0;
                    foreach ($units_in_subj as $unit_name => $unit_videos):
                        $is_no_unit = ($unit_name === '__nounit__');
                        $unit_label = $is_no_unit ? null : $unit_name;
                        $unit_count = count($unit_videos);
                        $unit_slug  = $subj_slug . '-u' . $unit_idx;
                        $is_first_unit = ($unit_idx === 0);
                        $unit_idx++;
                    ?>

                    <?php if ($unit_label): ?>
                    <!-- ── UNIT ── -->
                    <div class="unit-item">
                        <button class="unit-btn <?php echo $is_first_unit ? 'active' : ''; ?>"
                                id="ubtn-<?php echo $unit_slug; ?>"
                                onclick="toggleUnit('<?php echo $unit_slug; ?>')">
                            <div class="unit-dot">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <span class="unit-btn-name"><?php echo htmlspecialchars($unit_label); ?></span>
                            <span class="unit-btn-count"><?php echo $unit_count; ?></span>
                            <i class="fas fa-chevron-down unit-chevron" id="uc-<?php echo $unit_slug; ?>"
                               style="<?php echo $is_first_unit ? 'transform:rotate(180deg);' : ''; ?>"></i>
                        </button>
                        <div class="unit-body <?php echo $is_first_unit ? 'active' : ''; ?>"
                             id="ub-<?php echo $unit_slug; ?>">
                    <?php endif; ?>

                    <?php foreach ($unit_videos as $v):
                        $i       = $global_i++;
                        $vid     = $v['id'];
                        $vQuizId = $quiz_map[$vid] ?? null;
                        $vScore  = $vQuizId ? ($score_map[$vQuizId] ?? null) : null;
                    ?>

                    <!-- VIDEO -->
                    <div class="video-item <?php echo $i===0 ? 'playing' : ''; ?>"
                         id="vi-<?php echo $i; ?>"
                         onclick="playVideo(<?php echo $i; ?>)">
                        <div class="vi-play-btn" id="vi-icon-wrap-<?php echo $i; ?>">
                            <i class="fas <?php echo $i===0 ? 'fa-pause' : 'fa-play'; ?>" id="vi-icon-<?php echo $i; ?>"></i>
                        </div>
                        <div class="vi-info">
                            <div class="vi-title sel"><?php echo htmlspecialchars($v['title']); ?></div>
                            <div class="vi-num">Lecture <?php echo $i+1; ?> / <?php echo $total_videos; ?></div>
                            <div class="now-tag" id="vi-tag-<?php echo $i; ?>" <?php echo $i!==0 ? 'style="display:none;"' : ''; ?>>
                                <i class="fas fa-circle"></i> Now Playing
                            </div>
                        </div>
                    </div>

                    <!-- QUIZ -->
                    <?php if ($vQuizId): ?>
                    <a href="quiz.php?quiz_id=<?php echo $vQuizId; ?>" class="quiz-item">
                        <div class="qi-icon"><i class="fas fa-question-circle"></i></div>
                        <div class="qi-info">
                            <div class="qi-label">Quiz</div>
                            <div class="qi-title"><?php echo htmlspecialchars($v['title']); ?></div>
                        </div>
                        <div class="qi-right">
                            <?php if ($vScore):
                                $pct = round($vScore['score']/$vScore['total']*100);
                                $sc  = $pct>=80 ? '#22c55e' : ($pct>=50 ? '#FFD700' : '#ff4d4d'); ?>
                            <div class="qi-score" style="color:<?php echo $sc; ?>;border-color:<?php echo $sc; ?>40;background:<?php echo $sc; ?>12;">
                                <i class="fas fa-trophy" style="font-size:.55rem;"></i>
                                <?php echo $vScore['score'].'/'.$vScore['total']; ?>
                            </div>
                            <?php else: ?>
                            <div class="qi-start">Start <i class="fas fa-arrow-right" style="font-size:.58rem;"></i></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php endforeach; // videos ?>

                    <?php if ($unit_label): ?>
                        </div><!-- /unit-body -->
                    </div><!-- /unit-item -->
                    <?php endif; ?>

                    <?php endforeach; // units ?>
                    </div><!-- /subj-body -->
                </div><!-- /subj-item -->

                <?php endforeach; // subjects ?>
            </div><!-- /video-list -->
        </div><!-- /playlist-col -->

<!-- YouTube IFrame API -->
<script src="https://www.youtube.com/iframe_api"></script>
<script>
// ── SAFE data: only video IDs (DB primary keys) and titles — NO YouTube IDs, NO URLs ──
const VIDEO_META = <?php echo json_encode(array_map(fn($v) => [
    'db_id' => $v['id'],
    'title' => $v['title'],
], $videos)); ?>;

const COURSE_ID = <?php echo (int)$course_id; ?>;
const CSRF      = <?php echo json_encode($csrf); ?>;
const ORIGIN    = <?php echo json_encode($origin); ?>;
const TOTAL     = VIDEO_META.length;

// Quiz map: db_video_id => quiz_id (0 means no quiz)
const QUIZ_MAP  = <?php echo json_encode($quiz_map); ?>;
// Score map: quiz_id => {score, total}
const SCORE_MAP = <?php echo json_encode($score_map); ?>;

let currentIndex  = 0;
let ytPlayer      = null;
let nativeVid     = null;
let isYT          = false;
let isPlaying     = false;
let isMuted       = false;
let currentSpeed  = 1;
let progressTimer = null;

// ── Load first video on page ready ──
window.addEventListener('load', () => {
    if (TOTAL > 0) loadVideo(0);

    // Initialize playlist heights on load
    setTimeout(redistributeSubjectHeights, 200);
});

// ── Secure AJAX loader — fetches embed URL from server ──
function loadVideo(index) {
    const meta = VIDEO_META[index];

    // Mark item loading
    document.querySelectorAll('.video-item').forEach(el => el.classList.remove('loading-item'));
    document.getElementById(`vi-${index}`).classList.add('loading-item');

    // Show loading spinner in player (keep overlay)
    const wrap = document.getElementById('videoFrameWrap');
    const _ov  = document.getElementById('ytOverlay');
    const _old = document.getElementById('nativeVideo') || document.getElementById('ytFrame') || document.querySelector('.player-loading');
    if (_old) _old.remove();
    const _loader = document.createElement('div');
    _loader.className = 'player-loading';
    _loader.innerHTML = '<div class="loader-ring"></div><div class="loader-text">Loading video...</div>';
    wrap.insertBefore(_loader, _ov);

    clearInterval(progressTimer);
    setIcon('fa-play'); isPlaying = false;
    resetProgress();

    // AJAX POST — only sends DB video_id, never YouTube ID
    fetch('video_token.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            video_id:  meta.db_id,
            course_id: COURSE_ID,
            csrf:      CSRF
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showPlayerError(data.error); return; }
        renderPlayer(data, index);
    })
    .catch(() => showPlayerError('Failed to load video. Please try again.'));
}

function renderPlayer(data, index) {
    const wrap    = document.getElementById('videoFrameWrap');
    const v       = VIDEO_META[index];
    const overlay = document.getElementById('ytOverlay');

    // Remove only the media element (keep overlay intact)
    const old = document.getElementById('nativeVideo') || document.getElementById('ytFrame') || document.querySelector('.player-loading');
    if (old) old.remove();

    if (data.is_mp4) {
        isYT = false;
        const vid = document.createElement('video');
        vid.id = 'nativeVideo';
        vid.setAttribute('controlsList', 'nodownload noremoteplayback');
        vid.setAttribute('disablePictureInPicture', '');
        vid.setAttribute('autoplay', '');
        vid.oncontextmenu = () => false;
        vid.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;';
        vid.innerHTML = `<source src="${data.embed}" type="video/mp4">`;
        wrap.insertBefore(vid, overlay);
        nativeVid = vid;
        setupNative();
    } else {
        isYT = true; nativeVid = null;
        const autoplay = (index !== currentIndex) ? '&autoplay=1' : '';
        const fr = document.createElement('iframe');
        fr.id = 'ytFrame';
        fr.src = `${data.embed}&origin=${encodeURIComponent(ORIGIN)}${autoplay}`;
        fr.title = v.title;
        fr.frameBorder = '0';
        fr.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        fr.allowFullscreen = true;
        fr.referrerPolicy = 'strict-origin';
        fr.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;';
        wrap.insertBefore(fr, overlay);
        // Re-init YT API
        ytPlayer = new YT.Player('ytFrame', {
            events: {
                onReady: onPlayerReady,
                onStateChange: onPlayerStateChange
            }
        });
    }

    // Update title + counter
    document.getElementById('activeVideoTitle').textContent = v.title;
    document.getElementById('vidCounter').textContent = `Video ${index+1} of ${TOTAL}`;

    // Hide quiz banner when switching videos
    document.getElementById('quizBanner').style.display = 'none';

    // Update playlist UI
    document.querySelectorAll('.video-item').forEach((el, i) => {
        el.classList.remove('playing','loading-item');
        el.classList.toggle('playing', i === index);
        const ic  = document.getElementById(`vi-icon-${i}`);
        const tag = document.getElementById(`vi-tag-${i}`);
        if (ic)  ic.className  = 'fas ' + (i===index ? 'fa-pause' : 'fa-play');
        if (tag) tag.style.display = i===index ? 'inline-flex' : 'none';
    });

    // Auto-expand subject + unit containing the playing video
    const activeItem = document.getElementById(`vi-${index}`);
    if (activeItem) {
        const parentUnitBody = activeItem.closest('.unit-body');
        const parentSubjBody = activeItem.closest('.subj-body');
        if (parentSubjBody && !parentSubjBody.classList.contains('active')) {
            const slug = parentSubjBody.id.replace('sb-', '');
            toggleSubject(slug);
        }
        if (parentUnitBody && !parentUnitBody.classList.contains('active')) {
            const slug = parentUnitBody.id.replace('ub-', '');
            toggleUnit(slug);
        }
    }

    document.getElementById('btnPrev').disabled = index === 0;
    document.getElementById('btnNext').disabled = index === TOTAL - 1;

    currentIndex = index;

    if (window.innerWidth <= 1080)
        document.querySelector('.player-box').scrollIntoView({behavior:'smooth',block:'start'});
    document.getElementById(`vi-${index}`).scrollIntoView({behavior:'smooth',block:'nearest'});
}

function showPlayerError(msg) {
    const wrap = document.getElementById('videoFrameWrap');
    const ov   = document.getElementById('ytOverlay');
    const old  = document.getElementById('nativeVideo') || document.getElementById('ytFrame') || document.querySelector('.player-loading');
    if (old) old.remove();
    const err = document.createElement('div');
    err.className = 'player-loading';
    err.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:#ff6b6b;font-size:2rem;"></i>
        <div class="loader-text" style="color:#ff6b6b;">${msg}</div>`;
    wrap.insertBefore(err, ov);
    document.querySelectorAll('.video-item').forEach(el => el.classList.remove('loading-item'));
}

// Called by YT API when ready
function onYouTubeIframeAPIReady() {}

function onPlayerReady(e) { startProgressLoop(); }

function onPlayerStateChange(e) {
    if (e.data === YT.PlayerState.PLAYING) {
        isPlaying = true; setIcon('fa-pause'); startProgressLoop();
    } else if (e.data === YT.PlayerState.PAUSED || e.data === YT.PlayerState.ENDED) {
        isPlaying = false; setIcon('fa-play');
        if (e.data === YT.PlayerState.ENDED) {
            showQuizBanner(currentIndex);
            if (currentIndex < TOTAL-1)
                setTimeout(() => playVideo(currentIndex+1), 3000);
        }
    }
}

function setupNative() {
    if (!nativeVid) return;
    nativeVid.addEventListener('play',  () => { isPlaying=true; setIcon('fa-pause'); startProgressLoop(); });
    nativeVid.addEventListener('pause', () => { isPlaying=false; setIcon('fa-play'); });
    nativeVid.addEventListener('ended', () => {
        isPlaying=false; setIcon('fa-play');
        showQuizBanner(currentIndex);
        if (currentIndex < TOTAL-1) setTimeout(() => playVideo(currentIndex+1), 3000);
    });
    nativeVid.addEventListener('loadedmetadata', () => {
        document.getElementById('timeDuration').textContent = fmtTime(nativeVid.duration);
    });
    startProgressLoop();
}

// ── Controls ──
function togglePlayPause() {
    if (isYT && ytPlayer) {
        ytPlayer.getPlayerState() === YT.PlayerState.PLAYING ? ytPlayer.pauseVideo() : ytPlayer.playVideo();
    } else if (nativeVid) {
        nativeVid.paused ? nativeVid.play() : nativeVid.pause();
    }
}

function setIcon(cls) {
    const ic = document.getElementById('ppIcon');
    if (ic) ic.className = 'fas ' + cls;
}

function startProgressLoop() {
    clearInterval(progressTimer);
    progressTimer = setInterval(updateProgress, 500);
}

function resetProgress() {
    document.getElementById('seekFill').style.width = '0%';
    document.getElementById('timeCurrent').textContent  = '0:00';
    document.getElementById('timeDuration').textContent = '0:00';
}

function updateProgress() {
    let cur=0, dur=0;
    if (isYT && ytPlayer && ytPlayer.getCurrentTime) {
        cur = ytPlayer.getCurrentTime()||0;
        dur = ytPlayer.getDuration()||0;
    } else if (nativeVid) {
        cur = nativeVid.currentTime||0;
        dur = nativeVid.duration||0;
    }
    if (dur>0) {
        document.getElementById('seekFill').style.width = (cur/dur*100)+'%';
        document.getElementById('timeCurrent').textContent  = fmtTime(cur);
        document.getElementById('timeDuration').textContent = fmtTime(dur);
    }
}

document.getElementById('seekBg').addEventListener('click', function(e) {
    const pct = (e.clientX - this.getBoundingClientRect().left) / this.offsetWidth;
    seekToPct(pct);
});

let isDragging = false;
document.getElementById('seekBg').addEventListener('mousedown', () => isDragging=true);
document.addEventListener('mousemove', e => {
    if (!isDragging) return;
    const bg = document.getElementById('seekBg');
    seekToPct(Math.min(1, Math.max(0, (e.clientX - bg.getBoundingClientRect().left) / bg.offsetWidth)));
});
document.addEventListener('mouseup', () => isDragging=false);

function seekToPct(pct) {
    let dur=0;
    if (isYT && ytPlayer && ytPlayer.getDuration) dur = ytPlayer.getDuration();
    else if (nativeVid) dur = nativeVid.duration;
    if (dur>0) {
        const t = pct * dur;
        if (isYT && ytPlayer) ytPlayer.seekTo(t, true);
        else if (nativeVid) nativeVid.currentTime = t;
        updateProgress();
    }
}

function seekOffset(sec) {
    if (isYT && ytPlayer && ytPlayer.getCurrentTime)
        ytPlayer.seekTo(Math.max(0, ytPlayer.getCurrentTime()+sec), true);
    else if (nativeVid)
        nativeVid.currentTime = Math.max(0, nativeVid.currentTime+sec);
    updateProgress();
}

function toggleMute() {
    isMuted = !isMuted;
    if (isYT && ytPlayer) isMuted ? ytPlayer.mute() : ytPlayer.unMute();
    else if (nativeVid) nativeVid.muted = isMuted;
    document.getElementById('volIcon').className = 'fas '+(isMuted?'fa-volume-mute':'fa-volume-up');
    document.getElementById('volSlider').value = isMuted ? 0 : 100;
}

function setVolume(val) {
    const v = parseInt(val);
    if (isYT && ytPlayer) ytPlayer.setVolume(v);
    else if (nativeVid) nativeVid.volume = v/100;
    isMuted = v===0;
    document.getElementById('volIcon').className = 'fas '+(v===0?'fa-volume-mute':v<50?'fa-volume-down':'fa-volume-up');
}

function toggleSpeedMenu() { document.getElementById('speedMenu').classList.toggle('open'); }

function setSpeed(s) {
    currentSpeed = s;
    document.getElementById('speedLabel').textContent = s+'x';
    document.querySelectorAll('.speed-opt').forEach(el =>
        el.classList.toggle('active', parseFloat(el.textContent)===s));
    if (isYT && ytPlayer) ytPlayer.setPlaybackRate(s);
    else if (nativeVid) nativeVid.playbackRate = s;
    document.getElementById('speedMenu').classList.remove('open');
}

document.addEventListener('click', e => {
    if (!e.target.closest('.speed-wrap'))
        document.getElementById('speedMenu').classList.remove('open');
});

function toggleFullscreen() {
    const box = document.querySelector('.player-box');
    if (!document.fullscreenElement) {
        // Request fullscreen
        const req = box.requestFullscreen
            || box.webkitRequestFullscreen
            || box.mozRequestFullScreen
            || box.msRequestFullscreen;
        if (req) req.call(box);

        // Lock to landscape on mobile
        if (screen.orientation && screen.orientation.lock) {
            screen.orientation.lock('landscape').catch(() => {});
        } else if (window.screen.lockOrientation) {
            window.screen.lockOrientation('landscape');
        }
        document.getElementById('fsIcon').className = 'fas fa-compress';
    } else {
        // Exit fullscreen
        const exit = document.exitFullscreen
            || document.webkitExitFullscreen
            || document.mozCancelFullScreen
            || document.msExitFullscreen;
        if (exit) exit.call(document);

        // Unlock orientation
        if (screen.orientation && screen.orientation.unlock) {
            screen.orientation.unlock();
        } else if (window.screen.unlockOrientation) {
            window.screen.unlockOrientation();
        }
        document.getElementById('fsIcon').className = 'fas fa-expand';
    }
}

// Auto-unlock orientation when fullscreen exits via system back button
document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement) {
        if (screen.orientation && screen.orientation.unlock) {
            screen.orientation.unlock();
        }
        document.getElementById('fsIcon').className = 'fas fa-expand';
    }
});
document.addEventListener('webkitfullscreenchange', () => {
    if (!document.webkitFullscreenElement) {
        if (screen.orientation && screen.orientation.unlock) {
            screen.orientation.unlock();
        }
        document.getElementById('fsIcon').className = 'fas fa-expand';
    }
});

// External navigation
function playVideo(index) { if (index>=0 && index<TOTAL) loadVideo(index); }
function navigateVideo(dir) { const n=currentIndex+dir; if(n>=0&&n<TOTAL) loadVideo(n); }

/* ══ UNIT COLLAPSE ══ */
/* ══ UNIT COLLAPSE ══ */
/* ══ SUBJECT — one active at a time ══ */
function toggleSubject(slug) {
    const clickedBody = document.getElementById('sb-' + slug);
    const clickedBtn  = document.getElementById('sbtn-' + slug);
    const clickedChev = document.getElementById('sc-' + slug);
    if (!clickedBody) return;

    const isActive = clickedBody.classList.contains('active');

    // Close ALL subjects first
    document.querySelectorAll('.subj-body').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.subj-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('[id^="sc-"]').forEach(ch => {
        ch.style.transform = '';
        ch.style.color = '';
    });

    if (!isActive) {
        // Open this one
        clickedBody.classList.add('active');
        clickedBtn && clickedBtn.classList.add('active');
        if (clickedChev) {
            clickedChev.style.transform = 'rotate(180deg)';
            clickedChev.style.color = 'rgba(255,215,0,.7)';
        }
        // Activate first unit inside
        const firstUnitBody = clickedBody.querySelector('.unit-body');
        const firstUnitBtn  = clickedBody.querySelector('.unit-btn');
        const firstUnitChev = clickedBody.querySelector('[id^="uc-"]');
        if (firstUnitBody) {
            firstUnitBody.classList.add('active');
            firstUnitBtn && firstUnitBtn.classList.add('active');
            if (firstUnitChev) firstUnitChev.style.transform = 'rotate(180deg)';
        }
        // Scroll subject into view
        setTimeout(() => {
            clickedBtn && clickedBtn.scrollIntoView({behavior:'smooth', block:'nearest'});
        }, 80);
    }
}

/* ══ UNIT — one active per subject ══ */
function toggleUnit(slug) {
    const clickedBody = document.getElementById('ub-' + slug);
    const clickedBtn  = document.getElementById('ubtn-' + slug);
    const clickedChev = document.getElementById('uc-' + slug);
    if (!clickedBody) return;

    const isActive = clickedBody.classList.contains('active');

    // Find parent subject body, close all units inside it
    const parentSubjBody = clickedBody.closest('.subj-body');
    if (parentSubjBody) {
        parentSubjBody.querySelectorAll('.unit-body').forEach(b => b.classList.remove('active'));
        parentSubjBody.querySelectorAll('.unit-btn').forEach(b => b.classList.remove('active'));
        parentSubjBody.querySelectorAll('[id^="uc-"]').forEach(ch => ch.style.transform = '');
    }

    if (!isActive) {
        clickedBody.classList.add('active');
        clickedBtn && clickedBtn.classList.add('active');
        if (clickedChev) clickedChev.style.transform = 'rotate(180deg)';
        setTimeout(() => {
            clickedBtn && clickedBtn.scrollIntoView({behavior:'smooth', block:'nearest'});
        }, 80);
    }
}

/* ══ MOBILE PLAYLIST TOGGLE ══ */
function togglePlaylist() {
    const list    = document.getElementById('videoList');
    const chevron = document.getElementById('toggleChevron');
    const toggle  = document.getElementById('playlistToggle');
    const isOpen  = list.classList.toggle('mobile-open');
    if (chevron) chevron.style.transform = isOpen ? 'rotate(180deg)' : '';
    toggle.querySelector('span').innerHTML = isOpen
        ? '<i class="fas fa-list" style="margin-right:6px;color:var(--gold);"></i>Hide Lectures'
        : '<i class="fas fa-list" style="margin-right:6px;color:var(--gold);"></i>Show Lectures';
}

/* ══ SCROLL TO SUBJECT FROM CHIP ══ */
function scrollToSubject(name) {
    const list = document.getElementById('videoList');
    // On mobile: open playlist first
    if (list && !list.classList.contains('mobile-open')) togglePlaylist();

    // Find subject button by name and activate it
    document.querySelectorAll('.subj-btn').forEach(btn => {
        const nameEl = btn.querySelector('.subj-btn-name');
        if (nameEl && nameEl.textContent.trim() === name) {
            const slug = btn.id.replace('sbtn-', '');
            const body = document.getElementById('sb-' + slug);
            if (body && !body.classList.contains('active')) {
                toggleSubject(slug);
            }
            setTimeout(() => btn.scrollIntoView({behavior:'smooth', block:'start'}), 120);
        }
    });
}

/* ══ QUIZ BANNER ══ */
function showQuizBanner(index) {
    const meta    = VIDEO_META[index];
    if (!meta) return;
    const dbId    = meta.db_id;
    const quizId  = QUIZ_MAP[dbId];
    const banner  = document.getElementById('quizBanner');
    const inner   = document.getElementById('quizBannerInner');
    const noneDiv = document.getElementById('quizNoneInner');

    banner.style.display = 'block';

    if (quizId) {
        inner.style.display   = 'block';
        noneDiv.style.display = 'none';

        // Set quiz title
        document.getElementById('quizBannerTitle').textContent = 'Quiz: ' + meta.title;

        // Check if attempted before
        const scoreData = SCORE_MAP[quizId];
        const scorePill = document.getElementById('quizScorePill');
        const btnText   = document.getElementById('quizBannerBtnText');

        if (scoreData) {
            const pct = Math.round(scoreData.score / scoreData.total * 100);
            scorePill.style.display  = 'flex';
            scorePill.innerHTML      = `<i class="fas fa-trophy" style="margin-right:4px;"></i> Best: ${scoreData.score}/${scoreData.total} (${pct}%)`;
            btnText.textContent      = 'Retake Quiz';
            document.getElementById('quizBannerSub').textContent = 'You have already attempted this quiz.';
        } else {
            scorePill.style.display  = 'none';
            btnText.textContent      = 'Start Quiz';
            document.getElementById('quizBannerSub').textContent = 'Test your knowledge from this lecture!';
        }

        document.getElementById('quizBannerBtn').href = `quiz.php?quiz_id=${quizId}`;

        // Animate in
        banner.style.animation = 'none';
        banner.offsetHeight; // reflow
        banner.style.animation = 'slideUpFade .4s ease forwards';
    } else {
        inner.style.display   = 'none';
        noneDiv.style.display = 'flex';
    }
}

function fmtTime(sec) {
    if (!sec||isNaN(sec)) return '0:00';
    return Math.floor(sec/60)+':'+(('0'+Math.floor(sec%60)).slice(-2));
}

// ── Overlay: tap = pause/play | double-tap sides = seek ──
(function() {
    var overlay = document.getElementById('ytOverlay');
    if (!overlay) return;

    var rippleLeft  = document.getElementById('rippleLeft');
    var rippleRight = document.getElementById('rippleRight');
    var rippleTimerL, rippleTimerR;
    var singleTapTimer = null;
    var lastTapTime = 0;
    var DOUBLE_TAP_DELAY = 280; // ms

    function showRipple(el, timer) {
        clearTimeout(timer);
        el.classList.add('show');
        return setTimeout(function(){ el.classList.remove('show'); }, 700);
    }

    // ── TOUCH: double-tap sides to seek, single tap to pause ──
    overlay.addEventListener('touchend', function(e) {
        e.preventDefault();
        var touch = e.changedTouches[0];
        var rect  = overlay.getBoundingClientRect();
        var x     = touch.clientX - rect.left;
        var now   = Date.now();
        var isLeft  = x < rect.width * 0.35;
        var isRight = x > rect.width * 0.65;
        var isCenter = !isLeft && !isRight;

        if (now - lastTapTime < DOUBLE_TAP_DELAY) {
            // Double tap
            clearTimeout(singleTapTimer);
            lastTapTime = 0;
            if (isLeft) {
                seekOffset(-10);
                rippleTimerL = showRipple(rippleLeft, rippleTimerL);
            } else if (isRight) {
                seekOffset(10);
                rippleTimerR = showRipple(rippleRight, rippleTimerR);
            } else {
                togglePlayPause();
            }
        } else {
            // Single tap — wait to see if double tap follows
            lastTapTime = now;
            singleTapTimer = setTimeout(function() {
                togglePlayPause();
            }, DOUBLE_TAP_DELAY);
        }
    }, {passive: false});

    // ── MOUSE (desktop): single click = pause, double click sides = seek ──
    overlay.addEventListener('click', function(e) {
        var rect   = overlay.getBoundingClientRect();
        var x      = e.clientX - rect.left;
        var isLeft  = x < rect.width * 0.35;
        var isRight = x > rect.width * 0.65;
        // Single click anywhere = pause/play
        togglePlayPause();
    });

    overlay.addEventListener('dblclick', function(e) {
        var rect   = overlay.getBoundingClientRect();
        var x      = e.clientX - rect.left;
        var isLeft  = x < rect.width * 0.35;
        var isRight = x > rect.width * 0.65;
        if (isLeft) {
            seekOffset(-10);
            rippleTimerL = showRipple(rippleLeft, rippleTimerL);
        } else if (isRight) {
            seekOffset(10);
            rippleTimerR = showRipple(rippleRight, rippleTimerR);
        }
    });
})();

// ── Sidebar toggle ──
const menuToggle = document.getElementById('menuToggle');
const sidebar    = document.getElementById('sidebar');
menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
document.addEventListener('click', e => {
    if (sidebar.classList.contains('open') &&
        !sidebar.contains(e.target) && !menuToggle.contains(e.target))
        sidebar.classList.remove('open');
});

// ── Security ──
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('keydown', e => {
    if (e.key==='F12'||(e.ctrlKey&&e.shiftKey&&['I','J','C'].includes(e.key.toUpperCase()))||(e.ctrlKey&&['U','S'].includes(e.key.toUpperCase()))) {
        e.preventDefault(); return false;
    }
});
document.querySelectorAll('img').forEach(img => img.addEventListener('dragstart', e=>e.preventDefault()));
</script>
</body>
</html>