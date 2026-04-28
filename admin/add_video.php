<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id    = (int)$_POST['course_id'];
    $title        = htmlspecialchars(trim($_POST['title']));
    $youtube_link = trim($_POST['youtube_link']);

    $subject_id = (int)($_POST['subject_id'] ?? 0) ?: null;
    $unit_id    = (int)($_POST['unit_id']    ?? 0) ?: null;
    $stmt = $conn->prepare("INSERT INTO video (course_id, title, youtube_link, subject_id, unit_id) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issii", $course_id, $title, $youtube_link, $subject_id, $unit_id);
    if ($stmt->execute()) {
        $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Video added successfully! <a href="add_video.php">Add another →</a></div>';
    } else {
        $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Error: ' . htmlspecialchars($conn->error) . '</div>';
    }
}

$courses = $conn->query("SELECT course_id, title FROM course ORDER BY course_id DESC");
$subjects_all = $conn->query("SELECT s.subject_id, s.name, s.course_id, c.title as ctitle FROM subject s JOIN course c ON c.course_id=s.course_id ORDER BY s.course_id, s.sort_order ASC");
$subjects_by_course = [];
while($sr=$subjects_all->fetch_assoc()) {
    $subjects_by_course[$sr['course_id']][] = $sr;
}
$units_all = $conn->query("SELECT u.unit_id, u.name, u.subject_id FROM unit u ORDER BY u.subject_id ASC, u.sort_order ASC");
$units_by_subject = [];
while($ur=$units_all->fetch_assoc()) {
    $units_by_subject[$ur['subject_id']][] = $ur;
}

$page_title = "Add Video";
$active_nav = "add_video";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Add Video</h1>
        <p>Attach a YouTube video to a course</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_courses.php" class="btn-adm btn-adm-ghost">
            <i class="fas fa-book-open"></i> View Courses
        </a>
    </div>
</div>

<?php echo $msg; ?>

<div class="adm-card" style="max-width:620px;">
    <div class="adm-card-body" style="padding-top:24px;">
        <form method="POST" class="adm-form">

            <div class="form-group" id="fgCourse">
                <label class="form-label">Select Course <span>*</span></label>
                <?php if ($courses->num_rows > 0): ?>
                <select name="course_id" class="form-select" required>
                    <option value="" disabled selected>— Choose a course —</option>
                    <?php while($row = $courses->fetch_assoc()): ?>
                    <option value="<?php echo $row['course_id']; ?>">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <?php else: ?>
                <div class="adm-alert adm-alert-warn" style="margin:0;">
                    <i class="fas fa-exclamation-triangle"></i>
                    No courses found. <a href="add_course.php">Add a course first →</a>
                </div>
                <?php endif; ?>
            </div>


            <div class="form-group" id="fgSubject" style="display:none;">
                <label class="form-label">Subject <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                <select name="subject_id" id="subjectSelect" class="form-select" onchange="updateUnits()">
                    <option value="0">— No Subject / General —</option>
                </select>
            </div>

            <div class="form-group" id="fgUnit" style="display:none;">
                <label class="form-label">Unit <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                <select name="unit_id" id="unitSelect" class="form-select">
                    <option value="0">— No Unit —</option>
                </select>
                <div style="font-size:.71rem;color:var(--muted);margin-top:4px;">
                    <i class="fas fa-info-circle" style="margin-right:3px;color:var(--gold);"></i>
                    Select a unit to group this lecture inside a subject.
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Video Title <span>*</span></label>
                <input type="text" name="title" class="form-input"
                       placeholder="e.g. Lecture 1 — Introduction to Anatomy" required>
            </div>

            <div class="form-group">
                <label class="form-label">YouTube Link <span>*</span></label>
                <div class="form-input-icon">
                    <i class="fab fa-youtube" style="color:#ff4444;"></i>
                    <input type="url" name="youtube_link" class="form-input"
                           placeholder="https://www.youtube.com/watch?v=..." required
                           oninput="previewYT(this.value)">
                </div>
                <!-- Live thumbnail preview -->
                <div id="ytPreview" style="display:none;margin-top:10px;border-radius:10px;overflow:hidden;border:1px solid var(--border3);position:relative;">
                    <img id="ytThumb" src="" alt="Preview" style="width:100%;display:block;max-height:180px;object-fit:cover;">
                    <div style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,.65);color:#fff;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                        <i class="fab fa-youtube" style="color:#ff4444;"></i> Preview
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:12px;padding-top:4px;">
                <button type="submit" class="btn-adm btn-adm-primary" <?php echo $courses->num_rows == 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-plus-circle"></i> Add Video
                </button>
                <a href="dashboard.php" class="btn-adm btn-adm-ghost">Cancel</a>
            </div>

        </form>
    </div>
</div>

<script>
function getYTId(url) {
    const patterns = [
        /(?:youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/,
        /(?:youtu\.be\/)([a-zA-Z0-9_-]{11})/,
        /(?:youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
    ];
    for (const p of patterns) {
        const m = url.match(p);
        if (m) return m[1];
    }
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

const SUBJECTS_BY_COURSE = <?php echo json_encode($subjects_by_course); ?>;
const UNITS_BY_SUBJECT   = <?php echo json_encode($units_by_subject); ?>;
const subjectSelect = document.getElementById('subjectSelect');
const unitSelect    = document.getElementById('unitSelect');
const courseSelect  = document.querySelector('select[name="course_id"]');

function updateSubjects() {
    const cid  = courseSelect?.value;
    const subs = SUBJECTS_BY_COURSE[cid] || [];
    subjectSelect.innerHTML = '<option value="0">— No Subject / General —</option>';
    subs.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.subject_id; opt.textContent = s.name;
        subjectSelect.appendChild(opt);
    });
    document.getElementById('fgSubject').style.display = subs.length ? 'block' : 'none';
    updateUnits();
}

function updateUnits() {
    const sid   = subjectSelect?.value;
    const units = UNITS_BY_SUBJECT[sid] || [];
    unitSelect.innerHTML = '<option value="0">— No Unit —</option>';
    units.forEach(u => {
        const opt = document.createElement('option');
        opt.value = u.unit_id; opt.textContent = u.name;
        unitSelect.appendChild(opt);
    });
    document.getElementById('fgUnit').style.display = (units.length && sid > 0) ? 'block' : 'none';
}

if (courseSelect) {
    courseSelect.addEventListener('change', updateSubjects);
    updateSubjects();
}
</script>

<?php include '_layout_end.php'; ?>