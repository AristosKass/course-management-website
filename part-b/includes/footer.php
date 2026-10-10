<?php
// includes/footer.php
// expects: $showTopLink (bool)
if (!isset($showTopLink)) { $showTopLink = false; }
?>
    </div><!-- /#content -->
  </div><!-- /#main -->

  <?php if ($showTopLink): ?>
    <div id="footer"><a href="#top">top</a></div>
  <?php endif; ?>
</div><!-- /#container -->
</body>
</html>
