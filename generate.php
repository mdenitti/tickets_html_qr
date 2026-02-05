<?php
include 'includes/header.php';
require 'includes/conn.php';
$_SESSION['email'] = $_POST['email'];
$_SESSION['password'] = $_POST['password'];
$email = $_POST['email'];
$password = $_POST['password'];
$name = $_POST['name'];

$profile_pic_path = "";
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check file extension
    if($imageFileType != "jpg" && $imageFileType != "jpeg" && $imageFileType != "pdf" ) {
        echo "Sorry, only JPG & PDF files are allowed.";
    } else {
        // Simple file upload - purely for educational demo
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            $profile_pic_path = $target_file;
        }
    }
}

// minimal OOP prepared statement
$stmt = $conn->prepare("INSERT INTO users (name, email, password, profile_pic) VALUES (?, ?, ?, ?)");
if ($stmt && $stmt->bind_param("ssss", $name, $email, $password, $profile_pic_path) && $stmt->execute()) {
    echo "New user create successfully";
} else {
    echo "Error, try again: " . ($stmt ? $stmt->error : $conn->error);
}

if ($stmt) {
    $stmt->close();
}

?>
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-12">
                <h1>Your details are:</h1>
               
                <hr>
                <p>Email: <?php echo $email; ?></p>
                <p>Name: <?php echo $name; ?></p>
                
                <?php if ($profile_pic_path): ?>
                    <p>Profile Picture:</p>
                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" style="width: 200px; height: auto;">
                <?php endif; ?>
               
            </div>
        </div>
    </div>
</body>
</html>
