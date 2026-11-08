<?php
require 'includes/init.php';

$errors = [];
$username = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // Server-side validation
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 letters, numbers or underscore.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Duplicate check (prepared statement)
    if (!$errors) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Username or email is already taken.';
        }
        $stmt->close();
    }

    // Insert (role is NOT read from the form; DB default = student)
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $username, $email, $hash);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Account created. You can now log in.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

require 'includes/header.php';
?>
<h2>Register</h2>
<?php foreach ($errors as $err): ?>
  <div class="msg error"><?= e($err) ?></div>
<?php endforeach; ?>
<form method="post">
  <label>Username</label>
  <input name="username" value="<?= e($username) ?>" required>
  <label>Email</label>
  <input type="email" name="email" value="<?= e($email) ?>" required>
  <label>Password</label>
  <input type="password" name="password" required>
  <label>Confirm password</label>
  <input type="password" name="confirm" required>
  <button type="submit">Create account</button>
</form>
<?php require 'includes/footer.php'; ?>
