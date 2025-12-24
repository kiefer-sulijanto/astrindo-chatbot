<?php
// Include database connection
require_once 'db.php';

// Check if connection is successful
if ($conn) {
    echo "<h2>Database Connection Successful!</h2>";
    
    // Check if the chat_logs table exists, if not create it
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'chat_logs'");
    if (mysqli_num_rows($check_table) == 0) {
        $create_table = "CREATE TABLE chat_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_message TEXT NOT NULL,
            bot_response TEXT NOT NULL,
            timestamp DATETIME NOT NULL
        )";
        
        if (mysqli_query($conn, $create_table)) {
            echo "<p>Chat logs table created successfully!</p>";
        } else {
            echo "<p>Error creating chat logs table: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>Chat logs table already exists.</p>";
    }
    
    // List tables in the databases
    $result = mysqli_query($conn, "SHOW TABLES");
    
    if (mysqli_num_rows($result) > 0) {
        echo "<h3>Tables in database:</h3>";
        echo "<ul>";
        while($row = mysqli_fetch_array($result)) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tables found in database.</p>";
    }
} else {
    echo "<h2>Database Connection Failed!</h2>";
}
?>
