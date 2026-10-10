<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_tutor();

$pageTitle = 'Διαχείριση χρηστών';
$showTopLink = false;
include __DIR__ . '/includes/header.php';

$stmt = db()->prepare('SELECT id, first_name, last_name, email, role FROM users ORDER BY role DESC, last_name ASC');
$stmt->execute();
$res = $stmt->get_result();
$users = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<p><a class="inline-link" href="user_form.php">Προσθήκη νέου χρήστη</a></p>

<div class="card">
  <h2>Χρήστες</h2>
  <div class="h2-body">
    <table style="width:100%;border-collapse:collapse;">
      <tr>
        <th style="text-align:left;padding:6px;border-bottom:1px solid rgba(30,90,160,.25);">Ονοματεπώνυμο</th>
        <th style="text-align:left;padding:6px;border-bottom:1px solid rgba(30,90,160,.25);">Email (login)</th>
        <th style="text-align:left;padding:6px;border-bottom:1px solid rgba(30,90,160,.25);">Ρόλος</th>
        <th style="text-align:left;padding:6px;border-bottom:1px solid rgba(30,90,160,.25);">Ενέργειες</th>
      </tr>
      <?php foreach ($users as $u): ?>
        <tr>
          <td style="padding:6px;border-bottom:1px solid rgba(30,90,160,.12);">
            <?php echo h($u['first_name'] . ' ' . $u['last_name']); ?>
          </td>
          <td style="padding:6px;border-bottom:1px solid rgba(30,90,160,.12);">
            <?php echo h($u['email']); ?>
          </td>
          <td style="padding:6px;border-bottom:1px solid rgba(30,90,160,.12);">
            <?php echo h($u['role']); ?>
          </td>
          <td style="padding:6px;border-bottom:1px solid rgba(30,90,160,.12);">
            <a class="inline-link" href="user_form.php?id=<?php echo (int)$u['id']; ?>">επεξεργασία</a>
            &nbsp;|&nbsp;
            <?php echo delete_button('user_delete.php', $u['id']); ?>
            <?php if (is_tutor()) { echo delete_form('user_delete.php', $u['id']); } ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
