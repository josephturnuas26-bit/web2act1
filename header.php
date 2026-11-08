<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>WST2 Auth</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<nav>
  <strong>WST2 Auth</strong>
  <?php if (!empty($_SESSION['user_id'])): ?>
    <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
    <a href="<?= BASE_URL ?>/logout.php">Logout (<?= e($_SESSION['username']) ?>)</a>
  <?php else: ?>
    <a href="<?= BASE_URL ?>/login.php">Login</a>
    <a href="<?= BASE_URL ?>/register.php">Register</a>
  <?php endif; ?>
</nav>
<main>
<?php foreach (['success','error'] as $k): if ($m = flash($k)): ?>
  <div class="msg <?= $k ?>"><?= e($m) ?></div>
<?php endif; endforeach; ?>
