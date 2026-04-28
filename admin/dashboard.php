<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Stats
$total_courses  = $conn->query("SELECT COUNT(*) as c FROM course")->fetch_assoc()['c'];
$total_students = $conn->query("SELECT COUNT(*) as c FROM student")->fetch_assoc()['c'];
$total_videos   = $conn->query("SELECT COUNT(*) as c FROM video")->fetch_assoc()['c'];
$total_enroll   = $conn->query("SELECT COUNT(*) as c FROM enrollment")->fetch_assoc()['c'];

// Recent students
$recent_students = $conn->query("SELECT * FROM student ORDER BY student_id DESC LIMIT 6");

// Recent courses
$recent_courses  = $conn->query("SELECT * FROM course ORDER BY course_id DESC LIMIT 5");

$page_title = "Dashboard";
$active_nav = "dashboard";
include '_layout.php';
?>

<!-- Stats -->
<div class="adm-stats">
    <div class="stat-card gold">
        <div class="stat-icon"><i class="fas fa-book-open"></i></div>
        <div class="stat-val"><?php echo $total_courses; ?></div>
        <div class="stat-label">Total Courses</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-val"><?php echo $total_students; ?></div>
        <div class="stat-label">Total Students</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-video"></i></div>
        <div class="stat-val"><?php echo $total_videos; ?></div>
        <div class="stat-label">Total Videos</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-val"><?php echo $total_enroll; ?></div>
        <div class="stat-label">Enrollments</div>
    </div>
</div>

<!-- Quick Actions -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

    <!-- Recent Students -->
    <div class="adm-card">
        <div class="adm-card-header">
            <h2><i class="fas fa-users" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>Recent Students</h2>
            <a href="manage_students.php" class="btn-adm btn-adm-ghost btn-adm-sm">View All</a>
        </div>
        <div class="adm-card-body">
            <?php if ($recent_students->num_rows > 0): ?>
            <div style="display:flex;flex-direction:column;gap:2px;">
                <?php while($s = $recent_students->fetch_assoc()): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);">
                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold2));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.78rem;color:#111;flex-shrink:0;">
                        <?php echo strtoupper(substr($s['name'],0,1)); ?>
                    </div>
                    <div style="min-width:0;flex:1;">
                        <div style="font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($s['name']); ?></div>
                        <div style="font-size:.73rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($s['email']); ?></div>
                    </div>
                    <a href="enroll_student.php" class="btn-adm btn-adm-ghost btn-adm-sm" style="flex-shrink:0;">Enroll</a>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="adm-empty"><i class="fas fa-users"></i><p>No students yet.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Courses -->
    <div class="adm-card">
        <div class="adm-card-header">
            <h2><i class="fas fa-book-open" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>Recent Courses</h2>
            <a href="manage_courses.php" class="btn-adm btn-adm-ghost btn-adm-sm">View All</a>
        </div>
        <div class="adm-card-body">
            <?php if ($recent_courses->num_rows > 0): ?>
            <div style="display:flex;flex-direction:column;gap:2px;">
                <?php while($c = $recent_courses->fetch_assoc()): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);">
                    <div style="width:40px;height:36px;border-radius:8px;background:var(--s3);overflow:hidden;flex-shrink:0;">
                        <img src="../assets/images/<?php echo htmlspecialchars($c['image']); ?>"
                             style="width:100%;height:100%;object-fit:cover;" alt=""
                             onerror="this.parentElement.innerHTML='<i class=\'fas fa-book\' style=\'color:var(--muted);font-size:.9rem;display:flex;align-items:center;justify-content:center;height:100%;\'></i>'">
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($c['title']); ?></div>
                        <div style="font-size:.73rem;color:var(--gold);font-weight:600;">Rs. <?php echo number_format($c['discount_price']); ?></div>
                    </div>
                    <a href="delete_course.php?id=<?php echo $c['course_id']; ?>"
                       class="btn-icon btn-icon-danger"
                       onclick="return confirm('Delete this course?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="adm-empty"><i class="fas fa-book-open"></i><p>No courses yet.</p></div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Quick Links -->
<div class="adm-card">
    <div class="adm-card-header">
        <h2><i class="fas fa-bolt" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>Quick Actions</h2>
    </div>
    <div class="adm-card-body">
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            <a href="add_course.php" class="btn-adm btn-adm-primary">
                <i class="fas fa-plus"></i> Add New Course
            </a>
            <a href="add_video.php" class="btn-adm btn-adm-ghost">
                <i class="fas fa-video"></i> Add Video
            </a>
            <a href="manage_courses.php" class="btn-adm btn-adm-ghost">
                <i class="fas fa-book-open"></i> Manage Courses
            </a>
            <a href="manage_students.php" class="btn-adm btn-adm-ghost">
                <i class="fas fa-users"></i> Manage Students
            </a>
            <a href="enroll_student.php" class="btn-adm btn-adm-ghost">
                <i class="fas fa-user-plus"></i> Enroll Student
            </a>
        </div>
    </div>
</div>

<?php include '_layout_end.php'; ?>