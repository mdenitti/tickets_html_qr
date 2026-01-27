<?php
include 'includes/header.php';
session_unset();
session_destroy();
?>
<div class="container">
    <div class="row">
        <div class="col">
            <h2>You are logged out!</h2>
            <p>Have a nice day</p>
        </div>
    </div>
</div>