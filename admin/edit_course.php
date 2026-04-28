<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$course_id = (int)($_GET['id'] ?? $_POST['course_id'] ?? 0);
if (!$course_id) { header("Location: manage_courses.php"); exit(); }

// Fetch course
$stmt = $conn->prepare("SELECT * FROM course WHERE course_id=?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) { header("Location: manage_courses.php"); exit(); }

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = htmlspecialchars(trim($_POST['title']));
    $description    = htmlspecialchars(trim($_POST['description']));
    $price          = (float)$_POST['price'];
    $discount_price = (float)$_POST['discount_price'];
    $image          = $course['image']; // keep old image by default

    // New image uploaded?
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Only JPG, PNG, WEBP allowed.</div>';
            goto render;
        }
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Image must be under 5MB.</div>';
            goto render;
        }
        $image = uniqid('course_') . '.' . $ext;
        $target = "../assets/images/" . $image;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Image upload failed. Check folder permissions.</div>';
            goto render;
        }
        // Delete old image if exists
        $old = "../assets/images/" . $course['image'];
        if (file_exists($old)) @unlink($old);
    }

    $upd = $conn->prepare("UPDATE course SET title=?, description=?, price=?, discount_price=?, image=? WHERE course_id=?");
    $upd->bind_param("ssddsi", $title, $description, $price, $discount_price, $image, $course_id);
    if ($upd->execute()) {
        // Refresh course data
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Course updated successfully!</div>';
    } else {
        $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Update failed: ' . htmlspecialchars($conn->error) . '</div>';
    }
}

render:
$page_title = "Edit Course";
$active_nav = "manage_courses";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Edit Course</h1>
        <p>Update course details, pricing or thumbnail</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_courses.php" class="btn-adm btn-adm-ghost">
            <i class="fas fa-arrow-left"></i> Back to Courses
        </a>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:22px;align-items:start;">

    <!-- Form -->
    <div class="adm-card">
        <div class="adm-card-body" style="padding-top:24px;">
            <form method="POST" enctype="multipart/form-data" class="adm-form">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">

                <div class="form-group">
                    <label class="form-label">Course Title <span>*</span></label>
                    <input type="text" name="title" class="form-input"
                           value="<?php echo htmlspecialchars($course['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea"><?php echo htmlspecialchars($course['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Original Price (Rs.) <span>*</span></label>
                        <div class="form-input-icon">
                            <i class="fas fa-tag"></i>
                            <input type="number" name="price" class="form-input"
                                   value="<?php echo $course['price']; ?>" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount Price (Rs.) <span>*</span></label>
                        <div class="form-input-icon">
                            <i class="fas fa-percent"></i>
                            <input type="number" name="discount_price" class="form-input"
                                   value="<?php echo $course['discount_price']; ?>" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Replace Thumbnail <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <input type="file" name="image" accept="image/*" onchange="handleFileChange(this)">
                        <div class="file-upload-icon"><i class="fas fa-camera"></i></div>
                        <div class="file-upload-text">
                            <strong>Click to replace</strong> current thumbnail<br>
                            JPG, PNG, WEBP — max 5MB
                        </div>
                        <div id="fileNameDisplay"></div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;padding-top:4px;">
                    <button type="submit" class="btn-adm btn-adm-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="manage_courses.php" class="btn-adm btn-adm-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Current thumbnail preview -->
    <div style="display:flex;flex-direction:column;gap:14px;">
        <div class="adm-card">
            <div class="adm-card-header"><h2>Current Thumbnail</h2></div>
            <div class="adm-card-body">
                <div style="border-radius:10px;overflow:hidden;border:1px solid var(--border3);">
                    <img src="../assets/images/<?php echo htmlspecialchars($course['image']); ?>"
                         id="thumbPreview"
                         alt="<?php echo htmlspecialchars($course['title']); ?>"
                         style="width:100%;display:block;aspect-ratio:16/9;object-fit:cover;"
                         onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'56\'><rect fill=\'%231e1e1e\'/></svg>'">
                </div>
                <div style="margin-top:10px;font-size:.78rem;color:var(--muted);">
                    <i class="fas fa-image" style="margin-right:5px;"></i><?php echo htmlspecialchars($course['image']); ?>
                </div>
            </div>
        </div>

        <!-- Quick stats -->
        <div class="adm-card">
            <div class="adm-card-header"><h2>Course Stats</h2></div>
            <div class="adm-card-body">
                <?php
                $vc = $conn->prepare("SELECT COUNT(*) as c FROM video WHERE course_id=?");
                $vc->bind_param("i",$course_id); $vc->execute();
                $video_count = $vc->get_result()->fetch_assoc()['c'];
                $ec = $conn->prepare("SELECT COUNT(*) as c FROM enrollment WHERE course_id=?");
                $ec->bind_param("i",$course_id); $ec->execute();
                $enroll_count = $ec->get_result()->fetch_assoc()['c'];
                ?>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--s2);border-radius:10px;">
                        <span style="font-size:.82rem;color:var(--muted);"><i class="fas fa-video" style="margin-right:6px;color:var(--blue);"></i>Videos</span>
                        <span class="adm-badge blue"><?php echo $video_count; ?></span>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--s2);border-radius:10px;">
                        <span style="font-size:.82rem;color:var(--muted);"><i class="fas fa-user-graduate" style="margin-right:6px;color:var(--green);"></i>Enrolled</span>
                        <span class="adm-badge green"><?php echo $enroll_count; ?></span>
                    </div>
                    <a href="manage_videos.php?course_id=<?php echo $course_id; ?>" class="btn-adm btn-adm-ghost" style="justify-content:center;">
                        <i class="fas fa-video"></i> Manage Videos
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function handleFileChange(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        display.style.display = 'block';
        display.textContent = '✓ ' + input.files[0].name;
        // Live preview
        const reader = new FileReader();
        reader.onload = e => document.getElementById('thumbPreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '_layout_end.php'; ?>