<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$material_id = (int)($_GET['material_id'] ?? 0);
if (!$material_id) { header("Location: manage_materials.php"); exit(); }

// Fetch material
$mStmt = $conn->prepare("SELECT * FROM material WHERE material_id=? AND type='paid'");
$mStmt->bind_param("i", $material_id);
$mStmt->execute();
$material = $mStmt->get_result()->fetch_assoc();
if (!$material) { header("Location: manage_materials.php"); exit(); }

$msg = '';

// Grant access
if (isset($_GET['grant'])) {
    $sid = (int)$_GET['grant'];
    $ins = $conn->prepare("INSERT IGNORE INTO material_access (student_id, material_id) VALUES (?,?)");
    $ins->bind_param("ii", $sid, $material_id);
    $ins->execute();
    $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Access granted.</div>';
}

// Revoke access
if (isset($_GET['revoke'])) {
    $sid = (int)$_GET['revoke'];
    $del = $conn->prepare("DELETE FROM material_access WHERE student_id=? AND material_id=?");
    $del->bind_param("ii", $sid, $material_id);
    $del->execute();
    $msg = '<div class="adm-alert adm-alert-warn"><i class="fas fa-minus-circle"></i> Access revoked.</div>';
}

// Grant all
if (isset($_GET['grant_all'])) {
    $all = $conn->query("SELECT student_id FROM student");
    while ($s = $all->fetch_assoc()) {
        $ins = $conn->prepare("INSERT IGNORE INTO material_access (student_id, material_id) VALUES (?,?)");
        $ins->bind_param("ii", $s['student_id'], $material_id);
        $ins->execute();
    }
    $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Access granted to all students.</div>';
}

// Revoke all
if (isset($_GET['revoke_all'])) {
    $conn->prepare("DELETE FROM material_access WHERE material_id=?")->execute() || true;
    $del = $conn->prepare("DELETE FROM material_access WHERE material_id=?");
    $del->bind_param("i", $material_id);
    $del->execute();
    $msg = '<div class="adm-alert adm-alert-warn"><i class="fas fa-minus-circle"></i> All access revoked.</div>';
}

// Get all students with access status
$search = trim($_GET['search'] ?? '');
$sWhere = $search ? "AND (s.name LIKE '%" . $conn->real_escape_string($search) . "%' OR s.email LIKE '%" . $conn->real_escape_string($search) . "%')" : '';

$students = $conn->query("SELECT s.*,
    (SELECT COUNT(*) FROM material_access ma WHERE ma.student_id=s.student_id AND ma.material_id=$material_id) as has_access
    FROM student s WHERE 1=1 $sWhere ORDER BY s.name ASC");

$granted_count = $conn->query("SELECT COUNT(*) as c FROM material_access WHERE material_id=$material_id")->fetch_assoc()['c'];
$total_students = $conn->query("SELECT COUNT(*) as c FROM student")->fetch_assoc()['c'];

$page_title = "Grant Access";
$active_nav = "manage_materials";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Manage Access</h1>
        <p>Grant or revoke access to this paid material</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="manage_materials.php" class="btn-adm btn-adm-ghost">
            <i class="fas fa-arrow-left"></i> Back to Materials
        </a>
    </div>
</div>

<?php echo $msg; ?>

<!-- Material info strip -->
<div style="display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,#131000,#1c1700);border:1px solid rgba(255,215,0,.18);border-radius:14px;padding:18px 22px;margin-bottom:22px;flex-wrap:wrap;">
    <div style="width:44px;height:44px;border-radius:11px;background:rgba(255,215,0,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fas fa-lock" style="color:var(--gold);font-size:1.1rem;"></i>
    </div>
    <div style="flex:1;min-width:0;">
        <div style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;"><?php echo htmlspecialchars($material['title']); ?></div>
        <div style="font-size:.78rem;color:var(--muted);">
            <?php echo htmlspecialchars($material['category']); ?> &nbsp;·&nbsp;
            <?php echo strtoupper(pathinfo($material['file_name'], PATHINFO_EXTENSION)); ?> file
        </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <span class="adm-badge green"><i class="fas fa-user-check"></i> <?php echo $granted_count; ?> / <?php echo $total_students; ?> students</span>
        <a href="?material_id=<?php echo $material_id; ?>&grant_all=1"
           class="btn-adm btn-adm-ghost btn-adm-sm"
           onclick="return confirm('Grant access to ALL students?')">
            <i class="fas fa-users"></i> Grant All
        </a>
        <a href="?material_id=<?php echo $material_id; ?>&revoke_all=1"
           class="btn-adm btn-adm-danger btn-adm-sm"
           onclick="return confirm('Revoke access from ALL students?')">
            <i class="fas fa-user-slash"></i> Revoke All
        </a>
    </div>
</div>

<!-- Search -->
<div style="margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:10px;max-width:380px;">
        <input type="hidden" name="material_id" value="<?php echo $material_id; ?>">
        <div class="form-input-icon" style="flex:1;">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-input"
                   placeholder="Search students..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="btn-adm btn-adm-ghost">Search</button>
        <?php if ($search): ?>
        <a href="?material_id=<?php echo $material_id; ?>" class="btn-adm btn-adm-ghost"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>
</div>

<div class="adm-card">
    <div class="adm-card-header">
        <h2><i class="fas fa-users" style="color:var(--gold);margin-right:8px;font-size:.85rem;"></i>Students</h2>
        <div style="display:flex;gap:8px;">
            <span class="adm-badge green"><?php echo $granted_count; ?> have access</span>
            <span class="adm-badge red"><?php echo $total_students - $granted_count; ?> no access</span>
        </div>
    </div>
    <div class="adm-card-body">
        <?php if ($students->num_rows > 0): ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($s = $students->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:50%;
                                background:<?php echo $s['has_access'] ? 'rgba(34,197,94,.15)' : 'var(--s3)'; ?>;
                                border:1px solid <?php echo $s['has_access'] ? 'rgba(34,197,94,.3)' : 'var(--border3)'; ?>;
                                display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.78rem;
                                color:<?php echo $s['has_access'] ? 'var(--green)' : 'var(--muted)'; ?>;flex-shrink:0;">
                                <?php echo strtoupper(substr($s['name'],0,1)); ?>
                            </div>
                            <span style="font-weight:600;font-size:.87rem;"><?php echo htmlspecialchars($s['name']); ?></span>
                        </div>
                    </td>
                    <td style="color:var(--muted);font-size:.82rem;"><?php echo htmlspecialchars($s['email']); ?></td>
                    <td>
                        <?php if ($s['has_access']): ?>
                        <span class="adm-badge green"><i class="fas fa-check-circle"></i> Has Access</span>
                        <?php else: ?>
                        <span class="adm-badge" style="background:var(--s3);color:var(--muted2);border-color:var(--border3);">
                            <i class="fas fa-times-circle"></i> No Access
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['has_access']): ?>
                        <a href="?material_id=<?php echo $material_id; ?>&revoke=<?php echo $s['student_id']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"
                           class="btn-adm btn-adm-danger btn-adm-sm"
                           onclick="return confirm('Revoke access for <?php echo addslashes($s['name']); ?>?')">
                            <i class="fas fa-user-minus"></i> Revoke
                        </a>
                        <?php else: ?>
                        <a href="?material_id=<?php echo $material_id; ?>&grant=<?php echo $s['student_id']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"
                           class="btn-adm btn-adm-primary btn-adm-sm">
                            <i class="fas fa-user-plus"></i> Grant
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="adm-empty">
            <i class="fas fa-users"></i>
            <p>No students found.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '_layout_end.php'; ?>