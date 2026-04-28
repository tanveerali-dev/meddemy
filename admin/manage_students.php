<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$search = trim($_GET['search'] ?? '');
$where  = $search
    ? "WHERE s.name LIKE '%" . $conn->real_escape_string($search) . "%' OR s.email LIKE '%" . $conn->real_escape_string($search) . "%'"
    : '';

$students = $conn->query("SELECT s.*,
    (SELECT COUNT(*) FROM enrollment e WHERE e.student_id = s.student_id) as enroll_count
    FROM student s $where ORDER BY s.student_id DESC");

$total = $conn->query("SELECT COUNT(*) as c FROM student")->fetch_assoc()['c'];

$msg = '';
if (isset($_GET['removed']))  $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Enrollment removed successfully.</div>';
if (isset($_GET['enrolled'])) $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Student enrolled successfully.</div>';

$page_title = "Manage Students";
$active_nav = "manage_students";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Manage Students</h1>
        <p>View students, their enrollments and manage access</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="enroll_student.php" class="btn-adm btn-adm-primary">
            <i class="fas fa-user-plus"></i> Enroll Student
        </a>
    </div>
</div>

<?php echo $msg; ?>

<div style="margin-bottom:18px;">
    <form method="GET" style="display:flex;gap:10px;max-width:420px;">
        <div class="form-input-icon" style="flex:1;">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-input"
                   placeholder="Search by name or email..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="btn-adm btn-adm-ghost">Search</button>
        <?php if ($search): ?>
        <a href="manage_students.php" class="btn-adm btn-adm-ghost"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>
</div>

<div class="adm-card">
    <div class="adm-card-header">
        <h2><i class="fas fa-users" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>All Students</h2>
        <span class="adm-badge blue"><?php echo $total; ?> total</span>
    </div>
    <div class="adm-card-body">
        <?php if ($students->num_rows > 0): ?>
        <div style="display:flex;flex-direction:column;gap:10px;">

        <?php while($row = $students->fetch_assoc()):
            $enr_stmt = $conn->prepare("SELECT e.enroll_id, c.title, c.course_id FROM enrollment e JOIN course c ON c.course_id=e.course_id WHERE e.student_id=? ORDER BY e.enroll_id ASC");
            $enr_stmt->bind_param("i", $row['student_id']);
            $enr_stmt->execute();
            $enrollments = $enr_stmt->get_result();
        ?>

        <div style="background:var(--s2);border:1px solid var(--border2);border-radius:14px;overflow:hidden;transition:border-color .2s;"
             onmouseover="this.style.borderColor='rgba(255,215,0,.18)'"
             onmouseout="this.style.borderColor='var(--border2)'">

            <!-- Student header row -->
            <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;flex-wrap:wrap;">
                <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#222,#333);border:1px solid var(--border3);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.88rem;color:var(--gold);flex-shrink:0;">
                    <?php echo strtoupper(substr($row['name'],0,1)); ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:.9rem;"><?php echo htmlspecialchars($row['name']); ?></div>
                    <div style="font-size:.76rem;color:var(--muted);"><?php echo htmlspecialchars($row['email']); ?></div>
                </div>
                <?php if ($row['enroll_count'] > 0): ?>
                <span class="adm-badge green"><i class="fas fa-book-open"></i> <?php echo $row['enroll_count']; ?> course<?php echo $row['enroll_count']>1?'s':''; ?></span>
                <?php else: ?>
                <span class="adm-badge" style="background:var(--s3);color:var(--muted2);border-color:var(--border3);">Not enrolled</span>
                <?php endif; ?>
                <a href="enroll_student.php?student_id=<?php echo $row['student_id']; ?>"
                   class="btn-adm btn-adm-ghost btn-adm-sm" style="flex-shrink:0;">
                    <i class="fas fa-user-plus"></i> Enroll
                </a>
            </div>

            <!-- Enrolled courses pills -->
            <?php if ($enrollments->num_rows > 0): ?>
            <div style="padding:10px 18px 14px;border-top:1px solid rgba(255,255,255,.05);">
                <div style="font-size:.65rem;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;color:var(--muted2);margin-bottom:8px;">
                    <i class="fas fa-graduation-cap" style="margin-right:4px;color:var(--gold);"></i>Enrolled In
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:7px;">
                    <?php while($e = $enrollments->fetch_assoc()): ?>
                    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,215,0,.06);border:1px solid rgba(255,215,0,.15);border-radius:30px;padding:5px 6px 5px 13px;">
                        <span style="font-size:.8rem;font-weight:600;color:rgba(255,255,255,.82);"><?php echo htmlspecialchars($e['title']); ?></span>
                        <a href="delete_enrollment.php?id=<?php echo $e['enroll_id']; ?>&redirect=manage_students"
                           title="Remove from this course"
                           onclick="return confirm('Remove <?php echo addslashes($row['name']); ?> from \'<?php echo addslashes($e['title']); ?>\'?')"
                           style="width:20px;height:20px;border-radius:50%;background:rgba(255,77,77,.1);color:var(--red);display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:.65rem;flex-shrink:0;border:1px solid rgba(255,77,77,.15);transition:all .2s;"
                           onmouseover="this.style.background='rgba(255,77,77,.25)';this.style.transform='scale(1.1)'"
                           onmouseout="this.style.background='rgba(255,77,77,.1)';this.style.transform='scale(1)'">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php endwhile; ?>
        </div>

        <?php else: ?>
        <div class="adm-empty">
            <i class="fas fa-users"></i>
            <p><?php echo $search ? 'No students match your search.' : 'No students registered yet.'; ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '_layout_end.php'; ?>