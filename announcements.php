<?php
include("includes/db.php");

// All posts, latest first
$posts = $conn->query("SELECT * FROM post ORDER BY created_at DESC");
$total = $posts->num_rows;

// Single post view via ?id=
$single = null;
if (isset($_GET['id'])) {
    $sid  = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM post WHERE post_id=?");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $single = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Announcements — MEDDEMY</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --gold:#FFD700;--gold2:#ffed4e;
    --bg:#080808;--s1:#111;--s2:#171717;--s3:#1e1e1e;
    --border:rgba(255,215,0,.12);--border2:#222;
    --text:#f0f0f0;--muted:rgba(255,255,255,.45);
    --green:#22c55e;--red:#ff4d4d;--blue:#3b82f6;
    --ease:.26s cubic-bezier(.4,0,.2,1);
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-thumb{background:#333;border-radius:5px}
a{text-decoration:none;color:inherit;}

/* ── NAV ── */
.nav{position:sticky;top:0;z-index:90;background:rgba(8,8,8,.92);backdrop-filter:blur(18px);border-bottom:1px solid var(--border);padding:0 5vw;}
.nav-inner{max-width:900px;margin:0 auto;height:58px;display:flex;align-items:center;justify-content:space-between;gap:12px;}
.nav-brand{font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:900;color:var(--gold);}
.nav-links{display:flex;align-items:center;gap:6px;}
.nav-link{padding:7px 15px;border-radius:9px;font-size:.82rem;font-weight:600;color:var(--muted);transition:var(--ease);}
.nav-link:hover{color:var(--text);background:rgba(255,255,255,.06);}
.nav-link.cta{background:linear-gradient(135deg,var(--gold),var(--gold2));color:#111;padding:7px 18px;}
.nav-link.cta:hover{box-shadow:0 4px 16px rgba(255,215,0,.3);}

/* ── HERO ── */
.hero{padding:52px 5vw 40px;text-align:center;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;top:-80px;left:50%;transform:translateX(-50%);width:500px;height:300px;background:radial-gradient(ellipse,rgba(255,215,0,.06),transparent 65%);pointer-events:none;}
.hero-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(255,215,0,.1);border:1px solid rgba(255,215,0,.25);color:var(--gold);font-size:.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:5px 16px;border-radius:30px;margin-bottom:18px;}
.hero h1{font-family:'Playfair Display',serif;font-size:clamp(1.8rem,5vw,2.8rem);font-weight:900;margin-bottom:12px;}
.hero h1 span{color:var(--gold);}
.hero p{font-size:.92rem;color:var(--muted);max-width:480px;margin:0 auto 20px;}
.hero-count{display:inline-flex;align-items:center;gap:7px;font-size:.8rem;font-weight:600;color:var(--muted);background:var(--s2);border:1px solid var(--border2);border-radius:30px;padding:5px 14px;}

/* ── POSTS GRID ── */
.page-wrap{max-width:900px;margin:0 auto;padding:36px 5vw 60px;}
.posts-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;}

/* ── POST CARD ── */
.post-card{
    background:var(--s1);
    border:1px solid var(--border2);
    border-radius:18px;
    overflow:hidden;
    cursor:pointer;
    transition:var(--ease);
    display:flex;flex-direction:column;
}
.post-card:hover{
    border-color:rgba(255,215,0,.28);
    transform:translateY(-4px);
    box-shadow:0 16px 40px rgba(0,0,0,.5);
}
.post-card-accent{height:3px;}
.post-card-body{padding:20px 20px 14px;flex:1;}
.post-badge{display:inline-flex;align-items:center;gap:5px;font-size:.64rem;font-weight:700;letter-spacing:.9px;text-transform:uppercase;padding:3px 10px;border-radius:20px;margin-bottom:12px;}
.post-badge.gold {background:rgba(255,215,0,.1); color:var(--gold); border:1px solid rgba(255,215,0,.22);}
.post-badge.green{background:rgba(34,197,94,.1);  color:var(--green);border:1px solid rgba(34,197,94,.22);}
.post-badge.blue {background:rgba(59,130,246,.1); color:var(--blue); border:1px solid rgba(59,130,246,.22);}
.post-badge.red  {background:rgba(255,77,77,.1);  color:var(--red);  border:1px solid rgba(255,77,77,.22);}

.post-title{font-family:'Playfair Display',serif;font-size:1.02rem;font-weight:700;line-height:1.35;margin-bottom:9px;}
.post-preview{font-size:.8rem;color:var(--muted);line-height:1.65;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
.post-card-footer{padding:12px 20px;border-top:1px solid rgba(255,255,255,.05);display:flex;align-items:center;justify-content:space-between;}
.post-date{font-size:.7rem;color:rgba(255,255,255,.28);}
.post-read-more{font-size:.75rem;font-weight:700;color:var(--gold);display:flex;align-items:center;gap:5px;}

/* ── MODAL ── */
.modal-overlay{
    position:fixed;inset:0;z-index:999;
    background:rgba(0,0,0,.75);backdrop-filter:blur(8px);
    display:flex;align-items:center;justify-content:center;
    padding:20px;
    opacity:0;pointer-events:none;transition:opacity .25s ease;
}
.modal-overlay.open{opacity:1;pointer-events:all;}
.modal{
    background:var(--s1);border:1px solid var(--border2);
    border-radius:22px;max-width:580px;width:100%;
    max-height:85vh;overflow-y:auto;
    transform:scale(.94) translateY(18px);
    transition:transform .28s cubic-bezier(.4,0,.2,1);
    position:relative;
}
.modal-overlay.open .modal{transform:scale(1) translateY(0);}
.modal-top-accent{height:4px;border-radius:22px 22px 0 0;}
.modal-header{padding:24px 26px 18px;border-bottom:1px solid rgba(255,255,255,.06);}
.modal-badge{margin-bottom:12px;}
.modal-title{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:900;line-height:1.3;}
.modal-body{padding:22px 26px 24px;}
.modal-text{font-size:.9rem;color:rgba(255,255,255,.75);line-height:1.75;white-space:pre-wrap;}
.modal-footer{padding:14px 26px;border-top:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between;}
.modal-date{font-size:.75rem;color:var(--muted);}
.modal-close-btn{
    position:absolute;top:16px;right:16px;
    width:32px;height:32px;border-radius:50%;
    background:rgba(255,255,255,.07);border:none;
    color:var(--muted);font-size:.9rem;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    transition:var(--ease);
}
.modal-close-btn:hover{background:rgba(255,255,255,.14);color:var(--text);}
.btn-modal-close{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:10px;background:var(--s3);border:none;color:var(--text);font-size:.83rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:var(--ease);}
.btn-modal-close:hover{background:var(--s2);}

/* ── EMPTY ── */
.empty{text-align:center;padding:70px 20px;}
.empty i{font-size:2.8rem;color:rgba(255,255,255,.1);display:block;margin-bottom:16px;}
.empty p{color:var(--muted);font-size:.9rem;}

/* ── FOOTER ── */
.site-footer{padding:24px 5vw;border-top:1px solid var(--border);text-align:center;font-size:.75rem;color:rgba(255,255,255,.22);}

/* ── RESPONSIVE ── */
@media(max-width:500px){
    .posts-grid{grid-template-columns:1fr;}
    .modal-header,.modal-body,.modal-footer{padding-left:18px;padding-right:18px;}
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav">
    <div class="nav-inner">
        <a href="index.php" class="nav-brand">MEDDEMY</a>
        <div class="nav-links">
            <a href="index.php" class="nav-link">Home</a>
            <a href="students/materials.php" class="nav-link">Free Notes</a>
            <a href="login.php" class="nav-link cta">Login</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge"><i class="fas fa-bullhorn"></i> Official Updates</div>
    <h1>Latest <span>Announcements</span></h1>
    <p>Stay updated with new courses, upcoming batches, schedules and important notices</p>
    <div class="hero-count">
        <i class="fas fa-layer-group" style="color:var(--gold);"></i>
        <?php echo $total; ?> announcement<?php echo $total != 1 ? 's' : ''; ?> published
    </div>
</section>

<!-- POSTS -->
<div class="page-wrap">
<?php if ($total > 0):
    $posts->data_seek(0);
    $accentColors = [
        'gold'  => 'linear-gradient(90deg,#FFD700,#ffed4e)',
        'green' => 'linear-gradient(90deg,#22c55e,#16a34a)',
        'blue'  => 'linear-gradient(90deg,#3b82f6,#2563eb)',
        'red'   => 'linear-gradient(90deg,#ff4d4d,#dc2626)',
    ];
?>
<div class="posts-grid">
<?php while($p = $posts->fetch_assoc()):
    $color   = $p['badge_color'] ?? 'gold';
    $accent  = $accentColors[$color] ?? $accentColors['gold'];
    $preview = mb_substr($p['body'], 0, 160) . (mb_strlen($p['body']) > 160 ? '...' : '');
    $encoded = htmlspecialchars(json_encode([
        'id'    => $p['post_id'],
        'title' => $p['title'],
        'body'  => $p['body'],
        'badge' => $p['badge'],
        'color' => $color,
        'date'  => date('d M Y, h:i A', strtotime($p['created_at'])),
        'accent'=> $accent,
    ]), ENT_QUOTES);
?>
<div class="post-card" onclick="openModal(<?php echo htmlspecialchars(json_encode([
    'id'    => $p['post_id'],
    'title' => $p['title'],
    'body'  => $p['body'],
    'badge' => $p['badge'],
    'color' => $color,
    'date'  => date('d M Y, h:i A', strtotime($p['created_at'])),
    'accent'=> $accent,
]), ENT_QUOTES); ?>)">
    <div class="post-card-accent" style="background:<?php echo $accent; ?>;"></div>
    <div class="post-card-body">
        <div class="post-badge <?php echo $color; ?>">
            <i class="fas fa-circle" style="font-size:.45rem;"></i>
            <?php echo htmlspecialchars($p['badge']); ?>
        </div>
        <div class="post-title"><?php echo htmlspecialchars($p['title']); ?></div>
        <div class="post-preview"><?php echo htmlspecialchars($preview); ?></div>
    </div>
    <div class="post-card-footer">
        <span class="post-date"><i class="fas fa-clock" style="margin-right:4px;"></i><?php echo date('d M Y', strtotime($p['created_at'])); ?></span>
        <span class="post-read-more">Read More <i class="fas fa-arrow-right" style="font-size:.65rem;"></i></span>
    </div>
</div>
<?php endwhile; ?>
</div>

<?php else: ?>
<div class="empty">
    <i class="fas fa-bullhorn"></i>
    <p>No announcements yet. Check back soon for updates!</p>
</div>
<?php endif; ?>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="closeOnBg(event)">
    <div class="modal" id="modal">
        <div class="modal-top-accent" id="modalAccent"></div>
        <button class="modal-close-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
        <div class="modal-header">
            <div class="modal-badge">
                <span class="post-badge" id="modalBadge"></span>
            </div>
            <div class="modal-title" id="modalTitle"></div>
        </div>
        <div class="modal-body">
            <div class="modal-text" id="modalText"></div>
        </div>
        <div class="modal-footer">
            <span class="modal-date" id="modalDate"><i class="fas fa-clock" style="margin-right:5px;"></i></span>
            <button class="btn-modal-close" onclick="closeModal()"><i class="fas fa-times"></i> Close</button>
        </div>
    </div>
</div>

<!-- If opened directly via ?id= -->
<?php if ($single): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    openModal({
        title:  <?php echo json_encode($single['title']); ?>,
        body:   <?php echo json_encode($single['body']); ?>,
        badge:  <?php echo json_encode($single['badge']); ?>,
        color:  <?php echo json_encode($single['badge_color']); ?>,
        date:   <?php echo json_encode(date('d M Y, h:i A', strtotime($single['created_at']))); ?>,
        accent: <?php
            $ac = ['gold'=>'linear-gradient(90deg,#FFD700,#ffed4e)','green'=>'linear-gradient(90deg,#22c55e,#16a34a)','blue'=>'linear-gradient(90deg,#3b82f6,#2563eb)','red'=>'linear-gradient(90deg,#ff4d4d,#dc2626)'];
            echo json_encode($ac[$single['badge_color']] ?? $ac['gold']);
        ?>,
    });
});
</script>
<?php endif; ?>

<footer class="site-footer">
    &copy; <?php echo date('Y'); ?> MEDDEMY — Pakistan's Medical Exam Prep Platform. All rights reserved.
</footer>

<script>
function openModal(data) {
    document.getElementById('modalAccent').style.background = data.accent;
    document.getElementById('modalTitle').textContent = data.title;
    document.getElementById('modalText').textContent  = data.body;
    document.getElementById('modalDate').innerHTML    = '<i class="fas fa-clock" style="margin-right:5px;"></i>' + data.date;

    const badge = document.getElementById('modalBadge');
    badge.textContent = data.badge;
    badge.className   = 'post-badge ' + data.color;

    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

function closeOnBg(e) {
    if (e.target === document.getElementById('modalOverlay')) closeModal();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
</body>
</html>