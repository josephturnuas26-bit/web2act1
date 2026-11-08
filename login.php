
<?php
require 'includes/init.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Get user information, including lockout status
    $stmt = $conn->prepare("
        SELECT id, username, password_hash, role,
               failed_login_attempts, locked_until,
               (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked
        FROM users
        WHERE username = ?
    ");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {

        // Check if account is currently locked
        if ((int)$user['is_locked'] === 1) {
            $error = 'Account locked. Please try again after 15 minutes.';

        } else {

            // Reset attempts if the previous lock has expired
            if (
                $user['locked_until'] !== null &&
                strtotime($user['locked_until']) <= time()
            ) {
                $reset = $conn->prepare("
                    UPDATE users
                    SET failed_login_attempts = 0,
                        locked_until = NULL
                    WHERE id = ?
                ");
                $reset->bind_param('i', $user['id']);
                $reset->execute();
                $reset->close();
            }

            // Correct password
            if (password_verify($password, $user['password_hash'])) {

                $reset = $conn->prepare("
                    UPDATE users
                    SET failed_login_attempts = 0,
                        locked_until = NULL
                    WHERE id = ?
                ");
                $reset->bind_param('i', $user['id']);
                $reset->execute();
                $reset->close();

                session_regenerate_id(true);

                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;

            } else {

                // Increment failed attempts and lock at 5
                $update = $conn->prepare("
                    UPDATE users
                    SET failed_login_attempts = failed_login_attempts + 1,
                        locked_until = CASE
                            WHEN failed_login_attempts + 1 >= 5
                            THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                            ELSE NULL
                        END
                    WHERE id = ?
                      AND (locked_until IS NULL OR locked_until <= NOW())
                ");
                $update->bind_param('i', $user['id']);
                $update->execute();
                $update->close();

                // Check updated attempt count
                $check = $conn->prepare("
                    SELECT failed_login_attempts, locked_until,
                           (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked
                    FROM users
                    WHERE id = ?
                ");
                $check->bind_param('i', $user['id']);
                $check->execute();
                $status = $check->get_result()->fetch_assoc();
                $check->close();

                if ((int)$status['is_locked'] === 1) {
                    $error = 'Too many failed attempts. Your account is locked for 15 minutes.';
                } else {
                    $remaining = 5 - (int)$status['failed_login_attempts'];
                    $error = 'Invalid username or password. '
                           . $remaining . ' attempt(s) remaining.';
                }
            }
        }

    } else {
        $error = 'Invalid username or password.';
    }
}

require 'includes/header.php';
?>

<h2>Login</h2>

<?php if ($error): ?>
    <div class="msg error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">
    <label>Username</label>
    <input name="username" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit">Log in</button>
</form>

<?php require 'includes/footer.php'; ?>
