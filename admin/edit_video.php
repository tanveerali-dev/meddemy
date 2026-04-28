<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$video_id = (int)($_GET['id'] ?? $_POST['video_id'] ?? 0);
if (!$video_id) { header("Location: manage_videos.php"); exit(); }

// Fetch video
$stmt = $conn->prepare("SELECT v.*, c.title as course_title FROM video v JOIN course c ON c.course_id=v.course_id WHERE v.video_id=?");
$stmt->bind_param("i", $video_id);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();
if (!$video) { header("Location: manage_videos.php"); exit(); }

$courses = $conn->query("SELECT course_id, title FROM course ORDER BY title ASC");
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id    = (int)$_POST['course_id'];
    $title        = htmlspecialchars(trim($_POST['title']));
    $youtube_link = trim($_POST['youtube_link']);

    $upd = $conn->prepare("UPDATE video SET course_id=?, title=?, youtube_link=? WHERE video_id=?");
    $upd->bind_param("issi", $course_id, $title, $youtube_link, $video_id);
    if ($upd->execute()) {
        // Refresh
        $stmt->execute();
        $video = $stmt->get_result()->fetch_assoc();
        $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Video updated successfully! <a href="manage_videos.php">Back to videos →</a></div>';
    } else {
        $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Update failed: ' . htmlspecialchars($conn->error) . '</div>';
    }
}

function getVideoID($url) {
    if (strpos($url, "watch?v=") !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        return $q['v'] ?? null;
    }
    if (strpos($url, "youtu.be/") !== false) return basename(strtok($url,'?'));
    if (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $url, $m)) return $m[1];
    return null;
}

$currentVid = getVideoID($video['youtube_link']);

$page_title = "Edit Video";
$active_nav = "add_video";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Edit Video</h1>
        <p>Update video title, course assignment or YouTube link</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_videos.php" class="btn-adm btn-adm-ghost">
            <i class="fas fa-arrow-left"></i> Back to Videos
        </a>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:22px;align-items:start;">

    <!-- Form -->
    <div class="adm-card">
        <div class="adm-card-body" style="padding-top:24px;">
            <form method="POST" class="adm-form">
                <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">

                <div class="form-group">
                    <label class="form-label">Assign to Course <span>*</span></label>
                    <select name="course_id" class="form-select" required>
                        <?php
                        $courses->data_seek(0);
                        while($c = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $c['course_id']; ?>"
                            <?php echo $c['course_id'] == $video['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['title']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Video Title <span>*</span></label>
                    <input type="text" name="title" class="form-input"
                           value="<?php echo htmlspecialchars($video['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">YouTube Link <span>*</span></label>
                    <div class="form-input-icon">
                        <i class="fab fa-youtube" style="color:#ff4444;"></i>
                        <input type="url" name="youtube_link" id="ytInput" class="form-input"
                               value="<?php echo htmlspecialchars($video['youtube_link']); ?>"
                               required oninput="previewYT(this.value)">
                    </div>
                    <!-- Preview -->
                    <div id="ytPreview" style="margin-top:10px;border-radius:10px;overflow:hidden;border:1px solid var(--border3);position:relative;<?php echo $currentVid ? '' : 'display:none;'; ?>">
                        <img id="ytThumb"
                             src="<?php echo $currentVid ? "https://img.youtube.com/vi/{$currentVid}/mqdefault.jpg" : ''; ?>"
                             alt="Preview"
                             style="width:100%;display:block;max-height:180px;object-fit:cover;">
                        <div style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,.65);color:#fff;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                            <i class="fab fa-youtube" style="color:#ff4444;"></i> Preview
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;padding-top:4px;">
                    <button type="submit" class="btn-adm btn-adm-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="manage_videos.php" class="btn-adm btn-adm-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Current video info -->
    <div class="adm-card">
        <div class="adm-card-header"><h2>Current Video</h2></div>
        <div class="adm-card-body">
            <?php if ($currentVid): ?>
            <div style="border-radius:10px;overflow:hidden;border:1px solid var(--border3);margin-bottom:12px;">
                <img src="https://img.youtube.com/vi/<?php echo $currentVid; ?>/mqdefault.jpg"
                     style="width:100%;display:block;">
            </div>
            <?php endif; ?>
            <div style="font-size:.83rem;font-weight:600;margin-bottom:6px;"><?php echo htmlspecialchars($video['title']); ?></div>
            <div style="font-size:.75rem;color:var(--muted);margin-bottom:4px;">
                <i class="fas fa-book-open" style="margin-right:4px;color:var(--gold);"></i>
                <?php echo htmlspecialchars($video['course_title']); ?>
            </div>
            <?php if ($currentVid): ?>
            <div style="font-size:.72rem;color:var(--muted);margin-top:8px;">
                <i class="fab fa-youtube" style="color:#ff4444;margin-right:4px;"></i>
                youtu.be/<?php echo htmlspecialchars($currentVid); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function getYTId(url) {
    const patterns = [
        /(?:youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/,
        /(?:youtu\.be\/)([a-zA-Z0-9_-]{11})/,
        /(?:youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
    ];
    for (const p of patterns) { const m = url.match(p); if (m) return m[1]; }
    return null;
}
function previewYT(url) {
    const id = getYTId(url);
    const preview = document.getElementById('ytPreview');
    const thumb   = document.getElementById('ytThumb');
    if (id) {
        thumb.src = `https://img.youtube.com/vi/${id}/mqdefault.jpg`;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}
</script>

<?php include '_layout_end.php'; ?>