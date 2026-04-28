<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$course_id = (int)($_GET['id'] ?? 0);
if (!$course_id) { header("Location: manage_courses.php"); exit(); }

// Fetch course
$stmt = $conn->prepare("SELECT * FROM course WHERE course_id=?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) { header("Location: manage_courses.php"); exit(); }

// Check enrollments
$ec = $conn->prepare("SELECT COUNT(*) as c FROM enrollment WHERE course_id=?");
$ec->bind_param("i", $course_id);
$ec->execute();
$enroll_count = $ec->get_result()->fetch_assoc()['c'];

if ($enroll_count > 0) {
    // BLOCK — students enrolled, cannot delete
    $page_title = "Cannot Delete Course";
    $active_nav = "manage_courses";
    include '_layout.php';
    ?>
    <div class="adm-page-header">
        <div class="adm-page-header-left">
            <h1>Cannot Delete Course</h1>
            <p>This course has enrolled students</p>
        </div>
        <div class="adm-page-header-actions">
            <a href="manage_courses.php" class="btn-adm btn-adm-ghost">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="adm-alert adm-alert-danger" style="max-width:620px;">
        <i class="fas fa-ban" style="font-size:1.1rem;"></i>
        <div>
            <strong>Deletion blocked.</strong><br>
            <strong><?php echo htmlspecialchars($course['title']); ?></strong> has
            <strong><?php echo $enroll_count; ?> enrolled student<?php echo $enroll_count > 1 ? 's' : ''; ?></strong>.
            You must remove all enrollments before deleting this course.
        </div>
    </div>

    <div class="adm-card" style="max-width:620px;">
        <div class="adm-card-header">
            <h2>Enrolled Students</h2>
            <span class="adm-badge red"><?php echo $enroll_count; ?> enrolled</span>
        </div>
        <div class="adm-card-body">
            <?php
            $enr = $conn->prepare("SELECT s.name, s.email, e.enroll_id FROM enrollment e JOIN student s ON s.student_id = e.student_id WHERE e.course_id=?");
            $enr->bind_param("i", $course_id);
            $enr->execute();
            $enrolled = $enr->get_result();
            ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Student</th><th>Email</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php while($r = $enrolled->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($r['name']); ?></td>
                        <td style="color:var(--muted);"><?php echo htmlspecialchars($r['email']); ?></td>
                        <td>
                            <a href="delete_enrollment.php?id=<?php echo $r['enroll_id']; ?>&redirect=delete_course&course_id=<?php echo $course_id; ?>"
                               class="btn-adm btn-adm-danger btn-adm-sm"
                               onclick="return confirm('Remove this student from the course?')">
                                <i class="fas fa-user-minus"></i> Remove
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border3);display:flex;gap:10px;">
                <a href="manage_courses.php" class="btn-adm btn-adm-ghost">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
                <a href="manage_students.php" class="btn-adm btn-adm-ghost">
                    <i class="fas fa-users"></i> Manage Students
                </a>
            </div>
        </div>
    </div>

    <?php
    include '_layout_end.php';
    exit();
}

// No enrollments — safe to delete
// Delete videos first, then course image, then course
$vDel = $conn->prepare("DELETE FROM video WHERE course_id=?");
$vDel->bind_param("i", $course_id);
$vDel->execute();

$cDel = $conn->prepare("DELETE FROM course WHERE course_id=?");
$cDel->bind_param("i", $course_id);
$cDel->execute();

// Delete image file
$imgPath = "../assets/images/" . $course['image'];
if (file_exists($imgPath)) @unlink($imgPath);

header("Location: manage_courses.php?deleted=1");
exit();