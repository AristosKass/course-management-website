<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Εργασίες';
$showTopLink = true;
include __DIR__ . '/includes/header.php';

$stmt = db()->prepare('SELECT id, goals, spec_file_path, deliverables, due_date FROM homeworks ORDER BY id ASC');
$stmt->execute();
$res = $stmt->get_result();
$homeworks = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function split_lines($text) {
    $lines = preg_split("/\r\n|\r|\n/", (string)$text);
    $out = [];
    foreach ($lines as $l) {
        $l = trim($l);
        if ($l !== '') { $out[] = $l; }
    }
    return $out;
}
?>

<?php if (is_tutor()): ?>
  <p><a class="inline-link" href="homework_form.php">Προσθήκη νέας εργασίας</a></p>
<?php endif; ?>

<?php foreach ($homeworks as $hw): ?>
  <div class="card">
    <h2>Εργασία <?php echo (int)$hw['id']; ?>
      <?php if (is_tutor()): ?>
        <span class="small-note">
          [<?php echo delete_button('homework_delete.php', $hw['id']); ?>]
          [<a class="inline-link" href="homework_form.php?id=<?php echo (int)$hw['id']; ?>">επεξεργασία</a>]
        </span>
      <?php endif; ?>
    </h2>
    <?php if (is_tutor()) { echo delete_form('homework_delete.php', $hw['id']); } ?>
    <div class="h2-body">
      <p><b>Στόχοι:</b> Οι στόχοι τις εργασίας είναι</p>
      <ol>
        <?php foreach (split_lines($hw['goals']) as $g): ?>
          <li><?php echo h($g); ?></li>
        <?php endforeach; ?>
      </ol>

      <p><b>Εκφώνηση:</b><br> Κατεβάστε την εκφώνηση της εργασίας από <a class="inline-link" href="download.php?type=homework&id=<?php echo (int)$hw['id']; ?>">εδώ</a>.</p>

      <p><b>Παραδοτέα:</b></p>
      <ol>
        <?php foreach (split_lines($hw['deliverables']) as $d): ?>
          <li><?php echo h($d); ?></li>
        <?php endforeach; ?>
      </ol>

      <p><b class="due-date">Ημερομηνία παράδοσης:</b> <?php echo h($hw['due_date']); ?></p>
    </div>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
