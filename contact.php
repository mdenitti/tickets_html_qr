<?php
include 'includes/header.php'
?>
<div class="container">
  <div class="row">
    <div class="col">
        <h1>Contact</h1>
         <?php if (isset($_SESSION['email'])): ?>
        <p>Hello <?php echo $_SESSION['email'] ?></p>
        <?php endif; ?>
    </div>
  </div>
</div>