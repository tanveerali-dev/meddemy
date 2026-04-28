<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$filter_course = (int)($_GET['course_id'] ?? 0);
$courses = $conn->query("SELECT course_id, title FROM course ORDER BY title ASC");
$msg = '';

/* ── ADD ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $cid  = (int)$_POST['course_id'];
    $name = htmlspecialchars(trim($_POST['name']));
    $desc = htmlspecialchars(trim($_POST['description']));
    $ord  = (int)($_POST['sort_order'] ?? 0);
    if ($cid && $name) {
        $ins = $conn->prepare("INSERT INTO subject (course_id,name,description,sort_order) VALUES (?,?,?,?)");
        $ins->bind_param("issi",$cid,$name,$desc,$ord);
        $ins->execute();
        $filter_course = $cid;
        $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Subject added successfully!</div>';
    }
}

/* ── DELETE ── */
if (isset($_GET['delete'])) {
    $sid = (int)$_GET['delete'];
    // Unlink videos from this subject
    $conn->query("UPDATE video SET subject_id=NULL WHERE subject_id=$sid");
    $conn->query("DELETE FROM subject WHERE subject_id=$sid");
    $msg = '<div class="adm-alert adm-alert-warn"><i class="fas fa-minus-circle"></i> Subject deleted. Its videos are now unassigned.</div>';
}

// Fetch subjects for selected course
$subjects = [];
if ($filter_course) {
    $res = $conn->query("SELECT s.*,
        (SELECT COUNT(*) FROM video v WHERE v.subject_id=s.subject_id) as video_count
        FROM subject s WHERE s.course_id=$filter_course ORDER BY s.sort_order ASC, s.subject_id ASC");
    while ($r = $res->fetch_assoc()) $subjects[] = $r;
}

$page_title = "Manage Subjects";
$active_nav = "manage_subjects";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Manage Subjects</h1>
        <p>Organise course content into subjects — each with its own lectures and quizzes</p>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start;">

    <!-- Left: subjects list -->
    <div>
        <!-- Course selector -->
        <div class="adm-card" style="margin-bottom:18px;">
            <div class="adm-card-body">
                <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <label class="form-label" style="margin:0;white-space:nowrap;">Select Course:</label>
                    <select name="course_id" class="form-select" style="flex:1;max-width:320px;" onchange="this.form.submit()">
                        <option value="0">— Choose a course —</option>
                        <?php $courses->data_seek(0); while($c=$courses->fetch_assoc()): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo $filter_course==$c['course_id']?'selected':''; ?>>
                            <?php echo htmlspecialchars($c['title']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
        </div>

        <?php if ($filter_course): ?>
        <div class="adm-card">
            <div class="adm-card-header">
                <h2><i class="fas fa-layer-group" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>Subjects</h2>
                <span class="adm-badge gold"><?php echo count($subjects); ?> subjects</span>
            </div>
            <div class="adm-card-body">
                <?php if (count($subjects) > 0): ?>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <?php foreach($subjects as $s): ?>
                    <div style="display:flex;align-items:center;gap:14px;background:var(--s3);border:1px solid var(--border3);border-radius:13px;padding:14px 16px;transition:.2s;"
                         onmouseover="this.style.borderColor='rgba(255,215,0,.22)'"
                         onmouseout="this.style.borderColor='var(--border3)'">
                        <!-- Drag handle / order indicator -->
                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.15);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:var(--gold);flex-shrink:0;">
                            <?php echo $s['sort_order'] ?: '#'; ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:.9rem;"><?php echo htmlspecialchars($s['name']); ?></div>
                            <?php if ($s['description']): ?>
                            <div style="font-size:.76rem;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($s['description']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <span class="adm-badge blue">
                            <i class="fas fa-video"></i> <?php echo $s['video_count']; ?> lectures
                        </span>
                        <div style="display:flex;gap:6px;">
                            <a href="assign_videos.php?subject_id=<?php echo $s['subject_id']; ?>&course_id=<?php echo $filter_course; ?>"
                               class="btn-adm btn-adm-ghost btn-adm-sm" title="Assign Videos">
                                <i class="fas fa-video"></i> Assign Lectures
                            </a>
                            <a href="?delete=<?php echo $s['subject_id']; ?>&course_id=<?php echo $filter_course; ?>"
                               class="btn-icon btn-icon-danger"
                               onclick="return confirm('Delete \'<?php echo addslashes($s['name']); ?>\'? Its videos will become unassigned.')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="adm-empty">
                    <i class="fas fa-layer-group"></i>
                    <p>No subjects yet for this course. Add your first subject →</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="adm-card">
            <div class="adm-card-body">
                <div class="adm-empty" style="border:none;padding:30px 0;">
                    <i class="fas fa-hand-point-right" style="font-size:1.8rem;"></i>
                    <p>Select a course above to see and add its subjects.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: add subject form -->
    <div style="position:sticky;top:74px;">
        <div class="adm-card">
            <div class="adm-card-header">
                <h2><i class="fas fa-plus-circle" style="color:var(--gold);margin-right:7px;font-size:.85rem;"></i>Add Subject</h2>
            </div>
            <div class="adm-card-body" style="padding-top:20px;">
                <form method="POST" class="adm-form">
                    <input type="hidden" name="add_subject" value="1">

                    <div class="form-group">
                        <label class="form-label">Course <span>*</span></label>
                        <select name="course_id" class="form-select" required>
                            <option value="">— Select Course —</option>
                            <?php $courses->data_seek(0); while($c=$courses->fetch_assoc()): ?>
                            <option value="<?php echo $c['course_id']; ?>" <?php echo $filter_course==$c['course_id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($c['title']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Subject Name <span>*</span></label>
                        <input type="text" name="name" class="form-input"
                               placeholder="e.g. Biology, Chemistry, Physics..."
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                        <textarea name="description" class="form-textarea" rows="2"
                                  placeholder="Brief description of this subject..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="sort_order" class="form-input"
                               placeholder="1, 2, 3... (lower = first)"
                               min="0" value="0">
                        <div style="font-size:.72rem;color:var(--muted);margin-top:4px;">
                            <i class="fas fa-info-circle" style="margin-right:3px;"></i>
                            Lower number shows first in playlist
                        </div>
                    </div>

                    <button type="submit" class="btn-adm btn-adm-primary" style="width:100%;justify-content:center;">
                        <i class="fas fa-plus"></i> Add Subject
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '_layout_end.php'; ?>