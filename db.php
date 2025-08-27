<?php
// Database connection parameters
$host = 'localhost';
$username = 'root'; // Default MySQL username
$password = ''; // Default MySQL password is often empty
$database = 'digitalapproval'; // Database name based on your SQL file

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to ensure proper handling of special characters
mysqli_set_charset($conn, "utf8mb4");

// Function to sanitize user inputs
function sanitize_input($conn, $data) {
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Function to log chat interactions to database
function log_chat($conn, $user_message, $bot_response) {
    $user_message = sanitize_input($conn, $user_message);
    $bot_response = sanitize_input($conn, $bot_response);
    $timestamp = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO chat_logs (user_message, bot_response, timestamp) 
              VALUES ('$user_message', '$bot_response', '$timestamp')";
    
    return mysqli_query($conn, $query);
}
?>