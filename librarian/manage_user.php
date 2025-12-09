<?php
session_start();
require '../db_connect.php';

// Check if there's an alert message from a previous request
$alert_message = '';
if (isset($_SESSION['alert'])) {
    $alert_message = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

// Handle Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $login_id = trim($_POST['login_id']); 
    $grade = trim($_POST['grade']);
    $school_id = trim($_POST['school_id']);

    $hashedPassword = password_hash($login_id, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, login_id, password, grade, school_id, role, created_at) VALUES (?, ?, ?, ?, ?, 'student', NOW())");
    $stmt->bind_param("sssss", $name, $login_id, $hashedPassword, $grade, $school_id);
    
    if ($stmt->execute()) {
        $_SESSION['alert'] = 'Student added successfully!';
    } else {
        $_SESSION['alert'] = 'Error adding student: ' . $conn->error;
    }
    
    header("Location: manage_user.php");
    exit();
}

// Handle Update Student (from Edit Profile popup)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $grade = $_POST['grade'];
    $school_id = $_POST['school_id'];

    // Handle photo
    $result = $conn->query("SELECT profile_photo FROM users WHERE user_id=$user_id");
    $old = $result->fetch_assoc();
    $profile_photo = $old['profile_photo'];

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/profile_photos/"; 
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["profile_photo"]["name"]);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $targetFile)) {
            $profile_photo = "/uploads/profile_photos/" . $fileName;
        }
    }

    $stmt = $conn->prepare("UPDATE users 
        SET name=?, dob=?, gender=?, phone=?, address=?, grade=?, school_id=?, profile_photo=? 
        WHERE user_id=?");
    $stmt->bind_param("ssssssssi", $name, $dob, $gender, $phone, $address, $grade, $school_id, $profile_photo, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['alert'] = 'Student profile updated successfully!';
    } else {
        $_SESSION['alert'] = 'Error updating student: ' . $conn->error;
    }
    
    header("Location: manage_user.php");
    exit();
}

// Handle Reset Password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];
    $login_id = $_POST['login_id']; // reset to login_id
    $hashedPassword = password_hash($login_id, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
    $stmt->bind_param("si", $hashedPassword, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['alert'] = 'Password reset successfully!';
    } else {
        $_SESSION['alert'] = 'Error resetting password: ' . $conn->error;
    }
    
    header("Location: manage_user.php");
    exit();
}

// Handle Delete Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $user_id = $_POST['user_id'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if user has active borrowings
        $borrowing_check = $conn->prepare("SELECT COUNT(*) as count FROM book_requests WHERE user_id = ? AND status = 'accepted'");
        if (!$borrowing_check) {
            throw new Exception("Error preparing borrowing check: " . $conn->error);
        }
        $borrowing_check->bind_param("i", $user_id);
        $borrowing_check->execute();
        $result = $borrowing_check->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            throw new Exception("Cannot delete user. They have active book borrowings.");
        }
        
        // Delete user's book requests
        $delete_requests = $conn->prepare("DELETE FROM book_requests WHERE user_id = ?");
        if (!$delete_requests) {
            throw new Exception("Error preparing requests deletion: " . $conn->error);
        }
        $delete_requests->bind_param("i", $user_id);
        $delete_requests->execute();
        
        // Delete user's feedback (if any)
        $delete_feedback = $conn->prepare("DELETE FROM feedback WHERE user_id = ?");
        if (!$delete_feedback) {
            throw new Exception("Error preparing feedback deletion: " . $conn->error);
        }
        $delete_feedback->bind_param("i", $user_id);
        $delete_feedback->execute();
        
        // Delete user from database
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing user deletion: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['alert'] = 'Student deleted successfully!';
        } else {
            throw new Exception("Error deleting student: " . $conn->error);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['alert'] = $e->getMessage();
    }
    
    // Redirect to refresh the page and show updated list
    header("Location: manage_user.php");
    exit();
}

// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$query = "SELECT u.*, 
                 CASE WHEN EXISTS (SELECT 1 FROM book_requests br WHERE br.user_id = u.user_id) 
                      THEN 'active' 
                      ELSE 'inactive' 
                 END as borrow_status 
          FROM users u 
          WHERE u.role='student'";
if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (u.name LIKE ? OR u.grade LIKE ?)";
}

$query .= " ORDER BY u.user_id DESC";

$stmt = $conn->prepare($query);
if (!empty($search)) {
    $stmt->bind_param("ss", $search_term, $search_term);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Manage Users</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="lib.css">
  <style>
    /* Status indicator styles */
    .status-indicator {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin-right: 8px;
    }
    
    .status-active {
      background-color: #2ecc71;
    }
    
    .status-inactive {
      background-color: #f39c12;
    }
  </style>
</head>
<body>
  <?php include 'librarian_header.php'; ?>
  
  <div class="main-content">
    <div class="topbar">
      <h2>Manage Users</h2>
    </div>

    <div class="container5">
      <div class="search-container">
        <form method="GET" class="search-form">
          <input type="text" name="search" class="search-input" placeholder="Search by name or grade..." 
                value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="search-btn">Search</button>
          <?php if (!empty($search)): ?>
            <a href="manage_user.php" class="clear-btn">Clear</a>
          <?php endif; ?>
        </form>
        
        <div class="top-bar">
          <button class="add-btn" onclick="toggleForm()">➕ Add Student</button>
        </div>
      </div>

      <!-- Add Student Form -->
      <div class="add-form" id="addForm">
        <form method="post">
          <input type="hidden" name="add_student" value="1">
          <label>Full Name</label><input type="text" name="name" required>
          <label>Login ID (Initial Password)</label><input type="text" name="login_id" required>
          <label>Grade/Class</label><input type="text" name="grade" required>
          <label>School ID</label><input type="text" name="school_id" required>
          <button type="submit" class="add-btn">Add Student</button>
        </form>
      </div>

      <div class="table-section2">
        <h3>Registered Members</h3>
        <?php if ($result->num_rows > 0): ?>
          <table>
            <tr>
              <th>Status</th>
              <th>ID</th>
              <th>Photo</th>
              <th>Name</th>
              <th>Login ID</th>
              <th>Grade</th>
              <th>School ID</th>
              <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { ?>
              <tr>
                <td>
                  <span class="status-indicator <?php echo $row['borrow_status'] === 'active' ? 'status-active' : 'status-inactive'; ?>" 
                        title="<?php echo $row['borrow_status'] === 'active' ? 'Active user (has borrowed books)' : 'Inactive user (has not borrowed books)'; ?>">
                  </span>
                </td>
                <td>#<?= $row['user_id'] ?></td>
                <td>
                  <img src="<?= !empty($row['profile_photo']) ? $row['profile_photo'] : '/images/bookshelf.jpeg' ?>" 
                      alt="Profile" class="profile-thumb">
                </td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['login_id']) ?></td>
                <td><?= htmlspecialchars($row['grade']) ?></td>
                <td><?= htmlspecialchars($row['school_id']) ?></td>
                <td class="action-buttons2"> 
                  <button class="delete-btn" onclick="confirmDelete(<?= $row['user_id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')">Delete</button>
                  <button onclick='openEditForm(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit Profile</button>
                </td>
              </tr>
            <?php } ?>
          </table>
        <?php else: ?>
          <p style="text-align: center; padding: 20px; color: #7f8c8d;">
            <?php if (!empty($search)): ?>
              No users found matching your search criteria.
            <?php else: ?>
              No users found.
            <?php endif; ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <h3>Confirm Delete</h3>
      <p>Are you sure you want to delete student: <span id="deleteUserName"></span>?</p>
      <form id="deleteForm" method="post">
        <input type="hidden" name="delete_student" value="1">
        <input type="hidden" id="delete_user_id" name="user_id">
        <div class="modal-buttons">
          <button type="submit" class="confirm-delete">Yes, Delete</button>
          <button type="button" class="cancel-delete" onclick="closeDeleteModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div id="editModal">
    <div class="modal-content">
      <h3>Edit Student Profile</h3>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="update_student" value="1">
        <input type="hidden" id="edit_user_id" name="user_id">
        <label>Profile Photo</label>
        <img id="edit_preview" src="" alt="Profile Image" style="width:80px; height:80px; border-radius:50%; display:block; margin-bottom:10px;">
        <input type="file" name="profile_photo" id="edit_photo" accept="image/*">
        <label>Full Name</label><input type="text" id="edit_name" name="name"><br>
        <label>Login ID</label><input type="text" id="edit_login" name="login_id" readonly><br>
        <label>Password</label><input type="text" value="********" readonly>
        <button type="button" onclick="resetPassword()">Reset Password</button><br>
        <label>Grade</label><input type="text" id="edit_grade" name="grade"><br>
        <label>School ID</label><input type="text" id="edit_school_id" name="school_id"><br>
        <label>DOB</label><input type="date" id="edit_dob" name="dob"><br>
        <label>Gender</label>
        <select id="edit_gender" name="gender">
          <option value="">--Select--</option>
          <option>Male</option><option>Female</option><option>Other</option>
        </select><br>
        <label>Phone</label><input type="text" id="edit_phone" name="phone"><br>
        <label>Address</label><textarea id="edit_address" name="address"></textarea><br>
        <div class="form-actions">
          <button type="submit" class="add-btn">Save</button>
          <button type="button" onclick="closeEditForm()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <?php if (!empty($alert_message)): ?>
  <script>
    // Show alert after page loads
    window.onload = function() {
        alert("localhost says\n\n<?php echo $alert_message; ?>");
    };
  </script>
  <?php endif; ?>

  <script>
  function toggleForm(){ 
    var f=document.getElementById("addForm"); 
    f.style.display=(f.style.display==="block")?"none":"block";
  }
  
  function openEditForm(data){
    document.getElementById("edit_user_id").value=data.user_id;
    document.getElementById("edit_name").value=data.name;
    document.getElementById("edit_login").value=data.login_id;
    document.getElementById("edit_grade").value=data.grade;
    document.getElementById("edit_school_id").value=data.school_id;
    document.getElementById("edit_dob").value=data.dob || "";
    document.getElementById("edit_gender").value=data.gender || "";
    document.getElementById("edit_phone").value=data.phone || "";
    document.getElementById("edit_address").value=data.address || "";
    document.getElementById("edit_preview").src = data.profile_photo ? data.profile_photo : "/images/bookshelf.jpeg";
    document.getElementById("editModal").style.display="block";
  }
  
  function closeEditForm(){
    document.getElementById("editModal").style.display="none";
  }
  
  function resetPassword() {
    if (confirm("Are you sure you want to reset the password to the login ID?")) {
      // Create a hidden form to submit the reset request
      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';
      
      const userIdInput = document.createElement('input');
      userIdInput.type = 'hidden';
      userIdInput.name = 'user_id';
      userIdInput.value = document.getElementById("edit_user_id").value;
      
      const loginIdInput = document.createElement('input');
      loginIdInput.type = 'hidden';
      loginIdInput.name = 'login_id';
      loginIdInput.value = document.getElementById("edit_login").value;
      
      const resetInput = document.createElement('input');
      resetInput.type = 'hidden';
      resetInput.name = 'reset_password';
      resetInput.value = '1';
      
      form.appendChild(userIdInput);
      form.appendChild(loginIdInput);
      form.appendChild(resetInput);
      
      document.body.appendChild(form);
      form.submit();
    }
  }
  
  // Delete functionality
  function confirmDelete(userId, userName) {
    document.getElementById("delete_user_id").value = userId;
    document.getElementById("deleteUserName").textContent = userName;
    document.getElementById("deleteModal").style.display = "block";
  }
  
  function closeDeleteModal() {
    document.getElementById("deleteModal").style.display = "none";
  }
  
  // Close modals if clicked outside
  window.onclick = function(event) {
    var deleteModal = document.getElementById("deleteModal");
    var editModal = document.getElementById("editModal");
    
    if (event.target == deleteModal) {
      closeDeleteModal();
    }
    if (event.target == editModal) {
      closeEditForm();
    }
  }

  // Preview image when selecting a new profile photo
  document.getElementById('edit_photo').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
      var reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('edit_preview').src = e.target.result;
      }
      reader.readAsDataURL(e.target.files[0]);
    }
  });
  </script>
</body>
</html>