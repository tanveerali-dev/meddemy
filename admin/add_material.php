<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $category    = htmlspecialchars(trim($_POST['category']));
    $type        = in_array($_POST['type'], ['free','paid']) ? $_POST['type'] : 'free';
    $admin_id    = $_SESSION['admin_id'];

    // File validation
    $allowed_ext  = ['pdf','doc','docx','ppt','pptx'];
    $max_size     = 20 * 1024 * 1024; // 20MB

    if (empty($_FILES['file']['name'])) {
        $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Please select a file to upload.</div>';
    } else {
        $orig_name = $_FILES['file']['name'];
        $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Only PDF, DOC, DOCX, PPT, PPTX files allowed.</div>';
        } elseif ($_FILES['file']['size'] > $max_size) {
            $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> File must be under 20MB.</div>';
        } else {
            // Create uploads directory if not exists
            $upload_dir = "../assets/uploads/materials/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $file_name = uniqid('mat_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig_name);
            $target    = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                $stmt = $conn->prepare("INSERT INTO material (title, description, category, file_name, type, uploaded_by) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("sssssi", $title, $description, $category, $file_name, $type, $admin_id);
                if ($stmt->execute()) {
                    header("Location: manage_materials.php?added=1");
                    exit();
                } else {
                    $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> DB error: ' . htmlspecialchars($conn->error) . '</div>';
                }
            } else {
                $msg = '<div class="adm-alert adm-alert-danger"><i class="fas fa-times-circle"></i> Upload failed. Check folder permissions for assets/uploads/materials/</div>';
            }
        }
    }
}

$page_title = "Upload Material";
$active_nav = "manage_materials";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Upload Material</h1>
        <p>Add a free or paid book/PDF to the platform</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_materials.php" class="btn-adm btn-adm-ghost">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 280px;gap:22px;align-items:start;">

    <!-- Form -->
    <div class="adm-card">
        <div class="adm-card-body" style="padding-top:24px;">
            <form method="POST" enctype="multipart/form-data" class="adm-form">

                <div class="form-group">
                    <label class="form-label">Title <span>*</span></label>
                    <input type="text" name="title" class="form-input"
                           placeholder="e.g. AFNS Biology Notes 2025" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-input"
                               placeholder="e.g. Biology, Chemistry, Notes..."
                               list="cat-suggestions">
                        <datalist id="cat-suggestions">
                            <option value="Biology">
                            <option value="Chemistry">
                            <option value="Physics">
                            <option value="English">
                            <option value="Mathematics">
                            <option value="Nursing Notes">
                            <option value="Past Papers">
                            <option value="General">
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Access Type <span>*</span></label>
                        <div style="display:flex;gap:10px;margin-top:2px;">
                            <!-- Free option -->
                            <label id="lbl-free" style="flex:1;display:flex;align-items:center;gap:10px;background:var(--s3);border:2px solid var(--green);border-radius:10px;padding:12px 14px;cursor:pointer;transition:all .2s;">
                                <input type="radio" name="type" value="free" checked
                                       onchange="updateTypeUI()"
                                       style="display:none;">
                                <div style="width:32px;height:32px;border-radius:8px;background:rgba(34,197,94,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-unlock" style="color:var(--green);font-size:.85rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size:.83rem;font-weight:700;color:var(--green);">Free</div>
                                    <div style="font-size:.7rem;color:var(--muted);">All students</div>
                                </div>
                            </label>
                            <!-- Paid option -->
                            <label id="lbl-paid" style="flex:1;display:flex;align-items:center;gap:10px;background:var(--s3);border:2px solid var(--border3);border-radius:10px;padding:12px 14px;cursor:pointer;transition:all .2s;">
                                <input type="radio" name="type" value="paid"
                                       onchange="updateTypeUI()"
                                       style="display:none;">
                                <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,215,0,.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-lock" style="color:var(--gold);font-size:.85rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size:.83rem;font-weight:700;color:var(--gold);">Paid</div>
                                    <div style="font-size:.7rem;color:var(--muted);">Admin grants access</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                    <textarea name="description" class="form-textarea" rows="2"
                              placeholder="Brief description of this material..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">File <span>*</span></label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx"
                               required onchange="handleFileChange(this)">
                        <div class="file-upload-icon" id="uploadIcon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="file-upload-text" id="uploadText">
                            <strong>Click to upload</strong> or drag & drop<br>
                            PDF, DOC, DOCX, PPT, PPTX — max 20MB
                        </div>
                        <div id="fileNameDisplay"></div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;padding-top:4px;">
                    <button type="submit" class="btn-adm btn-adm-primary">
                        <i class="fas fa-upload"></i> Upload Material
                    </button>
                    <a href="manage_materials.php" class="btn-adm btn-adm-ghost">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    <!-- Info panel -->
    <div style="display:flex;flex-direction:column;gap:14px;">
        <div class="adm-card">
            <div class="adm-card-header"><h2>Upload Guide</h2></div>
            <div class="adm-card-body">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <?php foreach([
                        ['fa-file-pdf','#ff4d4d','PDF','.pdf files — recommended'],
                        ['fa-file-word','#3b82f6','Word','.doc, .docx files'],
                        ['fa-file-powerpoint','#f97316','PowerPoint','.ppt, .pptx files'],
                    ] as [$icon,$color,$name,$desc]): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px;background:var(--s2);border-radius:8px;">
                        <i class="fas <?php echo $icon; ?>" style="color:<?php echo $color; ?>;width:16px;text-align:center;"></i>
                        <div>
                            <div style="font-size:.8rem;font-weight:600;"><?php echo $name; ?></div>
                            <div style="font-size:.72rem;color:var(--muted);"><?php echo $desc; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border3);">
                    <div style="font-size:.75rem;color:var(--muted);line-height:1.7;">
                        <i class="fas fa-info-circle" style="color:var(--gold);margin-right:4px;"></i>
                        <strong style="color:var(--text);">Free</strong> — visible to all logged-in students<br>
                        <i class="fas fa-info-circle" style="color:var(--gold);margin-right:4px;"></i>
                        <strong style="color:var(--text);">Paid</strong> — hidden until you grant access per student
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function handleFileChange(input) {
    const display = document.getElementById('fileNameDisplay');
    const icon    = document.getElementById('uploadIcon');
    if (input.files && input.files[0]) {
        const f    = input.files[0];
        const size = (f.size / 1024 / 1024).toFixed(1);
        display.style.display = 'block';
        display.innerHTML = `<i class="fas fa-check-circle" style="color:var(--green);margin-right:4px;"></i>${f.name} <span style="color:var(--muted)">(${size} MB)</span>`;
        icon.innerHTML = '<i class="fas fa-file-check" style="color:var(--green);"></i>';
    }
}

function updateTypeUI() {
    const isFree = document.querySelector('input[name="type"][value="free"]').checked;
    document.getElementById('lbl-free').style.borderColor = isFree ? 'var(--green)' : 'var(--border3)';
    document.getElementById('lbl-paid').style.borderColor = !isFree ? 'var(--gold)' : 'var(--border3)';
}
</script>

<?php include '_layout_end.php'; ?>