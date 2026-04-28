<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title          = htmlspecialchars(trim($_POST['title']));
    $description    = htmlspecialchars(trim($_POST['description']));
    $price          = (float)$_POST['price'];
    $discount_price = (float)$_POST['discount_price'];
    $admin_id       = $_SESSION['admin_id'];

    // Image upload
    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Only JPG, PNG, WEBP images allowed.</div>';
    } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Image must be under 5MB.</div>';
    } else {
        $image = uniqid('course_') . '.' . $ext;
        $target = "../assets/images/" . $image;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $stmt = $conn->prepare("INSERT INTO course (title, description, price, discount_price, image, created_by) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssddsi", $title, $description, $price, $discount_price, $image, $admin_id);
            if ($stmt->execute()) {
                $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Course added successfully! <a href="manage_courses.php">View all courses →</a></div>';
            } else {
                $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Database error: ' . htmlspecialchars($conn->error) . '</div>';
            }
        } else {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Failed to upload image. Check folder permissions.</div>';
        }
    }
}

$page_title = "Add Course";
$active_nav = "add_course";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Add New Course</h1>
        <p>Fill in the details to publish a new course</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_courses.php" class="btn-adm btn-adm-ghost">
            <i class="fas fa-list"></i> View All Courses
        </a>
    </div>
</div>

<?php echo $msg; ?>

<div class="adm-card" style="max-width:720px;">
    <div class="adm-card-body" style="padding-top:24px;">
        <form method="POST" enctype="multipart/form-data" class="adm-form">

            <div class="form-group">
                <label class="form-label">Course Title <span>*</span></label>
                <input type="text" name="title" class="form-input"
                       placeholder="e.g. AFNS Nursing Entry Test 2025" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea"
                          placeholder="Write a brief description of what students will learn..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Original Price (Rs.) <span>*</span></label>
                    <div class="form-input-icon">
                        <i class="fas fa-tag"></i>
                        <input type="number" name="price" class="form-input"
                               placeholder="5000" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Discount Price (Rs.) <span>*</span></label>
                    <div class="form-input-icon">
                        <i class="fas fa-percent"></i>
                        <input type="number" name="discount_price" class="form-input"
                               placeholder="3500" min="0" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Course Thumbnail <span>*</span></label>
                <div class="file-upload-area" id="fileUploadArea">
                    <input type="file" name="image" accept="image/*" required
                           onchange="handleFileChange(this)">
                    <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="file-upload-text">
                        <strong>Click to upload</strong> or drag & drop<br>
                        JPG, PNG, WEBP — max 5MB
                    </div>
                    <div id="fileNameDisplay"></div>
                </div>
            </div>

            <div style="display:flex;gap:12px;padding-top:4px;">
                <button type="submit" class="btn-adm btn-adm-primary">
                    <i class="fas fa-plus-circle"></i> Add Course
                </button>
                <a href="dashboard.php" class="btn-adm btn-adm-ghost">Cancel</a>
            </div>

        </form>
    </div>
</div>

<script>
function handleFileChange(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        display.style.display = 'block';
        display.textContent = '✓ ' + input.files[0].name;
    }
}
</script>

<?php include '_layout_end.php'; ?>