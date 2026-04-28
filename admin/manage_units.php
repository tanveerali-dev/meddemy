<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$filter_course  = (int)($_GET['course_id']  ?? 0);
$filter_subject = (int)($_GET['subject_id'] ?? 0);
$courses  = $conn->query("SELECT course_id, title FROM course ORDER BY title ASC");
$msg = '';

/* ── DELETE UNIT ── */
if (isset($_GET['delete_unit'])) {
    $uid = (int)$_GET['delete_unit'];
    $conn->query("UPDATE video SET unit_id=NULL WHERE unit_id=$uid");
    $conn->query("DELETE FROM unit WHERE unit_id=$uid");
    $msg = '<div class="adm-alert adm-alert-warn"><i class="fas fa-minus-circle"></i> Unit deleted. Its videos are now unassigned.</div>';
}

/* ── ADD UNIT ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_unit'])) {
    $sid   = (int)$_POST['subject_id'];
    $name  = htmlspecialchars(trim($_POST['name']));
    $order = (int)($_POST['sort_order'] ?? 0);
    if ($sid && $name) {
        $ins = $conn->prepare("INSERT INTO unit (subject_id, name, sort_order) VALUES (?,?,?)");
        $ins->bind_param("isi", $sid, $name, $order);
        $ins->execute();
        $filter_subject = $sid;
        // get course_id for this subject
        $sc = $conn->prepare("SELECT course_id FROM subject WHERE subject_id=?");
        $sc->bind_param("i",$sid); $sc->execute();
        $filter_course = (int)($sc->get_result()->fetch_assoc()['course_id'] ?? 0);
        $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Unit added!</div>';
    }
}

// Load subjects for selected course
$subjects = [];
if ($filter_course) {
    $res = $conn->query("SELECT * FROM subject WHERE course_id=$filter_course ORDER BY sort_order ASC, subject_id ASC");
    while ($r=$res->fetch_assoc()) $subjects[] = $r;
}

// Load units for selected subject with video count
$units = [];
if ($filter_subject) {
    $res = $conn->query("SELECT u.*,
        (SELECT COUNT(*) FROM video v WHERE v.unit_id=u.unit_id) as video_count
        FROM unit u WHERE u.subject_id=$filter_subject
        ORDER BY u.sort_order ASC, u.unit_id ASC");
    while ($r=$res->fetch_assoc()) $units[] = $r;
}

// Get subject name
$subj_name = '';
if ($filter_subject) {
    $sn = $conn->query("SELECT name, course_id FROM subject WHERE subject_id=$filter_subject")->fetch_assoc();
    $subj_name    = $sn['name'] ?? '';
    if (!$filter_course) $filter_course = (int)($sn['course_id'] ?? 0);
}

$page_title = "Manage Units";
$active_nav = "manage_units";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Manage Units</h1>
        <p>Add units inside subjects — each unit groups related lectures together</p>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start;">

    <!-- Left: selectors + units list -->
    <div>
        <!-- Step 1: Course -->
        <div class="adm-card" style="margin-bottom:14px;">
            <div class="adm-card-body">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div style="font-size:.78rem;font-weight:700;color:var(--muted);white-space:nowrap;">
                        <i class="fas fa-book-open" style="color:var(--gold);margin-right:5px;"></i>Course:
                    </div>
                    <form method="GET" style="flex:1;">
                        <input type="hidden" name="subject_id" value="">
                        <select name="course_id" class="form-select" onchange="this.form.submit()">
                            <option value="0">— Select Course —</option>
                            <?php $courses->data_seek(0); while($c=$courses->fetch_assoc()): ?>
                            <option value="<?php echo $c['course_id']; ?>" <?php echo $filter_course==$c['course_id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($c['title']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Step 2: Subject (shown only if course selected) -->
        <?php if ($filter_course && !empty($subjects)): ?>
        <div class="adm-card" style="margin-bottom:14px;">
            <div class="adm-card-body">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div style="font-size:.78rem;font-weight:700;color:var(--muted);white-space:nowrap;">
                        <i class="fas fa-layer-group" style="color:var(--gold);margin-right:5px;"></i>Subject:
                    </div>
                    <form method="GET" style="flex:1;">
                        <input type="hidden" name="course_id" value="<?php echo $filter_course; ?>">
                        <select name="subject_id" class="form-select" onchange="this.form.submit()">
                            <option value="0">— Select Subject —</option>
                            <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['subject_id']; ?>" <?php echo $filter_subject==$s['subject_id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
        <?php elseif ($filter_course && empty($subjects)): ?>
        <div class="adm-alert adm-alert-warn">
            <i class="fas fa-exclamation-triangle"></i>
            No subjects in this course yet. <a href="manage_subjects.php?course_id=<?php echo $filter_course; ?>">Add subjects first →</a>
        </div>
        <?php endif; ?>

        <!-- Units list -->
        <?php if ($filter_subject): ?>
        <div class="adm-card">
            <div class="adm-card-header">
                <h2>
                    <i class="fas fa-list-ol" style="color:var(--gold);margin-right:8px;font-size:.82rem;"></i>
                    Units in "<?php echo htmlspecialchars($subj_name); ?>"
                </h2>
                <span class="adm-badge gold"><?php echo count($units); ?> units</span>
            </div>
            <div class="adm-card-body">
                <?php if (!empty($units)): ?>
                <div style="display:flex;flex-direction:column;gap:9px;">
                    <?php foreach ($units as $u): ?>
                    <div style="display:flex;align-items:center;gap:13px;background:var(--s3);border:1px solid var(--border3);border-radius:12px;padding:13px 15px;transition:.2s;"
                         onmouseover="this.style.borderColor='rgba(255,215,0,.22)'"
                         onmouseout="this.style.borderColor='var(--border3)'">
                        <!-- Order badge -->
                        <div style="width:30px;height:30px;border-radius:8px;background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.15);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:var(--gold);flex-shrink:0;">
                            <?php echo $u['sort_order'] ?: $loop_i ?? '—'; ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:.88rem;"><?php echo htmlspecialchars($u['name']); ?></div>
                        </div>
                        <span class="adm-badge blue">
                            <i class="fas fa-video"></i> <?php echo $u['video_count']; ?> lectures
                        </span>
                        <a href="assign_unit_videos.php?unit_id=<?php echo $u['unit_id']; ?>&subject_id=<?php echo $filter_subject; ?>&course_id=<?php echo $filter_course; ?>"
                           class="btn-adm btn-adm-ghost btn-adm-sm">
                            <i class="fas fa-video"></i> Assign Lectures
                        </a>
                        <a href="?delete_unit=<?php echo $u['unit_id']; ?>&subject_id=<?php echo $filter_subject; ?>&course_id=<?php echo $filter_course; ?>"
                           class="btn-icon btn-icon-danger"
                           onclick="return confirm('Delete unit \'<?php echo addslashes($u['name']); ?>\'?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="adm-empty">
                    <i class="fas fa-list-ol"></i>
                    <p>No units yet. Add your first unit →</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Add unit form -->
    <div style="position:sticky;top:74px;">
        <div class="adm-card">
            <div class="adm-card-header">
                <h2><i class="fas fa-plus-circle" style="color:var(--gold);margin-right:7px;font-size:.85rem;"></i>Add Unit</h2>
            </div>
            <div class="adm-card-body" style="padding-top:20px;">
                <form method="POST" class="adm-form">
                    <input type="hidden" name="add_unit" value="1">
                    <input type="hidden" name="course_id_hidden" value="<?php echo $filter_course; ?>">

                    <div class="form-group">
                        <label class="form-label">Subject <span>*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">— Select Subject —</option>
                            <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['subject_id']; ?>" <?php echo $filter_subject==$s['subject_id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($subjects) && $filter_course): ?>
                        <div style="font-size:.72rem;color:var(--red);margin-top:4px;">
                            No subjects found. <a href="manage_subjects.php?course_id=<?php echo $filter_course; ?>" style="color:var(--gold);">Add subjects first →</a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Unit Name <span>*</span></label>
                        <input type="text" name="name" class="form-input"
                               placeholder="e.g. Unit 1 — Cell Biology" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="sort_order" class="form-input"
                               placeholder="1, 2, 3..." min="0" value="0">
                        <div style="font-size:.71rem;color:var(--muted);margin-top:4px;">
                            <i class="fas fa-info-circle" style="margin-right:3px;color:var(--gold);"></i>
                            Lower number appears first
                        </div>
                    </div>

                    <button type="submit" class="btn-adm btn-adm-primary" style="width:100%;justify-content:center;"
                            <?php echo empty($subjects)?'disabled':''; ?>>
                        <i class="fas fa-plus"></i> Add Unit
                    </button>
                </form>

                <!-- Breadcrumb hint -->
                <?php if ($filter_course || $filter_subject): ?>
                <div style="margin-top:18px;padding:12px;background:var(--s3);border-radius:10px;font-size:.74rem;color:var(--muted);line-height:1.7;">
                    <div style="font-weight:700;color:var(--muted2);margin-bottom:4px;font-size:.65rem;letter-spacing:1px;text-transform:uppercase;">Structure</div>
                    Course → Subject → <strong style="color:var(--gold);">Unit</strong> → Lectures
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '_layout_end.php'; ?>