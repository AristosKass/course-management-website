<?php
// includes/header.php
// expects: $pageTitle (string), $showTopLink (bool)

if (!isset($pageTitle)) { $pageTitle = ''; }
if (!isset($showTopLink)) { $showTopLink = false; }

$user = current_user();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo h(site_title($pageTitle)); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php if ($showTopLink): ?><a id="top"></a><?php endif; ?>
<div id="container">
  <div id="header">
    <h1><?php echo h($pageTitle); ?></h1>
  </div>

  <div id="main">
    <div id="sidebar">
      <div class="menu-title">Μενού</div>
      <div id="nav">
        <a href="index.php"><img src="assets/img/btn_home.png" alt="Αρχική σελίδα"></a>
        <a href="announcements.php"><img src="assets/img/btn_announce.png" alt="Ανακοινώσεις"></a>
        <a href="communication.php"><img src="assets/img/btn_contact.png" alt="Επικοινωνία"></a>
        <a href="documents.php"><img src="assets/img/btn_docs.png" alt="Έγγραφα μαθήματος"></a>
        <a href="homework.php"><img src="assets/img/btn_hw.png" alt="Εργασίες"></a>
      </div>
    </div>

    <div id="content">
      <div class="small-note" style="margin-bottom:10px;">
        <?php if ($user): ?>
          Συνδεδεμένος ως: <b><?php echo h($user['first_name'] . ' ' . $user['last_name']); ?></b>
          (<?php echo h($user['role']); ?>) — <form class="inline-form" method="post" action="logout.php"><?php echo csrf_field(); ?><button class="inline-delete" type="submit">Αποσύνδεση</button></form>
          <?php if (is_tutor()): ?>
            &nbsp;|&nbsp; <a class="inline-link" href="users.php">Διαχείριση χρηστών</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <?php if ($flash): ?>
        <div class="card" style="border-color: rgba(30,90,160,.25);">
          <b><?php echo h(strtoupper($flash['type'])); ?>:</b> <?php echo h($flash['msg']); ?>
        </div>
      <?php endif; ?>
