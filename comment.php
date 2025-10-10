<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_text = trim($_POST['comment']);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    
    // Debug: Log session data
    error_log("Comment submission attempt - User ID: " . $_SESSION['user_id'] . ", Username: " . $_SESSION['username']);
    
    if (empty($comment_text)) {
        $error = "Please write a comment before submitting.";
    } elseif (strlen($comment_text) < 10) {
        $error = "Comment must be at least 10 characters long.";
    } elseif (strlen($comment_text) > 500) {
        $error = "Comment must be less than 500 characters.";
    } else {
        try {
            $coll = getCollection('coding_platform', 'comments');
            
            // Check if user already submitted a comment (Assuming user_id is the primary key for comments)
            $query = new MongoDB\Driver\Query(['user_id' => $_SESSION['user_id']]);
            $existing = $coll['manager']->executeQuery($coll['db'] . ".comments", $query)->toArray();
            
            if (count($existing) > 0) {
                $error = "You have already submitted a comment. You can only submit one comment per account.";
            } else {
                // Insert new comment
                $bulk = new MongoDB\Driver\BulkWrite;
                $comment_doc = [
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'comment' => $comment_text,
                    'rating' => $rating,
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'status' => 'approved' // Auto-approve for now
                ];
                
                $bulk->insert($comment_doc);
                $result = $coll['manager']->executeBulkWrite($coll['db'] . ".comments", $bulk);
                
                if ($result->getInsertedCount() > 0) {
                    $message = "Thank you for your feedback! Your comment has been submitted successfully.";
                    // Clear the posted comment text after successful submission
                    unset($_POST['comment']);
                } else {
                    $error = "Failed to submit comment. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
            // Debug: Log the full error for troubleshooting
            error_log("Comment submission error: " . $e->getMessage() . " - " . $e->getTraceAsString());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Leave Feedback</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets\css\comment.css">
</head>
<body>
<div class="stars"></div>
<canvas id="webComment" class="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">SkillForge</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link text-white">Hello, <?= $_SESSION['username'] ?><?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?> <span class="badge bg-warning text-dark">Admin</span><?php endif; ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 section">
    <div class="comment-card p-5 mx-auto" style="max-width: 700px;">
        <span class="brand">SkillForge</span>
        <h2 class="title">Share Your Feedback</h2>
        <p class="subtitle">Help us improve SkillForge by sharing your thoughts and experience!</p>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['debug'])): ?>
            <div class="alert alert-info">
                <strong>Debug Info:</strong><br>
                User ID: <?= $_SESSION['user_id'] ?? 'Not set' ?><br>
                Username: <?= $_SESSION['username'] ?? 'Not set' ?><br>
                Session Status: <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label class="form-label">Rate your experience (optional)</label>
                <div class="rating-stars">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" name="rating" id="rating-input" value="<?= isset($_POST['rating']) ? (int)$_POST['rating'] : 0 ?>">
            </div>
            
            <div class="mb-4">
                <label for="comment" class="form-label">Your Comment</label>
                <textarea 
                    name="comment" 
                    id="comment" 
                    class="form-control" 
                    placeholder="Tell us what you think about SkillForge. What did you like? What could be improved? Your feedback helps us make the platform better for everyone!"
                    maxlength="500"
                    required
                ><?= isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : '' ?></textarea>
                <div class="char-count">
                    <span id="char-count">0</span>/500 characters
                </div>
            </div>
            
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary-glow">Submit Feedback</button>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </div>
</div>

<script src="assets\js\comment.js"></script>
</body>
</html>