<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$msg = '';
$pre_student = (int)($_GET['student_id'] ?? 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = (int)$_POST['student_id'];
    $course_id  = (int)$_POST['course_id'];

    $check = $conn->prepare("SELECT 1 FROM enrollment WHERE student_id=? AND course_id=?");
    $check->bind_param("ii", $student_id, $course_id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $msg = '<div class="adm-alert adm-alert-warn"><i class="fas fa-exclamation-triangle"></i> This student is already enrolled in the selected course.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO enrollment (student_id, course_id) VALUES (?,?)");
        $stmt->bind_param("ii", $student_id, $course_id);
        if ($stmt->execute()) {
            $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Student enrolled successfully! <a href="manage_students.php">Back to students →</a></div>';
        } else {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Error: ' . htmlspecialchars($conn->error) . '</div>';
        }
    }
}

$students = $conn->query("SELECT * FROM student ORDER BY name ASC");
$courses  = $conn->query("SELECT * FROM course ORDER BY title ASC");

$page_title = "Enroll Student";
$active_nav = "enroll_student";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Enroll Student</h1>
        <p>Assign a student to a course</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_students.php" class="btn-adm btn-adm-ghost">
            <i class="fas fa-users"></i> All Students
        </a>
    </div>
</div>

<?php echo $msg; ?>

<div class="adm-card" style="max-width:600px;">
    <div class="adm-card-body" style="padding-top:24px;">
        <form method="POST" class="adm-form">

            <div class="form-group">
                <label class="form-label">Select Student <span>*</span></label>
                <?php if ($students->num_rows > 0): ?>
                <select name="student_id" class="form-select" required>
                    <option value="" disabled <?php echo !$pre_student?'selected':''; ?>>— Choose a student —</option>
                    <?php while($row = $students->fetch_assoc()): ?>
                    <option value="<?php echo $row['student_id']; ?>"
                        <?php echo $row['student_id'] == $pre_student ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['name']); ?> — <?php echo htmlspecialchars($row['email']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <?php else: ?>
                <div class="adm-alert adm-alert-warn" style="margin:0;">
                    <i class="fas fa-exclamation-triangle"></i> No students registered yet.
                </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Select Course <span>*</span></label>
                <?php if ($courses->num_rows > 0): ?>
                <select name="course_id" class="form-select" required>
                    <option value="" disabled selected>— Choose a course —</option>
                    <?php while($row = $courses->fetch_assoc()): ?>
                    <option value="<?php echo $row['course_id']; ?>">
                        <?php echo htmlspecialchars($row['title']); ?>
                        — Rs. <?php echo number_format($row['discount_price']); ?>
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

            <div style="display:flex;gap:12px;padding-top:4px;">
                <button type="submit" class="btn-adm btn-adm-primary"
                    <?php echo ($students->num_rows == 0 || $courses->num_rows == 0) ? 'disabled' : ''; ?>>
                    <i class="fas fa-user-plus"></i> Enroll Student
                </button>
                <a href="manage_students.php" class="btn-adm btn-adm-ghost">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php include '_layout_end.php'; ?>