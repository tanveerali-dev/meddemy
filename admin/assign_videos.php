<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$subject_id = (int)($_GET['subject_id'] ?? 0);
$course_id  = (int)($_GET['course_id']  ?? 0);
if (!$subject_id || !$course_id) { header("Location: manage_subjects.php"); exit(); }

// Fetch subject
$ss = $conn->prepare("SELECT s.*, c.title as ctitle FROM subject s JOIN course c ON c.course_id=s.course_id WHERE s.subject_id=?");
$ss->bind_param("i",$subject_id); $ss->execute();
$subject = $ss->get_result()->fetch_assoc();
if (!$subject) { header("Location: manage_subjects.php"); exit(); }

$msg = '';

/* ── SAVE ASSIGNMENTS ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Unassign all videos from this subject first
    $conn->query("UPDATE video SET subject_id=NULL WHERE subject_id=$subject_id AND course_id=$course_id");
    // Assign checked ones
    $assigned = $_POST['videos'] ?? [];
    if (!empty($assigned)) {
        foreach ($assigned as $vid) {
            $vid = (int)$vid;
            $upd = $conn->prepare("UPDATE video SET subject_id=? WHERE video_id=? AND course_id=?");
            $upd->bind_param("iii",$subject_id,$vid,$course_id);
            $upd->execute();
        }
    }
    $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Lectures assigned successfully!</div>';
}

// All videos for this course with their current subject
$videos = $conn->query("SELECT v.video_id, v.title, v.subject_id,
    s.name as subject_name
    FROM video v
    LEFT JOIN subject s ON s.subject_id = v.subject_id
    WHERE v.course_id=$course_id
    ORDER BY v.video_id ASC")->fetch_all(MYSQLI_ASSOC);

// Other subjects of this course (for reference)
$other_subjects = $conn->query("SELECT * FROM subject WHERE course_id=$course_id AND subject_id!=$subject_id ORDER BY sort_order ASC");

$page_title = "Assign Lectures";
$active_nav = "manage_subjects";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Assign Lectures</h1>
        <p>
            <i class="fas fa-layer-group" style="color:var(--gold);margin-right:5px;font-size:.8rem;"></i>
            <strong><?php echo htmlspecialchars($subject['name']); ?></strong>
            <span style="color:var(--muted2);margin:0 6px;">·</span>
            <span style="color:var(--muted);"><?php echo htmlspecialchars($subject['ctitle']); ?></span>
        </p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_subjects.php?course_id=<?php echo $course_id; ?>" class="btn-adm btn-adm-ghost">
            <i class="fas fa-arrow-left"></i> Back to Subjects
        </a>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 260px;gap:22px;align-items:start;">

    <!-- Video assignment form -->
    <div class="adm-card">
        <div class="adm-card-header">
            <h2><i class="fas fa-video" style="color:var(--gold);margin-right:7px;font-size:.85rem;"></i>
                Select Lectures for "<?php echo htmlspecialchars($subject['name']); ?>"
            </h2>
            <span class="adm-badge blue"><?php echo count($videos); ?> total lectures</span>
        </div>
        <div class="adm-card-body">
            <?php if (count($videos) > 0): ?>
            <form method="POST">
                <!-- Select all toggle -->
                <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--s3);border-radius:10px;margin-bottom:14px;cursor:pointer;"
                     onclick="toggleAll()">
                    <input type="checkbox" id="selectAll" style="accent-color:var(--gold);width:16px;height:16px;cursor:pointer;">
                    <label for="selectAll" style="font-size:.82rem;font-weight:600;cursor:pointer;">Select / Deselect All</label>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:20px;">
                <?php foreach($videos as $v):
                    $isThisSubject = $v['subject_id'] == $subject_id;
                    $otherSubject  = (!$isThisSubject && $v['subject_id']) ? $v['subject_name'] : null;
                ?>
                <label style="display:flex;align-items:center;gap:12px;padding:13px 14px;
                    background:<?php echo $isThisSubject?'rgba(255,215,0,.06)':'var(--s2)'; ?>;
                    border:1.5px solid <?php echo $isThisSubject?'rgba(255,215,0,.3)':'var(--border2)'; ?>;
                    border-radius:12px;cursor:pointer;transition:.2s;"
                     onmouseover="this.style.borderColor='rgba(255,215,0,.3)'"
                     onmouseout="this.style.borderColor='<?php echo $isThisSubject?'rgba(255,215,0,.3)':'var(--border2)'; ?>'">
                    <input type="checkbox" name="videos[]" value="<?php echo $v['video_id']; ?>"
                           <?php echo $isThisSubject?'checked':''; ?>
                           style="accent-color:var(--gold);width:16px;height:16px;flex-shrink:0;"
                           class="vid-check">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.87rem;font-weight:600;"><?php echo htmlspecialchars($v['title']); ?></div>
                        <?php if ($otherSubject): ?>
                        <div style="font-size:.72rem;margin-top:3px;">
                            <span style="color:var(--muted);">Currently in:</span>
                            <span style="color:var(--gold);font-weight:600;"><?php echo htmlspecialchars($otherSubject); ?></span>
                        </div>
                        <?php elseif (!$v['subject_id']): ?>
                        <div style="font-size:.72rem;color:var(--muted2);margin-top:2px;">Not assigned to any subject</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($isThisSubject): ?>
                    <span class="adm-badge gold" style="flex-shrink:0;"><i class="fas fa-check"></i> Assigned</span>
                    <?php elseif ($otherSubject): ?>
                    <span class="adm-badge" style="background:var(--s3);color:var(--muted2);border-color:var(--border3);flex-shrink:0;font-size:.65rem;">Other subject</span>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
                </div>

                <button type="submit" class="btn-adm btn-adm-primary">
                    <i class="fas fa-save"></i> Save Assignments
                </button>
            </form>
            <?php else: ?>
            <div class="adm-empty">
                <i class="fas fa-video-slash"></i>
                <p>No lectures in this course yet. Add videos first.</p>
                <a href="add_video.php?course_id=<?php echo $course_id; ?>" class="btn-adm btn-adm-primary" style="margin-top:14px;">
                    <i class="fas fa-plus"></i> Add Lecture
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info panel -->
    <div style="display:flex;flex-direction:column;gap:14px;">
        <!-- Subject info -->
        <div class="adm-card">
            <div class="adm-card-header"><h2>Subject Info</h2></div>
            <div class="adm-card-body">
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div style="padding:10px 12px;background:var(--s3);border-radius:10px;">
                        <div style="font-size:.68rem;color:var(--muted2);margin-bottom:3px;">SUBJECT</div>
                        <div style="font-weight:700;"><?php echo htmlspecialchars($subject['name']); ?></div>
                    </div>
                    <div style="padding:10px 12px;background:var(--s3);border-radius:10px;">
                        <div style="font-size:.68rem;color:var(--muted2);margin-bottom:3px;">COURSE</div>
                        <div style="font-weight:600;font-size:.88rem;"><?php echo htmlspecialchars($subject['ctitle']); ?></div>
                    </div>
                    <?php if ($subject['description']): ?>
                    <div style="padding:10px 12px;background:var(--s3);border-radius:10px;">
                        <div style="font-size:.68rem;color:var(--muted2);margin-bottom:3px;">DESCRIPTION</div>
                        <div style="font-size:.82rem;color:var(--muted);"><?php echo htmlspecialchars($subject['description']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Other subjects -->
        <?php if ($other_subjects->num_rows > 0): ?>
        <div class="adm-card">
            <div class="adm-card-header"><h2>Other Subjects</h2></div>
            <div class="adm-card-body">
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <?php while($os=$other_subjects->fetch_assoc()): ?>
                    <a href="assign_videos.php?subject_id=<?php echo $os['subject_id']; ?>&course_id=<?php echo $course_id; ?>"
                       style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 12px;background:var(--s3);border:1px solid var(--border3);border-radius:10px;text-decoration:none;color:var(--text);font-size:.82rem;font-weight:600;transition:.2s;"
                       onmouseover="this.style.borderColor='rgba(255,215,0,.2)'"
                       onmouseout="this.style.borderColor='var(--border3)'">
                        <?php echo htmlspecialchars($os['name']); ?>
                        <i class="fas fa-arrow-right" style="color:var(--muted2);font-size:.7rem;"></i>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
function toggleAll() {
    const master = document.getElementById('selectAll');
    const checks = document.querySelectorAll('.vid-check');
    master.checked = !master.checked;
    checks.forEach(c => c.checked = master.checked);
}
</script>

<?php include '_layout_end.php'; ?>