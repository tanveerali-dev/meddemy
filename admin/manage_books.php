<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$filter_cat = trim($_GET['cat'] ?? '');
$search     = trim($_GET['search'] ?? '');

$where = 'WHERE 1=1';
if ($filter_cat) $where .= " AND category='" . $conn->real_escape_string($filter_cat) . "'";
if ($search)     $where .= " AND (title LIKE '%" . $conn->real_escape_string($search) . "%' OR author LIKE '%" . $conn->real_escape_string($search) . "%')";

$books = $conn->query("SELECT * FROM book $where ORDER BY category ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$all_cats = $conn->query("SELECT DISTINCT category FROM book ORDER BY category ASC")->fetch_all(MYSQLI_ASSOC);
$all_cats = array_column($all_cats, 'category');

$total = $conn->query("SELECT COUNT(*) as c FROM book")->fetch_assoc()['c'];

$msg = '';
if (isset($_GET['added']))   $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Book added successfully!</div>';
if (isset($_GET['updated'])) $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Book updated successfully!</div>';
if (isset($_GET['deleted'])) $msg = '<div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Book deleted.</div>';

// Group fetched books by category for display
$grouped = [];
foreach ($books as $b) $grouped[$b['category']][] = $b;

$page_title = "Manage Books";
$active_nav = "manage_books";
include '_layout.php';
?>

<div class="adm-page-header">
    <div class="adm-page-header-left">
        <h1>Books Store</h1>
        <p>Manage books displayed on the public books page</p>
    </div>
    <div class="adm-page-header-actions">
        <a href="add_book.php" class="btn-adm btn-adm-primary">
            <i class="fas fa-plus"></i> Add Book
        </a>
    </div>
</div>

<?php echo $msg; ?>

<!-- Stats + filters -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:20px;">
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:var(--s2);border:1px solid var(--border2);border-radius:10px;font-size:.82rem;font-weight:600;">
            <i class="fas fa-book" style="color:var(--gold);"></i> <?php echo $total; ?> Total Books
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:var(--s2);border:1px solid var(--border2);border-radius:10px;font-size:.82rem;font-weight:600;">
            <i class="fas fa-layer-group" style="color:var(--blue);"></i> <?php echo count($all_cats); ?> Categories
        </div>
    </div>
    <a href="../books.php" target="_blank" class="btn-adm btn-adm-ghost btn-adm-sm">
        <i class="fas fa-external-link-alt"></i> View Public Page
    </a>
</div>

<!-- Search + category filter -->
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:10px;flex:1;min-width:0;flex-wrap:wrap;">
        <div class="form-input-icon" style="flex:1;min-width:180px;">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-input" placeholder="Search by title or author..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select name="cat" class="form-select" style="width:auto;">
            <option value="">— All Categories —</option>
            <?php foreach ($all_cats as $c): ?>
            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filter_cat===$c?'selected':''; ?>>
                <?php echo htmlspecialchars($c); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-adm btn-adm-ghost">Filter</button>
        <?php if ($search || $filter_cat): ?>
        <a href="manage_books.php" class="btn-adm btn-adm-ghost"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($books)): ?>
<div class="adm-empty">
    <i class="fas fa-book-open"></i>
    <p><?php echo ($search||$filter_cat) ? 'No books match your filters.' : 'No books yet. Add your first book!'; ?></p>
    <a href="add_book.php" class="btn-adm btn-adm-primary" style="margin-top:14px;"><i class="fas fa-plus"></i> Add Book</a>
</div>
<?php else: ?>

<?php foreach ($grouped as $cat => $cat_books): ?>
<div class="adm-card" style="margin-bottom:18px;">
    <div class="adm-card-header">
        <h2>
            <i class="fas fa-book" style="color:var(--gold);margin-right:8px;font-size:.82rem;"></i>
            <?php echo htmlspecialchars($cat); ?>
        </h2>
        <span class="adm-badge gold"><?php echo count($cat_books); ?> book<?php echo count($cat_books)!=1?'s':''; ?></span>
    </div>
    <div class="adm-card-body" style="padding:0;">
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Cover</th>
                        <th>Title / Author</th>
                        <th>Real Price</th>
                        <th>Discount</th>
                        <th>Savings</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cat_books as $book):
                    $hasDisc = $book['discount_price'] && $book['discount_price'] < $book['real_price'];
                    $discPct = $hasDisc ? round((1-$book['discount_price']/$book['real_price'])*100) : 0;
                ?>
                <tr>
                    <!-- Cover thumbnail -->
                    <td>
                        <div style="width:44px;height:58px;border-radius:8px;overflow:hidden;background:var(--s3);border:1px solid var(--border3);">
                            <?php if ($book['image']): ?>
                            <img src="../assets/uploads/books/<?php echo htmlspecialchars($book['image']); ?>"
                                 style="width:100%;height:100%;object-fit:cover;display:block;"
                                 onerror="this.style.display='none'">
                            <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted2);">
                                <i class="fas fa-book" style="font-size:.9rem;"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <!-- Title + author -->
                    <td>
                        <div style="font-weight:700;font-size:.87rem;"><?php echo htmlspecialchars($book['title']); ?></div>
                        <?php if ($book['author']): ?>
                        <div style="font-size:.74rem;color:var(--muted);margin-top:2px;">
                            <i class="fas fa-user-edit" style="font-size:.6rem;margin-right:3px;"></i>
                            <?php echo htmlspecialchars($book['author']); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <!-- Real price -->
                    <td style="font-size:.85rem;">
                        <span style="<?php echo $hasDisc?'text-decoration:line-through;color:var(--muted);':'font-weight:700;'; ?>">
                            Rs. <?php echo number_format($book['real_price'],0); ?>
                        </span>
                    </td>
                    <!-- Discount price -->
                    <td>
                        <?php if ($hasDisc): ?>
                        <span style="color:var(--gold);font-weight:800;font-size:.88rem;">
                            Rs. <?php echo number_format($book['discount_price'],0); ?>
                        </span>
                        <?php else: ?>
                        <span style="color:var(--muted2);font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <!-- Savings % -->
                    <td>
                        <?php if ($hasDisc): ?>
                        <span class="adm-badge red"><?php echo $discPct; ?>% OFF</span>
                        <?php else: ?>
                        <span style="color:var(--muted2);font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <!-- Date -->
                    <td style="font-size:.75rem;color:var(--muted);">
                        <?php echo date('d M Y', strtotime($book['created_at'])); ?>
                    </td>
                    <!-- Actions -->
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="edit_book.php?id=<?php echo $book['book_id']; ?>"
                               class="btn-icon btn-icon-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete_book.php?id=<?php echo $book['book_id']; ?>"
                               class="btn-icon btn-icon-danger" title="Delete"
                               onclick="return confirm('Delete \'<?php echo addslashes($book['title']); ?>\'?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include '_layout_end.php'; ?>