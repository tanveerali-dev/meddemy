<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Search
$search = trim($_GET['search'] ?? '');
$where  = $search ? "WHERE title LIKE '%" . $conn->real_escape_string($search) . "%'" : '';

$result = $conn->query("SELECT c.*, 
    (SELECT COUNT(*) FROM video v WHERE v.course_id = c.course_id) as video_count,
    (SELECT COUNT(*) FROM enrollment e WHERE e.course_id = c.course_id) as enroll_count
    FROM course c $where ORDER BY c.course_id DESC");

$page_title = "Manage Courses";
$active_nav = "manage_courses";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Manage Courses</h1>
        <p>View, search and delete courses</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="add_course.php" class="btn-adm btn-adm-primary">
            <i class="fas fa-plus"></i> Add Course
        </a>
    </div>
</div>

<!-- Search bar -->
<div style="margin-bottom:18px;">
    <form method="GET" style="display:flex;gap:10px;max-width:420px;">
        <div class="form-input-icon" style="flex:1;">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-input"
                   placeholder="Search courses..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="btn-adm btn-adm-ghost">Search</button>
        <?php if ($search): ?>
        <a href="manage_courses.php" class="btn-adm btn-adm-ghost">
            <i class="fas fa-times"></i>
        </a>
        <?php endif; ?>
    </form>
</div>

<div class="adm-card">
    <div class="adm-card-header">
        <h2><i class="fas fa-book-open" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>
            All Courses
            <?php if ($search): ?>
            <span style="font-size:.75rem;font-weight:400;color:var(--muted);margin-left:8px;">— results for "<?php echo htmlspecialchars($search); ?>"</span>
            <?php endif; ?>
        </h2>
        <span class="adm-badge gold"><?php echo $result->num_rows; ?> courses</span>
    </div>
    <div class="adm-card-body">
        <?php if ($result->num_rows > 0): ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course</th>
                        <th>Price</th>
                        <th>Videos</th>
                        <th>Enrolled</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="color:var(--muted);font-size:.78rem;"><?php echo $row['course_id']; ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:46px;height:36px;border-radius:8px;background:var(--s3);overflow:hidden;flex-shrink:0;">
                                    <img src="../assets/images/<?php echo htmlspecialchars($row['image']); ?>"
                                         style="width:100%;height:100%;object-fit:cover;"
                                         onerror="this.parentElement.style.display='flex';this.parentElement.style.alignItems='center';this.parentElement.style.justifyContent='center';this.remove();this.parentElement.innerHTML='<i class=\'fas fa-image\' style=\'color:var(--muted2);\'></i>'">
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:.87rem;"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <div style="font-size:.73rem;color:var(--muted);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?php echo htmlspecialchars(substr($row['description'],0,80)); ?>...
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:700;color:var(--gold);font-size:.88rem;">Rs. <?php echo number_format($row['discount_price']); ?></div>
                            <div style="font-size:.73rem;color:var(--muted);text-decoration:line-through;">Rs. <?php echo number_format($row['price']); ?></div>
                        </td>
                        <td>
                            <span class="adm-badge blue">
                                <i class="fas fa-video"></i>
                                <?php echo $row['video_count']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="adm-badge green">
                                <i class="fas fa-user-graduate"></i>
                                <?php echo $row['enroll_count']; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <a href="edit_course.php?id=<?php echo $row['course_id']; ?>"
                                   class="btn-icon btn-icon-edit" title="Edit Course">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="add_video.php?course_id=<?php echo $row['course_id']; ?>"
                                   class="btn-icon" style="background:rgba(59,130,246,.1);color:var(--blue);border-radius:8px;" title="Add Video">
                                    <i class="fas fa-video"></i>
                                </a>
                                <a href="manage_videos.php?course_id=<?php echo $row['course_id']; ?>"
                                   class="btn-icon" style="background:rgba(255,255,255,.05);color:var(--muted);border-radius:8px;" title="View Videos">
                                    <i class="fas fa-film"></i>
                                </a>
                                <a href="delete_course.php?id=<?php echo $row['course_id']; ?>"
                                   class="btn-icon btn-icon-danger" title="Delete Course"
                                   onclick="return confirm('Delete this course?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="adm-empty">
            <i class="fas fa-book-open"></i>
            <p><?php echo $search ? 'No courses match your search.' : 'No courses yet.'; ?></p>
            <?php if (!$search): ?>
            <a href="add_course.php" class="btn-adm btn-adm-primary" style="margin-top:14px;">
                <i class="fas fa-plus"></i> Add First Course
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '_layout_end.php'; ?>