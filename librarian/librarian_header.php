<?php
// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar" id="sidebar">
    <div class="logo-container">
      <img src="../images/logo.webp" alt="Library Logo">
    </div>

    <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>"><h3>🏠 Dashboard</h3></a>
    <a href="manage_books.php" class="<?= ($current_page == 'manage_books.php') ? 'active' : '' ?>"><h3>📚 Manage Books</h3></a>
    <a href="manage_user.php" class="<?= ($current_page == 'manage_user.php') ? 'active' : '' ?>"><h3>👥 Manage Users</h3></a>
    <a href="borrow_return.php" class="<?= ($current_page == 'borrow_return.php') ? 'active' : '' ?>"><h3>🔄 Borrow and Return</h3></a>
    <a href="category_management.php" class="<?= ($current_page == 'category_management.php') ? 'active' : '' ?>"><h3>📈 Manage Category</h3></a>
    <a href="handle_feedback.php" class="<?= ($current_page == 'handle_feedback.php') ? 'active' : '' ?>"><h3>⚙️ Feedbacks</h3></a>
    <a href="lib_logout.php" class="logout"><h3>🚪 Logout</h3></a>
</div>
