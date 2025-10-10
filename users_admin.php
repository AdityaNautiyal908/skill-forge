<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db_mysql.php';
// NOTE: For full cleanup, you would include db_mongo.php and execute deletion logic here.

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = isset($_POST['uid']) ? (int)$_POST['uid'] : 0;
    if ($uid > 0 && $uid !== (int)$_SESSION['user_id']) { // prevent self-delete
        
        // --- MongoDB Cleanup would go here (e.g., deleting submissions, preferences) ---
        
        // Delete user from MySQL
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();
        header('Location: users_admin.php');
        exit;
    }
}

$users = [];
$res = $conn->query('SELECT id, username, email, role, created_at FROM users ORDER BY id DESC');
while ($row = $res->fetch_assoc()) { $users[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin — Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets\css\users_admin.css">
  
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="dashboard.php">SkillForge</a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link active" href="users_admin.php">Users</a></li>
          <li class="nav-item"><a class="nav-link" href="submissions.php">Submissions</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_feedback.php">Feedback</a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-4 panel p-3">
    <h3 class="mb-3">Users</h3>
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
            <th style="width:120px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td class="user-id"><?= (int)$u['id'] ?></td>
              <td class="user-username"><?= htmlspecialchars($u['username']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td>
                  <?php if ($u['role'] === 'admin'): ?>
                      <span class="badge bg-warning text-dark"><?= htmlspecialchars($u['role']) ?></span>
                  <?php else: ?>
                      <?= htmlspecialchars($u['role']) ?>
                  <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($u['created_at']) ?></td>
              <td>
                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                  <form method="POST" action="users_admin.php" class="delete-user-form" style="display:inline">
                    <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                    <button class="btn btn-sm btn-danger delete-btn" 
                            type="submit"
                            data-username="<?= htmlspecialchars($u['username']) ?>"
                            data-uid="<?= (int)$u['id'] ?>">Delete</button>
                  </form>
                <?php else: ?>
                  <span class="badge bg-secondary">You</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets\js\users_admin.js"></script>
</body>
</html>