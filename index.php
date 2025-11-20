<?php  
// index.php - Microcart Entry Point  

// --------------------------  
// Database Configuration  
// --------------------------  
$DB_HOST = 'db.pxxl.pro';  
$DB_PORT = 56224;                // optional, remove if default 3306  
$DB_NAME = 'db_695c9cfd';  
$DB_USER = 'user_71553f4a';  
$DB_PASS = '789304d582936cd894ab650416050655';  

// --------------------------  
// Connect to Database  
// --------------------------  
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

if ($mysqli->connect_error) {  
    // Show error if connection fails  
    die("Database connection failed: " . $mysqli->connect_error);  
}  

// --------------------------  
// Optional: Redirect to index.html if it exists  
// --------------------------  
if (file_exists('index.html')) {  
    header("Location: index.html");  
    exit;  
}  

// --------------------------  
// Fallback message if no index.html  
// --------------------------  
echo "Microcart PHP environment is working! Database connection successful.";  
?>
