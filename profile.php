<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mysql.php";
require_once "config/db_mongo.php";
// use fully-qualified MongoDB classes inline to satisfy linters

$userId = (int)$_SESSION['user_id'];
$message = '';
$success = '';

// Fetch current user data from MySQL
$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userRes = $stmt->get_result();
$user = $userRes->fetch_assoc();
if (!$user) { die('User not found'); }

// Fetch preferences from MongoDB
$prefs = [ 'theme' => 'dark', 'animations' => 'on' ];
try {
    $prefColl = getCollection('coding_platform', 'user_prefs');
    $q = new \MongoDB\Driver\Query(['user_id' => $userId]);
    $rows = $prefColl['manager']->executeQuery($prefColl['db'] . '.user_prefs', $q)->toArray();
    if (!empty($rows)) {
        $doc = $rows[0];
        $prefs['theme'] = isset($doc->theme) ? (string)$doc->theme : $prefs['theme'];
        $prefs['animations'] = isset($doc->animations) ? (string)$doc->animations : $prefs['animations'];
    }
} catch (Throwable $e) {
    // ignore and use defaults
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');

        if ($newUsername === '' || $newEmail === '') {
            $message = 'Username and Email are required.';
        } else {
            // Check uniqueness for username/email except current user
            $chk = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1");
            $chk->bind_param("ssi", $newUsername, $newEmail, $userId);
            $chk->execute();
            $dup = $chk->get_result();
            if ($dup->num_rows > 0) {
                $message = 'Username or Email already in use.';
            } else {
                $upd = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $upd->bind_param("ssi", $newUsername, $newEmail, $userId);
                if ($upd->execute()) {
                    $_SESSION['username'] = $newUsername;
                    $user['username'] = $newUsername;
                    $user['email'] = $newEmail;
                    $success = 'Profile updated successfully.';
                } else {
                    $message = 'Failed to update profile.';
                }
            }
        }
    }

    if ($action === 'password') {
        $current = trim($_POST['current_password'] ?? '');
        $new = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if ($new !== $confirm) {
            $message = 'New passwords do not match.';
        } elseif (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/[0-9]/', $new) || !preg_match('/[^A-Za-z0-9]/', $new)) {
            $message = 'Password must be 8+ chars with upper, lower, number, and symbol.';
        } else {
            // Fetch current hash
            $g = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
            $g->bind_param("i", $userId);
            $g->execute();
            $r = $g->get_result();
            $row = $r->fetch_assoc();
            if (!$row || !password_verify($current, $row['password_hash'])) {
                $message = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                $u = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $u->bind_param("si", $hash, $userId);
                if ($u->execute()) {
                    $success = 'Password updated successfully.';
                } else {
                    $message = 'Failed to update password.';
                }
            }
        }
    }

    if ($action === 'preferences') {
        $theme = ($_POST['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
        $animations = ($_POST['animations'] ?? 'on') === 'off' ? 'off' : 'on';

        try {
            $bulk = new \MongoDB\Driver\BulkWrite;
            $bulk->update(
                ['user_id' => $userId],
                ['$set' => [ 'user_id' => $userId, 'theme' => $theme, 'animations' => $animations, 'updated_at' => new \MongoDB\BSON\UTCDateTime() ]],
                ['upsert' => true]
            );
            $prefColl = getCollection('coding_platform', 'user_prefs');
            $prefColl['manager']->executeBulkWrite($prefColl['db'] . '.user_prefs', $bulk);
            $prefs['theme'] = $theme;
            $prefs['animations'] = $animations;
            $success = 'Preferences saved.';
        } catch (Throwable $e) {
            $message = 'Failed to save preferences.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets\css\profile.css"> 
</head>
<body class="<?= $prefs['theme'] === 'light' ? 'light' : 'dark' ?> <?= $prefs['animations'] === 'off' ? 'no-anim' : '' ?>">
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">SkillForge</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><span class="nav-link">Hello, <?= htmlspecialchars($_SESSION['username']) ?></span></li>
        <li class="nav-item"><a class="nav-link active" href="profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="leaderboard.php">Leaderboard</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-4">
  <?php if ($message): ?><div class="alert alert-danger"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="panel p-4">
        <h5 class="mb-3">Profile Information</h5>
        <form method="POST" action="">
          <input type="hidden" name="action" value="profile">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">User Role</label>
            <input type="text" value="<?= htmlspecialchars($user['role'] ?? 'user') ?>" class="form-control" disabled>
          </div>
          <button class="btn btn-primary" type="submit">Save Profile</button>
        </form>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="panel p-4">
        <h5 class="mb-3">Change Password</h5>
        <form method="POST" action="">
          <input type="hidden" name="action" value="password">
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required>
            <div class="form-text text-white-50">Min 8 chars, include upper, lower, number, and symbol.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button class="btn btn-primary" type="submit">Update Password</button>
        </form>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="panel p-4">
        <h5 class="mb-3">Application Preferences</h5>
        <form method="POST" action="">
          <input type="hidden" name="action" value="preferences">
          <div class="mb-3">
            <label class="form-label">Theme</label>
            <select class="form-select" name="theme">
              <option value="dark" <?= $prefs['theme']==='dark'?'selected':'' ?>>Dark</option>
              <option value="light" <?= $prefs['theme']==='light'?'selected':'' ?>>Light</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Animations</label>
            <select class="form-select" name="animations">
              <option value="on" <?= $prefs['animations']==='on'?'selected':'' ?>>On</option>
              <option value="off" <?= $prefs['animations']==='off'?'selected':'' ?>>Off</option>
            </select>
          </div>
          <button class="btn btn-primary" type="submit">Save Preferences</button>
        </form>
        <div class="small text-muted mt-2">These preferences will sync across devices.</div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets\js\profile.js"></script>
</body>
</html>