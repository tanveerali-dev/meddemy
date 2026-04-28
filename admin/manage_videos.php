<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Optional course filter
$filter_course = (int)($_GET['course_id'] ?? 0);
$courses_list  = $conn->query("SELECT course_id, title FROM course ORDER BY title ASC");

$where = $filter_course ? "WHERE v.course_id = $filter_course" : '';
$videos = $conn->query("SELECT v.*, c.title as course_title 
    FROM video v 
    JOIN course c ON c.course_id = v.course_id
    $where
    ORDER BY v.course_id ASC, v.video_id ASC");

$msg = '';
if (isset($_GET['deleted'])) $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Video deleted successfully.</div>';
if (isset($_GET['updated'])) $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Video updated successfully.</div>';

function getVideoID($url) {
    if (strpos($url, "watch?v=") !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        return $q['v'] ?? null;
    }
    if (strpos($url, "youtu.be/") !== false) return basename(strtok($url,'?'));
    if (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $url, $m)) return $m[1];
    return null;
}

$page_title = "Manage Videos";
$active_nav = "add_video";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Manage Videos</h1>
        <p>Edit or delete videos from any course</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="add_video.php" class="btn-adm btn-adm-primary">
            <i class="fas fa-plus"></i> Add Video
        </a>
    </div>
</div>

<?php echo $msg; ?>

<!-- Filter by course -->
<div style="margin-bottom:18px;">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <select name="course_id" class="form-select" style="max-width:300px;"
                onchange="this.form.submit()">
            <option value="0">— All Courses —</option>
            <?php
            $courses_list->data_seek(0);
            while($c = $courses_list->fetch_assoc()):
            ?>
            <option value="<?php echo $c['course_id']; ?>"
                <?php echo $filter_course == $c['course_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['title']); ?>
            </option>
            <?php endwhile; ?>
        </select>
        <?php if ($filter_course): ?>
        <a href="manage_videos.php" class="btn-adm btn-adm-ghost btn-adm-sm">
            <i class="fas fa-times"></i> Clear
        </a>
        <?php endif; ?>
    </form>
</div>

<div class="adm-card">
    <div class="adm-card-header">
        <h2><i class="fas fa-video" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>
            Videos <?php if($filter_course): ?><span style="font-size:.75rem;font-weight:400;color:var(--muted);">— filtered by course</span><?php endif; ?>
        </h2>
        <span class="adm-badge blue"><?php echo $videos->num_rows; ?> videos</span>
    </div>
    <div class="adm-card-body">
        <?php if ($videos->num_rows > 0): ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Thumbnail</th>
                        <th>Video Title</th>
                        <th>Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($v = $videos->fetch_assoc()):
                    $vid = getVideoID($v['youtube_link']);
                    $thumb = $vid ? "https://img.youtube.com/vi/{$vid}/mqdefault.jpg" : null;
                ?>
                <tr>
                    <td style="color:var(--muted);font-size:.78rem;"><?php echo $v['video_id']; ?></td>
                    <td>
                        <div style="width:80px;height:46px;border-radius:8px;overflow:hidden;background:#111;flex-shrink:0;">
                            <?php if ($thumb): ?>
                            <img src="<?php echo $thumb; ?>" alt=""
                                 style="width:100%;height:100%;object-fit:cover;display:block;">
                            <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-film" style="color:var(--muted2);"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:.87rem;"><?php echo htmlspecialchars($v['title']); ?></div>
                        <?php if ($vid): ?>
                        <div style="font-size:.72rem;color:var(--muted);margin-top:2px;">
                            <i class="fab fa-youtube" style="color:#ff4444;"></i>
                            youtu.be/<?php echo htmlspecialchars($vid); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="adm-badge gold"><?php echo htmlspecialchars($v['course_title']); ?></span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <a href="edit_video.php?id=<?php echo $v['video_id']; ?>"
                               class="btn-icon btn-icon-edit" title="Edit Video">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete_video.php?id=<?php echo $v['video_id']; ?>"
                               class="btn-icon btn-icon-danger" title="Delete Video"
                               onclick="return confirm('Delete \'<?php echo addslashes($v['title']); ?>\'?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="adm-empty">
            <i class="fas fa-video-slash"></i>
            <p>No videos found<?php echo $filter_course ? ' for this course.' : '.'; ?></p>
            <a href="add_video.php" class="btn-adm btn-adm-primary" style="margin-top:14px;">
                <i class="fas fa-plus"></i> Add First Video
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '_layout_end.php'; ?>