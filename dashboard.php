<?php
require 'includes/init.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

require 'includes/header.php';
?>
<h2>Welcome, <?= e($_SESSION['username']) ?>!</h2>
<p>Your role: <strong><?= e($_SESSION['role']) ?></strong></p>
<?php require 'includes/footer.php'; ?>
