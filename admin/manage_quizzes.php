<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$filter_course = (int)($_GET['course_id'] ?? 0);
$courses = $conn->query("SELECT course_id, title FROM course ORDER BY title ASC");

$msg = '';
if (isset($_GET['deleted'])) $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Quiz deleted.</div>';

$subjects_data = [];

if ($filter_course) {
    // Subjects in this course
    $subj_res = $conn->query("SELECT * FROM subject WHERE course_id=$filter_course ORDER BY sort_order ASC, subject_id ASC");
    while ($s = $subj_res->fetch_assoc()) {
        $sid  = $s['subject_id'];
        $vids = $conn->query("
            SELECT v.video_id, v.title as vtitle,
                   q.quiz_id,
                   (SELECT COUNT(*) FROM question WHERE quiz_id=q.quiz_id) as q_count
            FROM video v
            LEFT JOIN quiz q ON q.video_id = v.video_id
            WHERE v.course_id=$filter_course AND v.subject_id=$sid
            ORDER BY v.video_id ASC
        ")->fetch_all(MYSQLI_ASSOC);
        $subjects_data[] = ['subject' => $s, 'videos' => $vids];
    }
    // Unassigned videos
    $unassigned = $conn->query("
        SELECT v.video_id, v.title as vtitle,
               q.quiz_id,
               (SELECT COUNT(*) FROM question WHERE quiz_id=q.quiz_id) as q_count
        FROM video v
        LEFT JOIN quiz q ON q.video_id = v.video_id
        WHERE v.course_id=$filter_course AND v.subject_id IS NULL
        ORDER BY v.video_id ASC
    ")->fetch_all(MYSQLI_ASSOC);
    if (!empty($unassigned)) {
        $subjects_data[] = ['subject' => ['subject_id'=>0,'name'=>'General / Unassigned'], 'videos' => $unassigned];
    }
} else {
    // All courses flat — group by course > subject
    $all = $conn->query("
        SELECT v.video_id, v.title as vtitle, c.title as ctitle, c.course_id,
               q.quiz_id,
               (SELECT COUNT(*) FROM question WHERE quiz_id=q.quiz_id) as q_count,
               s.name as subject_name, s.subject_id
        FROM video v
        JOIN course c ON c.course_id = v.course_id
        LEFT JOIN quiz q ON q.video_id = v.video_id
        LEFT JOIN subject s ON s.subject_id = v.subject_id
        ORDER BY c.title ASC, ISNULL(v.subject_id) ASC, s.sort_order ASC, v.video_id ASC
    ");
    $by_course = [];
    while ($r = $all->fetch_assoc()) {
        $ckey = $r['course_id'];
        $skey = $r['subject_name'] ?? '__general__';
        if (!isset($by_course[$ckey])) $by_course[$ckey] = ['ctitle'=>$r['ctitle'],'subjects'=>[]];
        $by_course[$ckey]['subjects'][$skey][] = $r;
    }
}

$page_title = "Manage Quizzes";
$active_nav = "manage_quizzes";
include '_layout.php';
?>

<style>
.qs-block{background:var(--s2);border:1px solid var(--border2);border-radius:16px;overflow:hidden;margin-bottom:14px;}
.qs-header{display:flex;align-items:center;gap:14px;padding:15px 18px;cursor:pointer;transition:background .2s;user-select:none;}
.qs-header:hover{background:rgba(255,215,0,.04);}
.qs-icon{width:40px;height:40px;border-radius:11px;background:rgba(255,215,0,.1);border:1px solid rgba(255,215,0,.18);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:.95rem;flex-shrink:0;}
.qs-title{flex:1;font-family:'Playfair Display',serif;font-size:.97rem;font-weight:700;}
.qs-badges{display:flex;gap:7px;align-items:center;flex-shrink:0;flex-wrap:wrap;}
.qs-chevron{color:var(--muted2);font-size:.75rem;transition:transform .25s ease;flex-shrink:0;margin-left:4px;}
.qs-body{border-top:1px solid var(--border2);}
.lect-row{display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s;}
.lect-row:last-child{border-bottom:none;}
.lect-row:hover{background:rgba(255,255,255,.03);}
.lect-num{width:26px;height:26px;border-radius:7px;background:var(--s4);display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;color:var(--muted);flex-shrink:0;}
.lect-title{flex:1;font-size:.86rem;font-weight:600;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.lect-actions{display:flex;gap:6px;align-items:center;flex-shrink:0;}
.quiz-stats{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.qstat{background:var(--s2);border:1px solid var(--border2);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px;}
.qstat-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.88rem;flex-shrink:0;}
.qstat-val{font-size:1.1rem;font-weight:800;}
.qstat-lbl{font-size:.72rem;color:var(--muted);}
</style>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Manage Quizzes</h1>
        <p>Add or edit MCQ quizzes — organised by subject</p>
    </div>
</div>

<?php echo $msg; ?>

<!-- Course filter -->
<div style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <select name="course_id" class="form-select" style="max-width:320px;" onchange="this.form.submit()">
            <option value="0">— All Courses —</option>
            <?php $courses->data_seek(0); while($c=$courses->fetch_assoc()): ?>
            <option value="<?php echo $c['course_id']; ?>" <?php echo $filter_course==$c['course_id']?'selected':''; ?>>
                <?php echo htmlspecialchars($c['title']); ?>
            </option>
            <?php endwhile; ?>
        </select>
        <?php if ($filter_course): ?>
        <a href="manage_quizzes.php" class="btn-adm btn-adm-ghost btn-adm-sm">
            <i class="fas fa-times"></i> All Courses
        </a>
        <?php endif; ?>
    </form>
</div>

<?php if ($filter_course): ?>
<?php
// Stats
$total_lects = 0; $quizzed = 0; $no_quiz = 0;
foreach ($subjects_data as $sg) {
    foreach ($sg['videos'] as $v) {
        $total_lects++;
        $v['quiz_id'] ? $quizzed++ : $no_quiz++;
    }
}
?>

<!-- Stats bar -->
<div class="quiz-stats">
    <div class="qstat">
        <div class="qstat-icon" style="background:rgba(255,215,0,.1);color:var(--gold);"><i class="fas fa-layer-group"></i></div>
        <div><div class="qstat-val"><?php echo count($subjects_data); ?></div><div class="qstat-lbl">Subjects</div></div>
    </div>
    <div class="qstat">
        <div class="qstat-icon" style="background:rgba(59,130,246,.1);color:var(--blue);"><i class="fas fa-video"></i></div>
        <div><div class="qstat-val"><?php echo $total_lects; ?></div><div class="qstat-lbl">Total Lectures</div></div>
    </div>
    <div class="qstat">
        <div class="qstat-icon" style="background:rgba(34,197,94,.1);color:var(--green);"><i class="fas fa-check-circle"></i></div>
        <div><div class="qstat-val"><?php echo $quizzed; ?></div><div class="qstat-lbl">Have Quiz</div></div>
    </div>
    <div class="qstat">
        <div class="qstat-icon" style="background:rgba(255,77,77,.1);color:var(--red);"><i class="fas fa-times-circle"></i></div>
        <div><div class="qstat-val"><?php echo $no_quiz; ?></div><div class="qstat-lbl">No Quiz Yet</div></div>
    </div>
</div>

<?php if (empty($subjects_data)): ?>
<div class="adm-empty">
    <i class="fas fa-video-slash"></i>
    <p>No lectures found in this course. Add videos first.</p>
</div>

<?php else: ?>
<?php foreach ($subjects_data as $si => $sg):
    $subj      = $sg['subject'];
    $svids     = $sg['videos'];
    $s_total   = count($svids);
    $s_quizzed = count(array_filter($svids, fn($v) => $v['quiz_id']));
    $s_noquiz  = $s_total - $s_quizzed;
    $is_gen    = $subj['subject_id'] === 0;
    $slug      = 'qs-' . $si;
?>

<div class="qs-block">
    <!-- Subject accordion header -->
    <div class="qs-header" onclick="toggleQS('<?php echo $slug; ?>')">
        <div class="qs-icon">
            <i class="fas <?php echo $is_gen ? 'fa-folder-open' : 'fa-book'; ?>"></i>
        </div>
        <div class="qs-title"><?php echo htmlspecialchars($subj['name']); ?></div>
        <div class="qs-badges">
            <span class="adm-badge blue">
                <i class="fas fa-video"></i> <?php echo $s_total; ?> lecture<?php echo $s_total!=1?'s':''; ?>
            </span>
            <?php if ($s_quizzed > 0): ?>
            <span class="adm-badge green">
                <i class="fas fa-check"></i> <?php echo $s_quizzed; ?> quiz<?php echo $s_quizzed!=1?'zes':''; ?>
            </span>
            <?php endif; ?>
            <?php if ($s_noquiz > 0): ?>
            <span class="adm-badge red">
                <i class="fas fa-plus-circle"></i> <?php echo $s_noquiz; ?> to add
            </span>
            <?php endif; ?>
        </div>
        <i class="fas fa-chevron-up qs-chevron" id="chev-<?php echo $slug; ?>"></i>
    </div>

    <!-- Subject lectures body -->
    <div class="qs-body" id="body-<?php echo $slug; ?>">
        <?php if (empty($svids)): ?>
        <div style="padding:16px 20px;color:var(--muted);font-size:.83rem;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-info-circle" style="color:var(--muted2);"></i>
            No lectures assigned to this subject yet.
            <?php if (!$is_gen): ?>
            <a href="assign_videos.php?subject_id=<?php echo $subj['subject_id']; ?>&course_id=<?php echo $filter_course; ?>"
               class="btn-adm btn-adm-ghost btn-adm-sm" style="margin-left:auto;">
                <i class="fas fa-link"></i> Assign Lectures
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php foreach ($svids as $vi => $row): ?>
        <div class="lect-row">
            <div class="lect-num"><?php echo $vi+1; ?></div>
            <div class="lect-title" title="<?php echo htmlspecialchars($row['vtitle']); ?>">
                <?php echo htmlspecialchars($row['vtitle']); ?>
            </div>
            <!-- Status -->
            <div style="flex-shrink:0;">
                <?php if ($row['quiz_id']): ?>
                <span class="adm-badge green" style="font-size:.7rem;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $row['q_count']; ?> MCQ<?php echo $row['q_count']!=1?'s':''; ?>
                </span>
                <?php else: ?>
                <span class="adm-badge" style="background:rgba(255,77,77,.08);color:var(--red);border-color:rgba(255,77,77,.2);font-size:.7rem;">
                    <i class="fas fa-exclamation-circle"></i> No Quiz
                </span>
                <?php endif; ?>
            </div>
            <!-- Actions -->
            <div class="lect-actions">
                <a href="edit_quiz.php?video_id=<?php echo $row['video_id']; ?>"
                   class="btn-adm btn-adm-<?php echo $row['quiz_id']?'ghost':'primary'; ?> btn-adm-sm">
                    <i class="fas fa-<?php echo $row['quiz_id']?'edit':'plus'; ?>"></i>
                    <?php echo $row['quiz_id']?'Edit Quiz':'Add Quiz'; ?>
                </a>
                <?php if ($row['quiz_id']): ?>
                <a href="delete_quiz.php?id=<?php echo $row['quiz_id']; ?>&course_id=<?php echo $filter_course; ?>"
                   class="btn-icon btn-icon-danger" title="Delete Quiz"
                   onclick="return confirm('Delete quiz for \'<?php echo addslashes($row['vtitle']); ?>\'?')">
                    <i class="fas fa-trash"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php endforeach; ?>
<?php endif; ?>

<?php else: ?>
<!-- ALL COURSES VIEW -->
<?php if (empty($by_course)): ?>
<div class="adm-empty">
    <i class="fas fa-video-slash"></i>
    <p>No videos found. Add courses and videos first.</p>
</div>
<?php else: ?>

<div class="adm-alert adm-alert-warn" style="margin-bottom:18px;">
    <i class="fas fa-lightbulb"></i>
    Select a specific course above to see subject-wise quiz organisation.
</div>

<?php foreach ($by_course as $cid => $cdata):
    $cslug = 'cqs-' . $cid;
?>
<div class="qs-block" style="margin-bottom:18px;">
    <!-- Course header -->
    <div class="qs-header" onclick="toggleQS('<?php echo $cslug; ?>')"
         style="background:linear-gradient(135deg,rgba(255,215,0,.07),transparent);">
        <div class="qs-icon"><i class="fas fa-book-open"></i></div>
        <div class="qs-title"><?php echo htmlspecialchars($cdata['ctitle']); ?></div>
        <div class="qs-badges">
            <a href="manage_quizzes.php?course_id=<?php echo $cid; ?>"
               class="btn-adm btn-adm-ghost btn-adm-sm"
               onclick="event.stopPropagation()">
                <i class="fas fa-layer-group"></i> Subject View
            </a>
        </div>
        <i class="fas fa-chevron-up qs-chevron" id="chev-<?php echo $cslug; ?>"></i>
    </div>

    <div class="qs-body" id="body-<?php echo $cslug; ?>">
    <?php foreach ($cdata['subjects'] as $sname => $svids):
        $is_gen  = $sname === '__general__';
        $slabel  = $is_gen ? 'General / Unassigned' : $sname;
        $sslug   = $cslug . '-' . md5($sname);
    ?>
        <!-- Subject sub-header -->
        <div style="display:flex;align-items:center;gap:10px;padding:10px 20px;cursor:pointer;background:rgba(255,215,0,.03);border-bottom:1px solid rgba(255,255,255,.04);"
             onclick="toggleQS('<?php echo $sslug; ?>')">
            <i class="fas <?php echo $is_gen?'fa-folder-open':'fa-book'; ?>"
               style="color:var(--gold);font-size:.78rem;width:16px;text-align:center;"></i>
            <span style="flex:1;font-size:.82rem;font-weight:700;color:rgba(255,215,0,.85);">
                <?php echo htmlspecialchars($slabel); ?>
            </span>
            <span class="adm-badge blue" style="font-size:.65rem;">
                <?php echo count($svids); ?> lectures
            </span>
            <i class="fas fa-chevron-up qs-chevron" id="chev-<?php echo $sslug; ?>" style="font-size:.65rem;"></i>
        </div>
        <div id="body-<?php echo $sslug; ?>">
        <?php foreach ($svids as $vi => $row): ?>
        <div class="lect-row" style="padding-left:36px;">
            <div class="lect-num"><?php echo $vi+1; ?></div>
            <div class="lect-title"><?php echo htmlspecialchars($row['vtitle']); ?></div>
            <div style="flex-shrink:0;">
                <?php if ($row['quiz_id']): ?>
                <span class="adm-badge green" style="font-size:.7rem;">
                    <i class="fas fa-check-circle"></i> <?php echo $row['q_count']; ?> MCQs
                </span>
                <?php else: ?>
                <span class="adm-badge" style="background:rgba(255,77,77,.08);color:var(--red);border-color:rgba(255,77,77,.2);font-size:.7rem;">
                    <i class="fas fa-exclamation-circle"></i> No Quiz
                </span>
                <?php endif; ?>
            </div>
            <div class="lect-actions">
                <a href="edit_quiz.php?video_id=<?php echo $row['video_id']; ?>"
                   class="btn-adm btn-adm-<?php echo $row['quiz_id']?'ghost':'primary'; ?> btn-adm-sm">
                    <i class="fas fa-<?php echo $row['quiz_id']?'edit':'plus'; ?>"></i>
                    <?php echo $row['quiz_id']?'Edit':'Add Quiz'; ?>
                </a>
                <?php if ($row['quiz_id']): ?>
                <a href="delete_quiz.php?id=<?php echo $row['quiz_id']; ?>"
                   class="btn-icon btn-icon-danger"
                   onclick="return confirm('Delete this quiz?')">
                    <i class="fas fa-trash"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>

<script>
function toggleQS(slug) {
    const body    = document.getElementById('body-' + slug);
    const chevron = document.getElementById('chev-' + slug);
    if (!body) return;
    const isOpen  = body.style.display !== 'none';
    body.style.display    = isOpen ? 'none' : 'block';
    if (chevron) chevron.style.transform = isOpen ? 'rotate(180deg)' : '';
}
</script>

<?php include '_layout_end.php'; ?>