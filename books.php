<?php
session_start();
require 'db_connect.php';

// Get search term if any
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Query to get books by category
$books_by_category = [];
foreach ($categories as $category) {
    $query = "SELECT b.book_id, b.title, b.author, b.published_year, b.image_path 
              FROM books b 
              WHERE b.category_id = ?";
    
    if (!empty($search)) {
        $query .= " AND (b.title LIKE ? OR b.author LIKE ?)";
    }
    
    $query .= " ORDER BY b.title";
    
    $stmt = $conn->prepare($query);
    if (!empty($search)) {
        $search_term = "%$search%";
        $stmt->bind_param("iss", $category['category_id'], $search_term, $search_term);
    } else {
        $stmt->bind_param("i", $category['category_id']);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $books = $result->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($books)) {
        $books_by_category[$category['name']] = $books;
    }
}

// If searching and no results found in categories, try a general search
if (!empty($search) && empty($books_by_category)) {
    $query = "SELECT b.book_id, b.title, b.author, b.published_year, b.image_path, c.name as category_name 
              FROM books b 
              LEFT JOIN categories c ON b.category_id = c.category_id 
              WHERE b.title LIKE ? OR b.author LIKE ? 
              ORDER BY c.name, b.title";
    
    $stmt = $conn->prepare($query);
    $search_term = "%$search%";
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_books = $result->fetch_all(MYSQLI_ASSOC);
    
    // Group search results by category
    foreach ($all_books as $book) {
        $category_name = $book['category_name'] ?: 'Uncategorized';
        if (!isset($books_by_category[$category_name])) {
            $books_by_category[$category_name] = [];
        }
        // Remove category_name from book array to maintain consistency
        unset($book['category_name']);
        $books_by_category[$category_name][] = $book;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Books - Online Library</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<?php include 'header.php'; ?>

<section class="search-section">
  <h2>Search Books</h2>
  <form method="GET" action="books.php">
    <input type="text" id="searchInput" name="search" placeholder="Search by title or author..." 
           value="<?php echo htmlspecialchars($search); ?>" />
    <button type="submit" style="display:none;">Search</button>
  </form>
</section>

<?php if (!empty($search)): ?>
  <div class="search-info">
    Search results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
  </div>
<?php endif; ?>

<?php if (empty($books_by_category)): ?>
  <div class="no-books">
    <p>No books found. Try a different search term.</p>
  </div>
<?php else: ?>
  <?php foreach ($books_by_category as $category_name => $books): ?>
    <section class="category-section">
      <div class="category-header">
        <h2 class="category-title"><?php echo htmlspecialchars($category_name); ?></h2>
        <button class="toggle-books">Collapse</button>
      </div>
      
      <div class="books-container">
        <?php foreach ($books as $book): ?>
          <div class="book-card">
            <?php if (!empty($book['image_path'])): ?>
              <img src="<?php echo htmlspecialchars($book['image_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            <?php else: ?>
              <img src="images/bookshelf.jpeg" alt="Default book image">
            <?php endif; ?>

            <h3><?php echo htmlspecialchars($book['title']); ?></h3>
            <p>by <?php echo htmlspecialchars($book['author']); ?></p>

            <div class="extra-details">
              <p>Year: <?php echo htmlspecialchars($book['published_year']); ?></p>
            </div>

            <a href="book_details.php?id=<?php echo $book['book_id']; ?>" class="read-btn">View Details</a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
<?php include 'footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>