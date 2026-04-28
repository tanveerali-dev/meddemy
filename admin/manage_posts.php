<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$msg = '';

/* ── DELETE ── */
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $del = $conn->prepare("DELETE FROM post WHERE post_id=?");
    $del->bind_param("i", $id);
    $del->execute();
    $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Post deleted.</div>';
}

/* ── CREATE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title  = htmlspecialchars(trim($_POST['title']));
    $body   = htmlspecialchars(trim($_POST['body']));
    $badge  = htmlspecialchars(trim($_POST['badge']));
    $color  = in_array($_POST['badge_color'], ['gold','green','blue','red']) ? $_POST['badge_color'] : 'gold';

    if ($title && $body) {
        $ins = $conn->prepare("INSERT INTO post (title, body, badge, badge_color) VALUES (?,?,?,?)");
        $ins->bind_param("ssss", $title, $body, $badge, $color);
        $ins->execute();
        $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Post published successfully!</div>';
    } else {
        $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Title and body are required.</div>';
    }
}

$posts = $conn->query("SELECT * FROM post ORDER BY created_at DESC");

$page_title = "Announcements";
$active_nav = "manage_posts";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Announcements</h1>
        <p>Publish updates about new courses, schedules and more</p>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:22px;align-items:start;">

    <!-- Posts list -->
    <div>
        <?php if ($posts->num_rows > 0): ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
        <?php while($p = $posts->fetch_assoc()):
            $colors = [
                'gold'  => ['bg'=>'rgba(255,215,0,.1)',  'border'=>'rgba(255,215,0,.25)',  'text'=>'var(--gold)'],
                'green' => ['bg'=>'rgba(34,197,94,.1)',  'border'=>'rgba(34,197,94,.25)',  'text'=>'var(--green)'],
                'blue'  => ['bg'=>'rgba(59,130,246,.1)', 'border'=>'rgba(59,130,246,.25)', 'text'=>'var(--blue)'],
                'red'   => ['bg'=>'rgba(255,77,77,.1)',  'border'=>'rgba(255,77,77,.25)',  'text'=>'var(--red)'],
            ];
            $col = $colors[$p['badge_color']] ?? $colors['gold'];
        ?>
        <div style="background:var(--s2);border:1px solid var(--border2);border-radius:16px;overflow:hidden;transition:border-color .2s;"
             onmouseover="this.style.borderColor='rgba(255,215,0,.2)'"
             onmouseout="this.style.borderColor='var(--border2)'">

            <!-- Post header -->
            <div style="padding:16px 20px 12px;display:flex;align-items:flex-start;gap:14px;">
                <!-- Icon -->
                <div style="width:40px;height:40px;border-radius:11px;background:<?php echo $col['bg']; ?>;border:1px solid <?php echo $col['border']; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                    <i class="fas fa-bullhorn" style="color:<?php echo $col['text']; ?>;font-size:.88rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-bottom:5px;">
                        <span style="font-family:'Playfair Display',serif;font-size:.97rem;font-weight:700;line-height:1.3;">
                            <?php echo htmlspecialchars($p['title']); ?>
                        </span>
                        <span style="font-size:.65rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:2px 10px;border-radius:20px;background:<?php echo $col['bg']; ?>;color:<?php echo $col['text']; ?>;border:1px solid <?php echo $col['border']; ?>;white-space:nowrap;">
                            <?php echo htmlspecialchars($p['badge']); ?>
                        </span>
                    </div>
                    <div style="font-size:.82rem;color:rgba(255,255,255,.65);line-height:1.6;white-space:pre-wrap;"><?php echo htmlspecialchars($p['body']); ?></div>
                </div>
            </div>

            <!-- Footer -->
            <div style="padding:10px 20px;border-top:1px solid rgba(255,255,255,.05);display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div style="font-size:.73rem;color:var(--muted2);">
                    <i class="fas fa-clock" style="margin-right:4px;"></i>
                    <?php echo date('d M Y, h:i A', strtotime($p['created_at'])); ?>
                </div>
                <a href="?delete=<?php echo $p['post_id']; ?>"
                   class="btn-adm btn-adm-danger btn-adm-sm"
                   onclick="return confirm('Delete this post?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        </div>
        <?php endwhile; ?>
        </div>

        <?php else: ?>
        <div class="adm-empty">
            <i class="fas fa-bullhorn"></i>
            <p>No announcements yet. Publish your first post!</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Create post form -->
    <div style="position:sticky;top:74px;">
        <div class="adm-card">
            <div class="adm-card-header">
                <h2><i class="fas fa-plus-circle" style="color:var(--gold);margin-right:7px;font-size:.85rem;"></i>New Announcement</h2>
            </div>
            <div class="adm-card-body" style="padding-top:20px;">
                <form method="POST" class="adm-form">

                    <div class="form-group">
                        <label class="form-label">Title <span>*</span></label>
                        <input type="text" name="title" class="form-input"
                               placeholder="e.g. New AFNS Course Launching Soon!" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Badge Label</label>
                        <input type="text" name="badge" class="form-input"
                               placeholder="New Course / Update / Alert..."
                               list="badge-suggestions" value="Update">
                        <datalist id="badge-suggestions">
                            <option value="New Course">
                            <option value="Coming Soon">
                            <option value="Update">
                            <option value="Important">
                            <option value="Discount">
                            <option value="New Batch">
                            <option value="Schedule">
                            <option value="Result">
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Badge Color</label>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <?php foreach([
                                'gold'  => ['#FFD700','Gold'],
                                'green' => ['#22c55e','Green'],
                                'blue'  => ['#3b82f6','Blue'],
                                'red'   => ['#ff4d4d','Red'],
                            ] as $val => [$hex, $label]): ?>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.8rem;padding:6px 12px;border-radius:8px;background:var(--s3);border:1px solid var(--border3);transition:all .2s;"
                                   id="clabel-<?php echo $val; ?>"
                                   onmouseover="this.style.borderColor='<?php echo $hex; ?>40'"
                                   onmouseout="updateColorLabels()">
                                <input type="radio" name="badge_color" value="<?php echo $val; ?>"
                                       <?php echo $val==='gold'?'checked':''; ?>
                                       onchange="updateColorLabels()"
                                       style="display:none;">
                                <span style="width:10px;height:10px;border-radius:50%;background:<?php echo $hex; ?>;display:inline-block;"></span>
                                <?php echo $label; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message <span>*</span></label>
                        <textarea name="body" class="form-textarea" rows="5"
                                  placeholder="Write your announcement here — upcoming course details, schedule, discounts..." required></textarea>
                    </div>

                    <button type="submit" class="btn-adm btn-adm-primary" style="width:100%;justify-content:center;">
                        <i class="fas fa-paper-plane"></i> Publish Announcement
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
const colorMap = {
    gold:  '#FFD700',
    green: '#22c55e',
    blue:  '#3b82f6',
    red:   '#ff4d4d',
};
function updateColorLabels() {
    const checked = document.querySelector('input[name="badge_color"]:checked')?.value;
    ['gold','green','blue','red'].forEach(c => {
        const lbl = document.getElementById('clabel-' + c);
        lbl.style.borderColor = c === checked ? colorMap[c] + '80' : 'var(--border3)';
        lbl.style.background  = c === checked ? colorMap[c] + '12' : 'var(--s3)';
    });
}
updateColorLabels();
</script>

<?php include '_layout_end.php'; ?>