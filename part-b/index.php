<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Αρχική σελίδα';
$showTopLink = false;
include __DIR__ . '/includes/header.php';
?>

<p>Καλωσορίσατε! Ο παρών ιστοχώρος αποτελεί ένα εκπαιδευτικό website που δείχνει πώς οργανώνουμε ένα απλό μάθημα στο διαδίκτυο, χρησιμοποιώντας HTML.</p>

<p>Στόχος είναι να υπάρχουν βασικές ενότητες που συναντώνται σε ένα προπτυχιακό μάθημα:</p>
<ul>
  <li><b>Ανακοινώσεις</b>: ενημερώσεις για το μάθημα και τις εργασίες.</li>
  <li><b>Επικοινωνία</b>: τρόποι αποστολής e-mail στον διδάσκοντα (φόρμα και e-mail).</li>
  <li><b>Έγγραφα μαθήματος</b>: υλικό προς κατέβασμα (download) από τους φοιτητές.</li>
  <li><b>Εργασίες</b>: εκφωνήσεις/στόχοι εργασιών και σχετικά αρχεία.</li>
</ul>

<div class="hero">
  <img src="assets/img/banner.png" alt="Banner μαθήματος">
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
