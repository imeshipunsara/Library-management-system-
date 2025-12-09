<?php
// category_management.php
session_start();
require '/xampp/htdocs/LibrarySystem/db_connect.php';

// Check if there's an alert message from a previous request
$alert_message = '';
if (isset($_SESSION['alert'])) {
    $alert_message = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $_SESSION['alert'] = 'Category added successfully!';
        } else {
            $_SESSION['alert'] = 'Error adding category: ' . $conn->error;
        }
        
        header("Location: category_management.php");
        exit();
    } elseif (isset($_POST['delete_category'])) {
        $category_id = intval($_POST['category_id']);
        
        // Check if category is in use
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM books WHERE category_id = ?");
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            $_SESSION['alert'] = "Cannot delete category: it is being used by " . $count . " book(s).";
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            
            if ($stmt->execute()) {
                $_SESSION['alert'] = 'Category deleted successfully!';
            } else {
                $_SESSION['alert'] = 'Error deleting category: ' . $conn->error;
            }
        }
        
        header("Location: category_management.php");
        exit();
    }
}

// Get all categories
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY name");
if ($result) {
    $categories = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Categories</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="lib.css">
</head>
<body>
<?php include 'librarian_header.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h2>Manage Categories</h2>
  </div>

  <div class="container4">
    <!-- Add New Category Form -->
    <div class="form-section">
      <h3>Add New Category</h3>
      <form method="POST">
        <div class="form-group">
          <label for="name">Category Name</label>
          <input type="text" id="name" name="name" placeholder="Enter category name" required />
        </div>
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" placeholder="Enter category description"></textarea>
        </div>
        <button type="submit" name="add_category">Add Category</button>
      </form>
    </div>

    <!-- Category List Table -->
    <div class="table-section1">
      <h3>Category List</h3>
      
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $category): ?>
          <tr>
            <td><?php echo htmlspecialchars($category['name']); ?></td>
            <td><?php echo htmlspecialchars($category['description']); ?></td>
            <td class="action-buttons1">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                <button type="submit" name="delete_category" class="delete-btn1" onclick="return confirm('Are you sure you want to delete this category?')">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($categories)): ?>
            <tr>
              <td colspan="3" style="text-align:center;">No categories found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
      
    </div>
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

</body>
</html>