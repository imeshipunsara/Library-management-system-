<?php
session_start();
require '/xampp/htdocs/LibrarySystem/db_connect.php';

// Check if there's an alert message from a previous request
$alert_message = '';
if (isset($_SESSION['alert'])) {
    $alert_message = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_book'])) {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category_id = intval($_POST['category_id']); // Changed from genre to category_id
        $published_year = trim($_POST['published_year']);
        $description = trim($_POST['description']);
        $available_copies = intval($_POST['available_copies']);
        
        // Handle file upload
        $image_path = '';
        if (isset($_FILES['bookImage']) && $_FILES['bookImage']['error'] == 0) {
            $target_dir = "../images/books/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['bookImage']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES['bookImage']['tmp_name'], $target_file)) {
                $image_path = "images/books/" . $filename;
            }
        }
        
        // Insert into database - UPDATED to use category_id instead of genre
        $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category_id, published_year, description, image_path, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiissi", $title, $author, $isbn, $category_id, $published_year, $description, $image_path, $available_copies);
        
        if ($stmt->execute()) {
            $_SESSION['alert'] = 'Book added successfully!';
        } else {
            $_SESSION['alert'] = 'Error adding book: ' . $conn->error;
        }
        
        header("Location: manage_books.php");
        exit();
    } elseif (isset($_POST['delete_book'])) {
    $book_id = intval($_POST['book_id']);
    
    // First get image path to delete the file
    $stmt = $conn->prepare("SELECT image_path FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    // Start transaction to handle multiple operations
    $conn->begin_transaction();
    
    try {
        // First delete related records in book_requests
        $stmt = $conn->prepare("DELETE FROM book_requests WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        
        // Then delete the book
        $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        // Delete the image file if it exists
        if (!empty($book['image_path'])) {
            $file_path = "../" . $book['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $_SESSION['alert'] = 'Book deleted successfully!';
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $_SESSION['alert'] = 'Error deleting book: ' . $e->getMessage();
    }
    
    header("Location: manage_books.php");
    exit();
} elseif (isset($_POST['update_book'])) {
    $book_id = intval($_POST['book_id']);
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $category_id = intval($_POST['category_id']);
    $published_year = trim($_POST['published_year']);
    $description = trim($_POST['description']);
    $available_copies = intval($_POST['available_copies']);

    // Get current book details for image path
    $stmt = $conn->prepare("SELECT image_path FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_book = $result->fetch_assoc();
    $image_path = $current_book['image_path'];

    // Handle file upload if a new image is provided
    if (isset($_FILES['bookImage']) && $_FILES['bookImage']['error'] == 0) {
        $target_dir = "../images/books/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_extension = pathinfo($_FILES['bookImage']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES['bookImage']['tmp_name'], $target_file)) {
            // Delete old image
            if (!empty($current_book['image_path']) && file_exists("../" . $current_book['image_path'])) {
                unlink("../" . $current_book['image_path']);
            }
            $image_path = "images/books/" . $filename;
        }
    }

    // Update book in database
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, category_id=?, published_year=?, description=?, image_path=?, available_copies=? WHERE book_id=?");
    $stmt->bind_param("sssiissii", $title, $author, $isbn, $category_id, $published_year, $description, $image_path, $available_copies, $book_id);

    if ($stmt->execute()) {
        $_SESSION['alert'] = 'Book updated successfully!';
    } else {
        $_SESSION['alert'] = 'Error updating book: ' . $conn->error;
    }
    
    header("Location: manage_books.php");
    exit();
}
}

// Get all books for the table - UPDATED to join with categories table
$books = [];
$result = $conn->query("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.category_id ORDER BY title");
if ($result) {
    $books = $result->fetch_all(MYSQLI_ASSOC);
}

// Get categories for dropdown
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name");
if ($cat_result) {
    $categories = $cat_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Books</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="lib.css">
</head>
<body>
<?php include 'librarian_header.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h2>Manage Books</h2>
  </div>

  <div class="container4">
    <!-- Add New Book Form -->
    <div class="form-section">
      <h3>Add New Book</h3>
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="title">Book Title</label>
          <input type="text" id="title" name="title" placeholder="Enter book title" required />
        </div>
        <div class="form-group">
          <label for="author">Author</label>
          <input type="text" id="author" name="author" placeholder="Enter author name" required />
        </div>
        <div class="form-group">
          <label for="isbn">ISBN</label>
          <input type="text" id="isbn" name="isbn" placeholder="Enter ISBN number" required />
        </div>
        <div class="form-group">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id" required>
            <option value="">Select a Category</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="published_year">Published Year</label>
          <input type="number" id="published_year" name="published_year" placeholder="Enter published year" required />
        </div>
        <div class="form-group">
          <label for="available_copies">Available Copies</label>
          <input type="number" id="available_copies" name="available_copies" value="1" min="1" required />
        </div>
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" placeholder="Enter a short description of the book"></textarea>
        </div>
        <div class="form-group">
          <label for="bookImage">Book Image</label>
          <input type="file" id="bookImage" name="bookImage" accept="image/*" />
        </div>
        <button type="submit" name="add_book">Add Book</button>
      </form>
    </div>

    <!-- Book List Table -->
    <div class="table-section1">
      <h3>Book List</h3>
      <table>
        <thead>
          <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Author</th>
            <th>ISBN</th>
            <th>Category</th>
            <th>Published</th>
            <th>Copies</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($books as $book): ?>
          <tr id="book-row-<?php echo $book['book_id']; ?>">
            <td>
              <?php if (!empty($book['image_path'])): ?>
                <img src="../<?php echo htmlspecialchars($book['image_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-image">
              <?php else: ?>
                <img src="../images/bookshelf.jpeg" alt="Default book image" class="book-image">
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($book['title']); ?></td>
            <td><?php echo htmlspecialchars($book['author']); ?></td>
            <td><?php echo htmlspecialchars($book['isbn']); ?></td>
            <td><?php echo htmlspecialchars($book['category_name']); ?></td>
            <td><?php echo htmlspecialchars($book['published_year']); ?></td>
            <td><?php echo htmlspecialchars($book['available_copies']); ?></td>
            <td class="action-buttons1">
              <button type="button" class="edit-btn1" onclick="openEditModal(<?php echo $book['book_id']; ?>)">Edit</button>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                <button type="submit" name="delete_book" class="delete-btn1" onclick="return confirm('Are you sure you want to delete this book?')">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($books)): ?>
            <tr>
              <td colspan="8" style="text-align:center;">No books found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit Book Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close-modal" onclick="closeEditModal()">&times;</span>
    <h2 class="modal-title">Edit Book</h2>
    <form id="editBookForm" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="book_id" id="edit_book_id">
      
      <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" id="edit_title" required>
      </div>
      <div class="form-group">
        <label>Author</label>
        <input type="text" name="author" id="edit_author" required>
      </div>
      <div class="form-group">
        <label>ISBN</label>
        <input type="text" name="isbn" id="edit_isbn" required>
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="category_id" id="edit_category_id" required>
          <option value="">Select a Category</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Published Year</label>
        <input type="number" name="published_year" id="edit_published_year" required>
      </div>
      <div class="form-group">
        <label>Available Copies</label>
        <input type="number" name="available_copies" id="edit_available_copies" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="edit_description"></textarea>
      </div>
      <div class="form-group">
        <label>Book Image</label><br>
        <img id="edit_image_preview" src="" alt="Book Image" width="80" style="margin-bottom: 10px;"><br>
        <input type="file" name="bookImage" id="edit_bookImage">
      </div>
      
      <div class="modal-buttons">
        <button type="submit" name="update_book" class="modal-update-btn">Update Book</button>
        <button type="button" class="modal-cancel-btn" onclick="closeEditModal()">Cancel</button>
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
// Store book data for modal population
const bookData = {};
<?php foreach ($books as $book): ?>
bookData[<?php echo $book['book_id']; ?>] = {
  title: "<?php echo addslashes($book['title']); ?>",
  author: "<?php echo addslashes($book['author']); ?>",
  isbn: "<?php echo addslashes($book['isbn']); ?>",
  category_id: "<?php echo $book['category_id']; ?>",
  published_year: "<?php echo $book['published_year']; ?>",
  available_copies: "<?php echo $book['available_copies']; ?>",
  description: "<?php echo addslashes($book['description']); ?>",
  image_path: "<?php echo $book['image_path']; ?>"
};
<?php endforeach; ?>

function openEditModal(bookId) {
  const book = bookData[bookId];
  if (!book) return;
  
  // Populate form fields
  document.getElementById('edit_book_id').value = bookId;
  document.getElementById('edit_title').value = book.title;
  document.getElementById('edit_author').value = book.author;
  document.getElementById('edit_isbn').value = book.isbn;
  document.getElementById('edit_category_id').value = book.category_id;
  document.getElementById('edit_published_year').value = book.published_year;
  document.getElementById('edit_available_copies').value = book.available_copies;
  document.getElementById('edit_description').value = book.description;
  
  // Set image preview
  const imagePreview = document.getElementById('edit_image_preview');
  if (book.image_path) {
    imagePreview.src = '../' + book.image_path;
  } else {
    imagePreview.src = '../images/bookshelf.jpeg';
  }
  
  // Show modal
  document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
  const modal = document.getElementById('editModal');
  if (event.target == modal) {
    closeEditModal();
  }
}

// Handle image preview when a new image is selected
document.getElementById('edit_bookImage').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('edit_image_preview').src = e.target.result;
    }
    reader.readAsDataURL(file);
  }
});
</script>

</body>
</html>