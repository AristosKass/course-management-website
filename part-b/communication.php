<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Επικοινωνία';
$showTopLink = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender = trim($_POST['sender'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($sender === '' || $subject === '' || $message === '') {
        set_flash('err', 'Συμπλήρωσε αποστολέα, θέμα και κείμενο.');
        redirect_to('communication.php');
    }

    if (!filter_var($sender, FILTER_VALIDATE_EMAIL) || strlen($sender) > 120 ||
        strlen($subject) > 255 || preg_match('/[\r\n]/', $sender . $subject)) {
        set_flash('err', 'Δώσε έγκυρο e-mail και θέμα χωρίς αλλαγές γραμμής (έως 255 χαρακτήρες).');
        redirect_to('communication.php');
    }

    // Store in DB
    $stmt = db()->prepare('INSERT INTO contact_messages (sender_email, subject, body, created_at) VALUES (?,?,?,NOW())');
    $stmt->bind_param('sss', $sender, $subject, $message);
    $stmt->execute();
    $stmt->close();

    // Send mail to all tutors (best-effort)
    $tutors = [];
    $stmt = db()->prepare("SELECT email FROM users WHERE role='tutor'");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $tutors[] = $row['email']; }
    $stmt->close();

    $sent = 0;
    $headers = "From: " . $sender . "\r\n" . "Reply-To: " . $sender;
    foreach ($tutors as $to) {
        if (filter_var(MAIL_ENABLED, FILTER_VALIDATE_BOOLEAN) && @mail($to, $subject, $message, $headers)) {
            $sent++;
        }
    }

    set_flash('ok', 'Το μήνυμα καταχωρήθηκε. Αποστολή e-mail προς tutors: ' . $sent . '/' . count($tutors) . ' (αν υποστηρίζεται από τον server).');
    redirect_to('communication.php');
}

include __DIR__ . '/includes/header.php';
?>

<p>Η συγκεκριμένη ιστοσελίδα παρέχει δύο δυνατότητες για την αποστολή e-mail στον καθηγητή:</p>
<ul>
  <li>Μέσω web φόρμας</li>
  <li>Με χρήση e-mail διεύθυνσης</li>
</ul>

<div class="card">
  <h2>Αποστολή e-mail μέσω web φόρμας</h2>
  <div class="h2-body">
    <form method="post">
      <?php echo csrf_field(); ?>
      <div class="form-row">
        <label for="sender">Αποστολέας:</label>
        <input type="email" id="sender" name="sender" placeholder="name@example.com" required>
      </div>
      <div class="form-row">
        <label for="subject">Θέμα:</label>
        <input type="text" id="subject" name="subject" placeholder="Π.χ. Ερώτηση για το μάθημα" required>
      </div>
      <div class="form-row">
        <label for="message">Κείμενο:</label>
        <textarea id="message" name="message" placeholder="Γράψτε το μήνυμά σας..." required></textarea>
      </div>
      <button type="submit">Αποστολή</button>
    </form>
  </div>
</div>

<div class="card">
  <h2>Αποστολή e-mail με χρήση e-mail διεύθυνσης</h2>
  <div class="h2-body">
    <p>Εναλλακτικά μπορείτε να αποστείλετε e-mail στην παρακάτω διεύθυνση ηλεκτρονικού ταχυδρομείου:</p>
    <p>
      <a class="inline-link" href="mailto:<?php echo h(CONTACT_EMAIL); ?>"><?php echo h(CONTACT_EMAIL); ?></a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
