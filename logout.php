<?php
session_start();
session_unset();
session_destroy();

// Show message then redirect
echo "<script>
    alert('You have been logged out successfully.');
    window.location = 'login.php';
</script>";
exit();
?>
