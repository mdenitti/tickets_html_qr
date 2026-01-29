<?php
include 'includes/header.php';
require 'includes/conn.php';
$sql = "SELECT * FROM users";
$result = mysqli_query($conn,$sql);
// print_r($result);
// echo mysqli_num_rows($result);

if (mysqli_num_rows($result) > 0) {
    while ($assoc = mysqli_fetch_assoc($result)) {
        echo $assoc['name']." - ".$assoc['email'];
        if (!empty($assoc['profile_pic'])) {
            echo " <img src='".$assoc['profile_pic']."' width='50'>";
        }
        echo "<hr>";
    }    
} else {
    echo "0 results";
}


?>
    <div class="container mt-2">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <h1>Register</h1>
                <form action="generate.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="exampleInputEmail1" class="form-label">Email address</label>
                        <input type="email" class="form-control" name="email" aria-describedby="emailHelp" required>
                        <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                    </div>
                    <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="profilePic" class="form-label">Profile Picture (JPG or PDF only)</label>
                        <input type="file" class="form-control" name="profile_pic" id="profilePic" accept=".jpg,.jpeg,.pdf">
                    </div>
                    <div class="mb-3">
                        <label for="exampleName" class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
