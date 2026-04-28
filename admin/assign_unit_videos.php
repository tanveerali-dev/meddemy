<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$unit_id    = (int)($_GET['unit_id']    ?? 0);
$subject_id = (int)($_GET['subject_id'] ?? 0);
$course_id  = (int)($_GET['course_id']  ?? 0);
if (!$unit_id || !$course_id) { header("Location: manage_units.php"); exit(); }

// Fetch unit + subject
$us = $conn->prepare("SELECT u.*, s.name as sname, s.course_id FROM unit u JOIN subject s ON s.subject_id=u.subject_id WHERE u.unit_id=?");
$us->bind_param("i",$unit_id); $us->execute();
$unit = $us->get_result()->fetch_assoc();
if (!$unit) { header("Location: manage_units.php"); exit(); }
if (!$subject_id) $subject_id = $unit['subject_id'];
if (!$course_id)  $course_id  = $unit['course_id'];

$msg = '';

/* ── SAVE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Unassign current videos from this unit
    $conn->query("UPDATE video SET unit_id=NULL WHERE unit_id=$unit_id AND course_id=$course_id");
    $assigned = $_POST['videos'] ?? [];
    foreach ($assigned as $vid) {
        $vid = (int)$vid;
        $upd = $conn->prepare("UPDATE video SET unit_id=?, subject_id=? WHERE video_id=? AND course_id=?");
        $upd->bind_param("iiii",$unit_id,$subject_id,$vid,$course_id);
        $upd->execute();
    }
    $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Lectures assigned to unit successfully!</div>';
}

// All videos for this course — show with their current unit/subject
$videos = $conn->query("
    SELECT v.video_id, v.title, v.unit_id, v.subject_id,
           u.name as unit_name, s.name as subject_name
    FROM video v
    LEFT JOIN unit u ON u.unit_id = v.unit_id
    LEFT JOIN subject s ON s.subject_id = v.subject_id
    WHERE v.course_id=$course_id
    ORDER BY v.subject_id ASC, v.unit_id ASC, v.video_id ASC
")->fetch_all(MYSQLI_ASSOC);

// Other units in same subject
$other_units = $conn->query("SELECT * FROM unit WHERE subject_id=$subject_id AND unit_id!=$unit_id ORDER BY sort_order ASC");

$page_title = "Assign Lectures to Unit";
$active_nav = "manage_units";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Assign Lectures</h1>
        <p>
            <i class="fas fa-layer-group" style="color:var(--gold);margin-right:5px;font-size:.8rem;"></i>
            <?php echo htmlspecialchars($unit['sname']); ?>
            <span style="color:var(--muted2);margin:0 5px;">›</span>
            <strong style="color:var(--gold);"><?php echo htmlspecialchars($unit['name']); ?></strong>
        </p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_units.php?course_id=<?php echo $course_id; ?>&subject_id=<?php echo $subject_id; ?>"
           class="btn-adm btn-adm-ghost">
            <i class="fas fa-arrow-left"></i> Back to Units
        </a>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 250px;gap:22px;align-items:start;">

    <div class="adm-card">
        <div class="adm-card-header">
            <h2><i class="fas fa-video" style="color:var(--gold);margin-right:7px;font-size:.85rem;"></i>
                Select Lectures for "<?php echo htmlspecialchars($unit['name']); ?>"
            </h2>
            <span class="adm-badge blue"><?php echo count($videos); ?> lectures</span>
        </div>
        <div class="adm-card-body">
            <?php if (!empty($videos)): ?>
            <form method="POST">
                <!-- Select all -->
                <div style="display:flex;align-items:center;gap:10px;padding:9px 13px;background:var(--s3);border-radius:10px;margin-bottom:12px;cursor:pointer;" onclick="toggleAll()">
                    <input type="checkbox" id="selectAll" style="accent-color:var(--gold);width:16px;height:16px;">
                    <label for="selectAll" style="font-size:.82rem;font-weight:600;cursor:pointer;">Select / Deselect All</label>
                </div>

                <div style="display:flex;flex-direction:column;gap:7px;margin-bottom:18px;">
                <?php foreach ($videos as $v):
                    $isThis   = $v['unit_id'] == $unit_id;
                    $otherUnit = (!$isThis && $v['unit_id']) ? $v['unit_name'] : null;
                    $otherSubj = ($v['subject_id'] && $v['subject_id'] != $subject_id) ? $v['subject_name'] : null;
                ?>
                <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;
                    background:<?php echo $isThis?'rgba(255,215,0,.06)':'var(--s2)'; ?>;
                    border:1.5px solid <?php echo $isThis?'rgba(255,215,0,.3)':'var(--border2)'; ?>;
                    border-radius:12px;cursor:pointer;transition:.18s;"
                    onmouseover="this.style.borderColor='rgba(255,215,0,.3)'"
                    onmouseout="this.style.borderColor='<?php echo $isThis?'rgba(255,215,0,.3)':'var(--border2)'; ?>'">
                    <input type="checkbox" name="videos[]" value="<?php echo $v['video_id']; ?>"
                           <?php echo $isThis?'checked':''; ?>
                           style="accent-color:var(--gold);width:16px;height:16px;flex-shrink:0;"
                           class="vid-check">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.86rem;font-weight:600;"><?php echo htmlspecialchars($v['title']); ?></div>
                        <?php if ($otherUnit): ?>
                        <div style="font-size:.71rem;margin-top:2px;color:var(--muted);">
                            Currently in: <strong style="color:var(--gold);"><?php echo htmlspecialchars($otherUnit); ?></strong>
                        </div>
                        <?php elseif ($otherSubj): ?>
                        <div style="font-size:.71rem;margin-top:2px;color:var(--muted);">
                            Subject: <strong style="color:var(--blue);"><?php echo htmlspecialchars($otherSubj); ?></strong>
                        </div>
                        <?php elseif (!$v['unit_id']): ?>
                        <div style="font-size:.71rem;color:var(--muted2);margin-top:2px;">Not assigned to any unit</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($isThis): ?>
                    <span class="adm-badge gold"><i class="fas fa-check"></i> This Unit</span>
                    <?php elseif ($otherUnit): ?>
                    <span class="adm-badge" style="background:var(--s3);color:var(--muted2);border-color:var(--border3);font-size:.65rem;">Other unit</span>
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
                <p>No lectures in this course. <a href="add_video.php?course_id=<?php echo $course_id; ?>" style="color:var(--gold);">Add videos first →</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right info panel -->
    <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="adm-card">
            <div class="adm-card-header"><h2>Unit Info</h2></div>
            <div class="adm-card-body">
                <div style="display:flex;flex-direction:column;gap:8px;font-size:.82rem;">
                    <div style="padding:9px 11px;background:var(--s3);border-radius:9px;">
                        <div style="font-size:.65rem;color:var(--muted2);margin-bottom:2px;text-transform:uppercase;letter-spacing:.8px;">Unit</div>
                        <div style="font-weight:700;color:var(--gold);"><?php echo htmlspecialchars($unit['name']); ?></div>
                    </div>
                    <div style="padding:9px 11px;background:var(--s3);border-radius:9px;">
                        <div style="font-size:.65rem;color:var(--muted2);margin-bottom:2px;text-transform:uppercase;letter-spacing:.8px;">Subject</div>
                        <div style="font-weight:600;"><?php echo htmlspecialchars($unit['sname']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($other_units->num_rows > 0): ?>
        <div class="adm-card">
            <div class="adm-card-header"><h2>Other Units</h2></div>
            <div class="adm-card-body">
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <?php while($ou=$other_units->fetch_assoc()): ?>
                    <a href="assign_unit_videos.php?unit_id=<?php echo $ou['unit_id']; ?>&subject_id=<?php echo $subject_id; ?>&course_id=<?php echo $course_id; ?>"
                       style="display:flex;align-items:center;justify-content:space-between;padding:8px 11px;background:var(--s3);border:1px solid var(--border3);border-radius:9px;text-decoration:none;color:var(--text);font-size:.81rem;font-weight:600;transition:.18s;"
                       onmouseover="this.style.borderColor='rgba(255,215,0,.2)'"
                       onmouseout="this.style.borderColor='var(--border3)'">
                        <?php echo htmlspecialchars($ou['name']); ?>
                        <i class="fas fa-arrow-right" style="color:var(--muted2);font-size:.68rem;"></i>
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
    master.checked = !master.checked;
    document.querySelectorAll('.vid-check').forEach(c => c.checked = master.checked);
}
</script>

<?php include '_layout_end.php'; ?>