<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

require_once "../config/db_mongo.php";

$message = isset($_GET['message']) ? $_GET['message'] : "";
$error = isset($_GET['error']) ? $_GET['error'] : "";

// Handle delete action
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['comment_id'])) {
    try {
        $coll = getCollection('coding_platform', 'comments');
        $bulk = new MongoDB\Driver\BulkWrite;
        // NOTE: Using soft delete by setting 'deleted' flag
        $bulk->update(
            ['_id' => new MongoDB\BSON\ObjectId($_POST['comment_id'])],
            ['$set' => [
                'deleted' => true,
                'deleted_at' => new MongoDB\BSON\UTCDateTime(),
                'deleted_by' => $_SESSION['user_id']
            ]],
            ['upsert' => false]
        );
        $coll['manager']->executeBulkWrite($coll['db'] . '.comments', $bulk);
        header("Location: admin_feedback.php?message=" . urlencode("Comment deleted successfully (soft-deleted)."));
        exit;
    } catch (Exception $e) {
        $error = "Failed to delete comment: " . $e->getMessage();
        header("Location: admin_feedback.php?error=" . urlencode($error));
        exit;
    }
}

// Fetch all comments (including deleted ones for admin view)
try {
    $coll = getCollection('coding_platform', 'comments');
    $query = new MongoDB\Driver\Query([], ['sort' => ['created_at' => -1]]);
    $comments = $coll['manager']->executeQuery($coll['db'] . '.comments', $query)->toArray();
} catch (Exception $e) {
    $error = "Failed to fetch comments: " . $e->getMessage();
    $comments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Admin Feedback Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/admin_feedback.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="stars"></div>
<canvas id="webAdmin" class="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="../public/dashboard.php">SkillForge</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link text-white">Hello, <?= $_SESSION['username'] ?> <span class="badge bg-warning text-dark">Admin</span></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../public/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../public/submissions.php">Submissions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users_admin.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="admin_feedback.php">Feedback</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../core/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 section">
    <div class="comment-card p-4">
        <span class="brand">Admin Panel</span>
        <h2 class="title">Feedback Management</h2>
        <p class="subtitle">Manage user feedback and comments</p>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>All Comments (<?= count($comments) ?>)</h4>
            <a href="../public/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (empty($comments)): ?>
            <div class="text-center py-5">
                <p class="text-muted">No comments found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Comment</th>
                            <th>Rating</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                            <tr class="<?= isset($comment->deleted) && $comment->deleted ? 'comment-deleted' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($comment->username) ?></strong><br>
                                    <small class="text-muted">ID: <?= htmlspecialchars((string)$comment->user_id) ?></small>
                                </td>
                                <td>
                                    <div style="max-width: 300px; word-wrap: break-word;">
                                        <?= htmlspecialchars($comment->comment) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($comment->rating > 0): ?>
                                        <div class="rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span style="color: <?= $i <= $comment->rating ? '#ffd700' : '#666' ?>">★</span>
                                            <?php endfor; ?>
                                            <small class="text-muted">(<?= $comment->rating ?>/5)</small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No rating</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('M j, Y g:i A', $comment->created_at->toDateTime()->getTimestamp()) ?>
                                </td>
                                <td>
                                    <?php if (isset($comment->deleted) && $comment->deleted): ?>
                                        <span class="badge bg-danger">Deleted</span>
                                        <?php if (isset($comment->deleted_at)): ?>
                                            <br><small class="text-muted"><?= date('M j, Y', $comment->deleted_at->toDateTime()->getTimestamp()) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <?php if (!isset($comment->deleted) || !$comment->deleted): ?>
                                            <button class="btn btn-warning btn-sm edit-comment-btn" 
                                                    data-comment-id="<?= htmlspecialchars((string)$comment->_id) ?>"
                                                    data-comment-text="<?= htmlspecialchars($comment->comment, ENT_QUOTES) ?>"
                                                    data-comment-rating="<?= $comment->rating ?>">
                                                Edit
                                            </button>
                                            <form method="POST" class="delete-form" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="comment_id" value="<?= (string)$comment->_id ?>">
                                                <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">No actions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="editCommentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: rgba(60,70,123,0.95); border: 1px solid rgba(255,255,255,0.2);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                <h5 class="modal-title" style="color: white;">Edit Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <form method="POST" action="admin_edit_comment.php">
                <div class="modal-body">
                    <input type="hidden" name="comment_id" id="edit_comment_id">
                    <div class="mb-3">
                        <label class="form-label" style="color: white;">Comment</label>
                        <textarea name="comment" id="edit_comment_text" class="form-control" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white;" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: white;">Rating</label>
                        <select name="rating" id="edit_rating" class="form-select" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            <option value="0">No rating</option>
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.2);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-glow">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../public/assets/js/admin_feedback.js"></script>
</body>
</html>