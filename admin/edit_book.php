<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$book_id = (int)($_GET['id'] ?? $_POST['book_id'] ?? 0);
if (!$book_id) { header("Location: manage_books.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM book WHERE book_id=?");
$stmt->bind_param("i",$book_id); $stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) { header("Location: manage_books.php"); exit(); }

$cats = $conn->query("SELECT DISTINCT category FROM book ORDER BY category ASC")->fetch_all(MYSQLI_ASSOC);
$cats = array_column($cats, 'category');
$msg  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = htmlspecialchars(trim($_POST['title']));
    $author   = htmlspecialchars(trim($_POST['author']));
    $category = htmlspecialchars(trim($_POST['category']));
    $desc     = htmlspecialchars(trim($_POST['description']));
    $real     = (float)$_POST['real_price'];
    $disc     = strlen(trim($_POST['discount_price'])) ? (float)$_POST['discount_price'] : null;
    $image    = $book['image'];

    // New image?
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) { $msg='<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Only JPG/PNG/WEBP allowed.</div>'; goto render; }
        $dir    = "../assets/uploads/books/";
        if (!is_dir($dir)) mkdir($dir,0755,true);
        $newImg = uniqid('book_').'.'.$ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'],$dir.$newImg)) {
            if ($image && file_exists($dir.$image)) @unlink($dir.$image);
            $image = $newImg;
        }
    }

    $upd = $conn->prepare("UPDATE book SET title=?,author=?,category=?,description=?,image=?,real_price=?,discount_price=? WHERE book_id=?");
    $upd->bind_param("sssssddi",$title,$author,$category,$desc,$image,$real,$disc,$book_id);
    if ($upd->execute()) {
        // Refresh
        $stmt->execute(); $book = $stmt->get_result()->fetch_assoc();
        $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Book updated successfully!</div>';
    } else {
        $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Update failed.</div>';
    }
}

render:
$page_title = "Edit Book";
$active_nav = "manage_books";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Edit Book</h1>
        <p><?php echo htmlspecialchars($book['title']); ?></p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_books.php" class="btn-adm btn-adm-ghost"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 280px;gap:22px;align-items:start;">
    <div class="adm-card">
        <div class="adm-card-body" style="padding-top:24px;">
            <form method="POST" enctype="multipart/form-data" class="adm-form">
                <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">

                <div class="form-group">
                    <label class="form-label">Book Title <span>*</span></label>
                    <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" class="form-input" value="<?php echo htmlspecialchars($book['author']??''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category <span>*</span></label>
                        <input type="text" name="category" class="form-input" list="cat-list"
                               value="<?php echo htmlspecialchars($book['category']); ?>" required>
                        <datalist id="cat-list">
                            <?php foreach ($cats as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Real Price (Rs.) <span>*</span></label>
                        <div class="form-input-icon">
                            <i class="fas fa-tag"></i>
                            <input type="number" name="real_price" class="form-input"
                                   value="<?php echo $book['real_price']; ?>" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount Price (Rs.)</label>
                        <div class="form-input-icon">
                            <i class="fas fa-percent"></i>
                            <input type="number" name="discount_price" class="form-input"
                                   value="<?php echo $book['discount_price'] ?? ''; ?>" min="0">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="3"><?php echo htmlspecialchars($book['description']??''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Replace Cover Image <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                    <div class="file-upload-area">
                        <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                        <div class="file-upload-icon"><i class="fas fa-camera"></i></div>
                        <div class="file-upload-text"><strong>Click to replace</strong> current cover<br>JPG, PNG, WEBP — max 5MB</div>
                        <div id="fileNameDisplay"></div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn-adm btn-adm-primary"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="manage_books.php" class="btn-adm btn-adm-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Current cover -->
    <div style="position:sticky;top:74px;">
        <div class="adm-card">
            <div class="adm-card-header"><h2>Current Cover</h2></div>
            <div class="adm-card-body">
                <div style="border-radius:12px;overflow:hidden;border:1px solid var(--border3);">
                    <?php if ($book['image']): ?>
                    <img src="../assets/uploads/books/<?php echo htmlspecialchars($book['image']); ?>"
                         id="coverPreview"
                         style="width:100%;display:block;aspect-ratio:3/4;object-fit:cover;"
                         onerror="this.style.display='none'">
                    <?php else: ?>
                    <div id="coverPreview" style="aspect-ratio:3/4;display:flex;align-items:center;justify-content:center;background:var(--s3);color:var(--muted2);">
                        <i class="fas fa-book-open" style="font-size:2rem;"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
                $hasDisc = $book['discount_price'] && $book['discount_price'] < $book['real_price'];
                $discPct = $hasDisc ? round((1-$book['discount_price']/$book['real_price'])*100) : 0;
                ?>
                <div style="margin-top:12px;display:flex;flex-direction:column;gap:6px;">
                    <div style="display:flex;justify-content:space-between;font-size:.8rem;">
                        <span style="color:var(--muted);">Real Price</span>
                        <span style="<?php echo $hasDisc?'text-decoration:line-through;color:var(--muted);':'font-weight:700;'; ?>">
                            Rs. <?php echo number_format($book['real_price'],0); ?>
                        </span>
                    </div>
                    <?php if ($hasDisc): ?>
                    <div style="display:flex;justify-content:space-between;font-size:.8rem;">
                        <span style="color:var(--muted);">Discount Price</span>
                        <span style="color:var(--gold);font-weight:800;">Rs. <?php echo number_format($book['discount_price'],0); ?></span>
                    </div>
                    <div style="text-align:center;padding:6px;background:rgba(255,77,77,.1);border:1px solid rgba(255,77,77,.2);border-radius:8px;color:var(--red);font-size:.8rem;font-weight:700;">
                        <?php echo $discPct; ?>% OFF — Save Rs. <?php echo number_format($book['real_price']-$book['discount_price'],0); ?>
                    </div>
                    <?php endif; ?>
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
            const prev = document.getElementById('coverPreview');
            prev.src   = e.target.result;
            prev.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('fileNameDisplay').style.display = 'block';
        document.getElementById('fileNameDisplay').textContent   = '✓ ' + input.files[0].name;
    }
}
</script>

<?php include '_layout_end.php'; ?>