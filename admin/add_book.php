<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$msg = '';
// Existing categories for datalist
$cats = $conn->query("SELECT DISTINCT category FROM book ORDER BY category ASC")->fetch_all(MYSQLI_ASSOC);
$cats = array_column($cats, 'category');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = htmlspecialchars(trim($_POST['title']));
    $author   = htmlspecialchars(trim($_POST['author']));
    $category = htmlspecialchars(trim($_POST['category']));
    $desc     = htmlspecialchars(trim($_POST['description']));
    $real     = (float)$_POST['real_price'];
    $disc     = strlen(trim($_POST['discount_price'])) ? (float)$_POST['discount_price'] : null;
    $image    = null;

    if (!$title || !$category || !$real) {
        $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Title, category and price are required.</div>';
        goto render;
    }

    // Image upload
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Only JPG, PNG, WEBP images allowed.</div>';
            goto render;
        }
        if ($_FILES['image']['size'] > 5*1024*1024) {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Image must be under 5MB.</div>';
            goto render;
        }
        $dir = "../assets/uploads/books/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $image  = uniqid('book_') . '.' . $ext;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dir . $image)) {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Image upload failed.</div>';
            goto render;
        }
    }

    $ins = $conn->prepare("INSERT INTO book (title,author,category,description,image,real_price,discount_price) VALUES (?,?,?,?,?,?,?)");
    $ins->bind_param("sssssdd", $title, $author, $category, $desc, $image, $real, $disc);
    if ($ins->execute()) {
        header("Location: manage_books.php?added=1");
        exit();
    }
    $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> DB error: ' . htmlspecialchars($conn->error) . '</div>';
}

render:
$page_title = "Add Book";
$active_nav = "manage_books";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Add Book</h1>
        <p>Add a new book to the public store</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_books.php" class="btn-adm btn-adm-ghost"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:22px;align-items:start;">

    <div class="adm-card">
        <div class="adm-card-body" style="padding-top:24px;">
            <form method="POST" enctype="multipart/form-data" class="adm-form">

                <div class="form-group">
                    <label class="form-label">Book Title <span>*</span></label>
                    <input type="text" name="title" class="form-input"
                           placeholder="e.g. Human Anatomy & Physiology" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" class="form-input" placeholder="Author name...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category <span>*</span></label>
                        <input type="text" name="category" class="form-input"
                               placeholder="e.g. AFNS, Biology, Chemistry..."
                               list="cat-list" required>
                        <datalist id="cat-list">
                            <?php foreach ($cats as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>">
                            <?php endforeach; ?>
                            <option value="AFNS"><option value="NCAT">
                            <option value="BSN"><option value="Biology">
                            <option value="Chemistry"><option value="Physics">
                            <option value="English"><option value="Mathematics">
                        </datalist>
                        <div style="font-size:.71rem;color:var(--muted);margin-top:4px;">
                            <i class="fas fa-info-circle" style="margin-right:3px;color:var(--gold);"></i>
                            Same category name = same section on books page
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Real Price (Rs.) <span>*</span></label>
                        <div class="form-input-icon">
                            <i class="fas fa-tag"></i>
                            <input type="number" name="real_price" class="form-input"
                                   placeholder="e.g. 1500" min="0" step="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount Price (Rs.) <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                        <div class="form-input-icon">
                            <i class="fas fa-percent"></i>
                            <input type="number" name="discount_price" class="form-input"
                                   placeholder="e.g. 1200" min="0" step="1"
                                   oninput="updateSavings()">
                        </div>
                        <div id="savingsPreview" style="font-size:.72rem;margin-top:4px;display:none;">
                            <i class="fas fa-tag" style="color:var(--green);margin-right:3px;"></i>
                            <span id="savingsText"></span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                    <textarea name="description" class="form-textarea" rows="3"
                              placeholder="Brief description shown on book card..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Book Cover Image</label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                        <div class="file-upload-icon" id="uploadIcon">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="file-upload-text" id="uploadText">
                            <strong>Click to upload</strong> cover image<br>
                            JPG, PNG, WEBP — max 5MB
                        </div>
                        <div id="fileNameDisplay"></div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn-adm btn-adm-primary">
                        <i class="fas fa-plus"></i> Add Book
                    </button>
                    <a href="manage_books.php" class="btn-adm btn-adm-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview panel -->
    <div style="position:sticky;top:74px;">
        <div class="adm-card">
            <div class="adm-card-header"><h2>Cover Preview</h2></div>
            <div class="adm-card-body">
                <div id="imgPreviewWrap" style="border-radius:12px;overflow:hidden;background:var(--s3);aspect-ratio:3/4;display:flex;align-items:center;justify-content:center;border:1px solid var(--border3);">
                    <div style="text-align:center;color:var(--muted2);">
                        <i class="fas fa-book-open" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                        <span style="font-size:.75rem;">Upload cover image</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="adm-card" style="margin-top:14px;">
            <div class="adm-card-header"><h2>Tips</h2></div>
            <div class="adm-card-body">
                <div style="font-size:.78rem;color:var(--muted);line-height:1.8;display:flex;flex-direction:column;gap:6px;">
                    <div><i class="fas fa-check" style="color:var(--green);margin-right:6px;"></i>Use same category name to group books</div>
                    <div><i class="fas fa-check" style="color:var(--green);margin-right:6px;"></i>Discount price shows "% OFF" ribbon</div>
                    <div><i class="fas fa-check" style="color:var(--green);margin-right:6px;"></i>3:4 ratio image looks best (portrait)</div>
                    <div><i class="fas fa-check" style="color:var(--green);margin-right:6px;"></i>WhatsApp order message auto-generated</div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imgPreviewWrap').innerHTML =
                `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;display:block;">`;
        };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('fileNameDisplay').style.display = 'block';
        document.getElementById('fileNameDisplay').innerHTML =
            `<i class="fas fa-check-circle" style="color:var(--green);margin-right:4px;"></i>${input.files[0].name}`;
    }
}

function updateSavings() {
    const real = parseFloat(document.querySelector('[name="real_price"]').value) || 0;
    const disc = parseFloat(document.querySelector('[name="discount_price"]').value) || 0;
    const preview = document.getElementById('savingsPreview');
    if (real > 0 && disc > 0 && disc < real) {
        const pct = Math.round((1 - disc/real)*100);
        const save = Math.round(real - disc);
        document.getElementById('savingsText').textContent = `${pct}% OFF — Save Rs. ${save.toLocaleString()}`;
        preview.style.display = 'block';
        document.getElementById('savingsText').style.color = 'var(--green)';
    } else {
        preview.style.display = 'none';
    }
}
</script>

<?php include '_layout_end.php'; ?>