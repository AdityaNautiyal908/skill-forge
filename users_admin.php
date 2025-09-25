<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db_mysql.php';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = isset($_POST['uid']) ? (int)$_POST['uid'] : 0;
    if ($uid > 0 && $uid !== (int)$_SESSION['user_id']) { // prevent self-delete
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
  <style>
    body { margin:0; color:white; min-height:100vh; background: radial-gradient(1200px 600px at 10% 10%, rgba(76,91,155,0.35), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(60,70,123,0.35), transparent 60%), linear-gradient(135deg, #171b30, #20254a 55%, #3c467b); }
    .panel { background: linear-gradient(180deg, rgba(60,70,123,0.42), rgba(60,70,123,0.18)); border:1px solid rgba(255,255,255,0.14); border-radius:16px; }
    .navbar { background: rgba(10,12,28,0.45) !important; border-bottom:1px solid rgba(255,255,255,0.12); }
    table { color:#fff; }
    table td, table th { border-color: rgba(255,255,255,0.18) !important; }
  </style>
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
              <td><?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>
              <td><?= htmlspecialchars($u['created_at']) ?></td>
              <td>
                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                  <form method="POST" action="users_admin.php" onsubmit="return confirm('Delete this user? This cannot be undone.');" style="display:inline">
                    <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Delete</button>
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
</body>
</html>


