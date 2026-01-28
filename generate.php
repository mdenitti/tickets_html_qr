<?php
include 'includes/header.php';
require 'includes/conn.php';
$_SESSION['email'] = $_POST['email'];
$_SESSION['password'] = $_POST['password'];
$email=$_POST['email'];
$password=$_POST['password'];

// the unsafe way; 
$query="INSERT INTO users (name, email,password) VALUES ('ljlkj','$email','$password')";
if(mysqli_query($conn,$query)) {
    echo "New user create successfully";
} else {
    echo "Error, try again: ".mysqli_error($conn);
}

?>
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-12">
                <h1>Your details are:</h1>
               
                <hr>
                <?php echo $_POST['email'] ?>
               
            </div>
        </div>
    </div>
</body>
</html>
