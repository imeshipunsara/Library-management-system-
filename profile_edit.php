<?php
session_start();
require 'db_connect.php';

// Ensure logged in
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM users WHERE user_id=$user_id");
$user = $result->fetch_assoc();

// Check if there's an alert message from a previous request
$alert_message = '';
if (isset($_SESSION['alert'])) {
    $alert_message = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

// Handle update
if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Handle photo upload - Use absolute path from root
    $profile_photo = $user['profile_photo']; // keep old if not changed
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/profile_photos/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["profile_photo"]["name"]);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $targetFile)) {
            // Store relative path from root for consistent access
            $profile_photo = "/uploads/profile_photos/" . $fileName;
        }
    }

    if (!empty($_POST['password'])) {
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users 
            SET name=?, dob=?, gender=?, phone=?, address=?, password=?, profile_photo=? 
            WHERE user_id=?");
        $stmt->bind_param("sssssssi",$name,$dob,$gender,$phone,$address,$hashed,$profile_photo,$user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users 
            SET name=?, dob=?, gender=?, phone=?, address=?, profile_photo=? 
            WHERE user_id=?");
        $stmt->bind_param("ssssssi",$name,$dob,$gender,$phone,$address,$profile_photo,$user_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['alert'] = 'Profile updated successfully!';
    } else {
        $_SESSION['alert'] = 'Error updating profile. Please try again.';
    }
    
    header("Location: profile_edit.php");
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Profile</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="profile-container">
    <h2>Edit Your Profile</h2>
    <form method="post" enctype="multipart/form-data">
      <div class="image-section">
        <img id="profilePreview" src="<?= $user['profile_photo'] ?? '/images/bookshelf.jpeg' ?>" alt="Profile Image">
        <input type="file" name="profile_photo" id="imageUpload" accept="image/*">
      </div>

      <label>Name(Username)</label>
      <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>">

      <label>Login ID</label>
      <input type="text" value="<?= htmlspecialchars($user['login_id']) ?>" readonly>

      <label>Password</label>
      <input type="password" name="password" placeholder="Change Password">

      <label>Grade/Class</label>
      <input type="text" value="<?= htmlspecialchars($user['grade']) ?>" readonly>

      <label>School ID</label>
      <input type="text" value="<?= htmlspecialchars($user['school_id']) ?>" readonly>

      <label>Created At</label>
      <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" readonly>

      <label>DOB</label>
      <input type="date" name="dob" value="<?= htmlspecialchars($user['dob']) ?>">

      <label>Gender</label>
      <select name="gender">
        <option <?= $user['gender']=='Male'?'selected':'' ?>>Male</option>
        <option <?= $user['gender']=='Female'?'selected':'' ?>>Female</option>
        <option <?= $user['gender']=='Other'?'selected':'' ?>>Other</option>
      </select>

      <label>Phone</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

      <label>Address</label>
      <textarea name="address"><?= htmlspecialchars($user['address']) ?></textarea>

      <div class="button-container">
        <button type="submit" class="save-btn">Save Changes</button>
      </div>
    </form>
  </div>
  <?php include 'footer.php'; ?>

  <?php if (!empty($alert_message)): ?>
  <script>
    // Show alert after page loads
    window.onload = function() {
        alert("localhost says\n\n<?php echo $alert_message; ?>");
    };
  </script>
  <?php endif; ?>

  <script>
  // Preview image when selecting a new profile photo
  document.getElementById('imageUpload').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
      var reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('profilePreview').src = e.target.result;
      }
      reader.readAsDataURL(e.target.files[0]);
    }
  });
  </script>
</body>
</html>