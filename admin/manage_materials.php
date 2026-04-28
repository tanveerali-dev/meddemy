<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$filter = $_GET['type'] ?? 'all';
$where  = in_array($filter, ['free','paid']) ? "WHERE m.type='$filter'" : '';

$materials = $conn->query("SELECT m.*,
    (SELECT COUNT(*) FROM material_access ma WHERE ma.material_id = m.material_id) as access_count
    FROM material m $where ORDER BY m.created_at DESC");

$total_free = $conn->query("SELECT COUNT(*) as c FROM material WHERE type='free'")->fetch_assoc()['c'];
$total_paid = $conn->query("SELECT COUNT(*) as c FROM material WHERE type='paid'")->fetch_assoc()['c'];

$msg = '';
if (isset($_GET['deleted'])) $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Material deleted.</div>';
if (isset($_GET['added']))   $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Material uploaded successfully.</div>';

$page_title = "Manage Materials";
$active_nav = "manage_materials";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Manage Materials</h1>
        <p>Upload and manage free &amp; paid books, PDFs and resources</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="add_material.php" class="btn-adm btn-adm-primary">
            <i class="fas fa-upload"></i> Upload Material
        </a>
    </div>
</div>

<?php echo $msg; ?>

<!-- Stats + Filter tabs -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">

    <!-- Stats pills -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;background:var(--s2);border:1px solid var(--border2);border-radius:10px;padding:8px 16px;">
            <i class="fas fa-unlock" style="color:var(--green);font-size:.85rem;"></i>
            <span style="font-size:.82rem;font-weight:600;"><?php echo $total_free; ?> Free</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;background:var(--s2);border:1px solid var(--border2);border-radius:10px;padding:8px 16px;">
            <i class="fas fa-lock" style="color:var(--gold);font-size:.85rem;"></i>
            <span style="font-size:.82rem;font-weight:600;"><?php echo $total_paid; ?> Paid</span>
        </div>
    </div>

    <!-- Filter tabs -->
    <div style="display:flex;background:var(--s2);border:1px solid var(--border2);border-radius:10px;padding:4px;gap:3px;">
        <?php foreach(['all'=>'All','free'=>'Free','paid'=>'Paid'] as $k=>$label): ?>
        <a href="?type=<?php echo $k; ?>"
           style="padding:6px 16px;border-radius:8px;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .2s;
                  <?php echo $filter===$k
                    ? 'background:var(--s4);color:var(--text);'
                    : 'color:var(--muted);'; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="adm-card">
    <div class="adm-card-header">
        <h2><i class="fas fa-folder-open" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>
            Materials
        </h2>
        <span class="adm-badge gold"><?php echo $materials->num_rows; ?> files</span>
    </div>
    <div class="adm-card-body">
        <?php if ($materials->num_rows > 0): ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>File</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Access</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $materials->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--muted);font-size:.78rem;"><?php echo $row['material_id']; ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <!-- File type icon -->
                            <div style="width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                <?php
                                $ext = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
                                $iconStyle = match($ext) {
                                    'pdf'  => 'background:rgba(255,77,77,.12);color:#ff4d4d;',
                                    'doc','docx' => 'background:rgba(59,130,246,.12);color:#3b82f6;',
                                    'ppt','pptx' => 'background:rgba(249,115,22,.12);color:#f97316;',
                                    default => 'background:rgba(255,255,255,.07);color:var(--muted);',
                                };
                                echo $iconStyle;
                                ?>">
                                <i class="fas <?php
                                echo match($ext) {
                                    'pdf'  => 'fa-file-pdf',
                                    'doc','docx' => 'fa-file-word',
                                    'ppt','pptx' => 'fa-file-powerpoint',
                                    default => 'fa-file',
                                };
                                ?>"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.87rem;"><?php echo htmlspecialchars($row['title']); ?></div>
                                <?php if ($row['description']): ?>
                                <div style="font-size:.73rem;color:var(--muted);max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-size:.8rem;color:var(--muted);"><?php echo htmlspecialchars($row['category']); ?></span>
                    </td>
                    <td>
                        <?php if ($row['type'] === 'free'): ?>
                        <span class="adm-badge green"><i class="fas fa-unlock"></i> Free</span>
                        <?php else: ?>
                        <span class="adm-badge gold"><i class="fas fa-lock"></i> Paid</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['type'] === 'free'): ?>
                        <span style="font-size:.78rem;color:var(--muted);">All students</span>
                        <?php else: ?>
                        <a href="grant_material_access.php?material_id=<?php echo $row['material_id']; ?>"
                           class="adm-badge blue" style="text-decoration:none;cursor:pointer;">
                            <i class="fas fa-users"></i> <?php echo $row['access_count']; ?> granted
                        </a>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--muted);font-size:.78rem;">
                        <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="../assets/uploads/materials/<?php echo urlencode($row['file_name']); ?>"
                               target="_blank"
                               class="btn-icon" style="background:rgba(34,197,94,.1);color:var(--green);border-radius:8px;"
                               title="Download / Preview">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($row['type'] === 'paid'): ?>
                            <a href="grant_material_access.php?material_id=<?php echo $row['material_id']; ?>"
                               class="btn-icon btn-icon-edit" title="Manage Access">
                                <i class="fas fa-user-shield"></i>
                            </a>
                            <?php endif; ?>
                            <a href="delete_material.php?id=<?php echo $row['material_id']; ?>"
                               class="btn-icon btn-icon-danger" title="Delete"
                               onclick="return confirm('Delete \'<?php echo addslashes($row['title']); ?>\'?')">
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
            <i class="fas fa-folder-open"></i>
            <p>No materials uploaded yet<?php echo $filter!='all' ? " ($filter)" : ''; ?>.</p>
            <a href="add_material.php" class="btn-adm btn-adm-primary" style="margin-top:14px;">
                <i class="fas fa-upload"></i> Upload First Material
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '_layout_end.php'; ?>