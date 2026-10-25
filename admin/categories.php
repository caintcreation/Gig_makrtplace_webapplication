<?php
// admin/categories.php - Manage Marketplace Categories
require_once __DIR__ . '/../includes/functions.php';

$adminNav = 'categories';
$adminTitle = 'Categories Management';
$db = getDb();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $sort = (int)($_POST['sort_order'] ?? 0);

            if (empty($name)) {
                $errors[] = 'Category name cannot be empty.';
            } else {
                $slug = slugify($name);
                $ins = $db->prepare("INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)");
                try {
                    $ins->execute([$name, $slug, $desc, $sort]);
                    logAudit($_SESSION['user_id'], 'category_created', 'Created category: ' . $name);
                    setFlash('success', 'Category added successfully!');
                    header('Location: categories.php');
                    exit;
                } catch (Exception $e) {
                    $errors[] = 'A category with that name or slug already exists.';
                }
            }
        } elseif ($action === 'delete') {
            $catId = (int)($_POST['category_id'] ?? 0);
            if ($catId > 0) {
                // Check if any platforms use this category
                $inUse = (int)$db->prepare("SELECT COUNT(*) FROM platforms WHERE category_id = ?")->execute([$catId]) ? $db->query("SELECT COUNT(*) FROM platforms WHERE category_id = {$catId}")->fetchColumn() : 0;
                if ($inUse > 0) {
                    setFlash('error', "Cannot delete this category because {$inUse} platforms are assigned to it.");
                } else {
                    $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$catId]);
                    logAudit($_SESSION['user_id'], 'category_deleted', 'Deleted category ID: ' . $catId);
                    setFlash('info', 'Category removed.');
                }
                header('Location: categories.php');
                exit;
            }
        }
    }
}

$categories = $db->query("
    SELECT c.*, COUNT(p.id) AS platforms_count 
    FROM categories c
    LEFT JOIN platforms p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.sort_order ASC
")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="page-title-row">
    <div>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Marketplace Categories</h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Organize verified platforms by digital task types and job families.
        </p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="padding-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Categories List -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Slug</th>
                    <th>Sort Order</th>
                    <th>Platforms</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>
                            <strong><?= e($cat['name']) ?></strong>
                            <?php if (!empty($cat['description'])): ?>
                                <div style="font-size: 0.78rem; color: var(--text-secondary);"><?= e($cat['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><code><?= e($cat['slug']) ?></code></td>
                        <td><?= (int)$cat['sort_order'] ?></td>
                        <td><strong><?= (int)$cat['platforms_count'] ?></strong></td>
                        <td style="text-align: right;">
                            <form method="POST" onsubmit="return confirm('Delete this category?');" style="display: inline;">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: #F8B4B4;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Create Category Form -->
    <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem;">
        <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--divider);">
            Add New Category
        </h2>

        <form method="POST" action="categories.php">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label class="form-label" for="catName">Category Name *</label>
                <input type="text" id="catName" name="name" required class="form-control" placeholder="e.g. Swahili Transcription">
            </div>

            <div class="form-group">
                <label class="form-label" for="catDesc">Description</label>
                <textarea id="catDesc" name="description" rows="2" class="form-control" placeholder="Brief summary of opportunities in this group..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="catSort">Display Order</label>
                <input type="number" id="catSort" name="sort_order" class="form-control" value="0">
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 1rem;">
                Create Category
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
