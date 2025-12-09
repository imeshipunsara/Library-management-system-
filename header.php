<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get current page name for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="header">
    <div class="logo-container">
        <img src="./images/logo.webp" alt="" class="logo">
        <div class="library-name">Sujatha Vidyalaya</div>
    </div>
    
    <nav>
        <ul>
            <li><a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
            <li><a href="about_us.php" class="<?php echo ($current_page == 'about_us.php') ? 'active' : ''; ?>">About Us</a></li>
            <li><a href="books.php" class="<?php echo ($current_page == 'books.php') ? 'active' : ''; ?>">Browse Books</a></li>
            <li><a href="borrowed_books.php" class="<?php echo ($current_page == 'borrowed_books.php') ? 'active' : ''; ?>">My Borrowed Books</a></li>
            <li><a href="profile_edit.php" class="<?php echo ($current_page == 'profile_edit.php') ? 'active' : ''; ?>">Profile</a></li>
            <li><a href="user_feedback.php" class="<?php echo ($current_page == 'user_feedback.php') ? 'active' : ''; ?>">Feedback</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>
</div>