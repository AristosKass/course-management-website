<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Έγγραφα μαθήματος';
$showTopLink = true;
include __DIR__ . '/includes/header.php';

$stmt = db()->prepare('SELECT id, title, description FROM documents ORDER BY id ASC');
$stmt->execute();
$res = $stmt->get_result();
$docs = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php if (is_tutor()): ?>
  <p><a class="inline-link" href="document_form.php">Προσθήκη νέου εγγράφου</a></p>
<?php endif; ?>

<?php foreach ($docs as $d): ?>
  <div class="card">
    <h2><?php echo h($d['title']); ?>
      <?php if (is_tutor()): ?>
        <span class="small-note">
          [<?php echo delete_button('document_delete.php', $d['id']); ?>]
          [<a class="inline-link" href="document_form.php?id=<?php echo (int)$d['id']; ?>">επεξεργασία</a>]
        </span>
      <?php endif; ?>
    </h2>
    <?php if (is_tutor()) { echo delete_form('document_delete.php', $d['id']); } ?>
    <div class="h2-body">
      <p><b>Περιγραφή:</b> <?php echo h($d['description']); ?></p>
      <p><a class="inline-link" href="download.php?type=document&id=<?php echo (int)$d['id']; ?>">Download</a></p>
    </div>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
