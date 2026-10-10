<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Ανακοινώσεις';
$showTopLink = true;
include __DIR__ . '/includes/header.php';

$stmt = db()->prepare('SELECT id, ann_date, subject, body, is_homework, homework_id FROM announcements ORDER BY ann_date ASC, id ASC');
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php if (is_tutor()): ?>
  <p><a class="inline-link" href="announcement_form.php">Προσθήκη νέας ανακοίνωσης</a></p>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
  <div class="card">
    <h2>Ανακοίνωση <?php echo (int)$r['id']; ?>
      <?php if (is_tutor()): ?>
        <span class="small-note">
          [<?php echo delete_button('announcement_delete.php', $r['id']); ?>]
          [<a class="inline-link" href="announcement_form.php?id=<?php echo (int)$r['id']; ?>">επεξεργασία</a>]
        </span>
      <?php endif; ?>
    </h2>
    <?php if (is_tutor()) { echo delete_form('announcement_delete.php', $r['id']); } ?>
    <div class="h2-body">
      <p class="meta"><b>Ημερομηνία:</b> <?php echo h($r['ann_date']); ?></p>
      <p><b>Θέμα:</b> <?php echo h($r['subject']); ?></p>
      <p><?php echo nl2br(h($r['body'])); ?></p>

      <?php if ((int)$r['is_homework'] === 1 || !empty($r['homework_id'])): ?>
        <p>Δείτε την ιστοσελίδα <a class="inline-link" href="homework.php">Εργασίες</a>.</p>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
