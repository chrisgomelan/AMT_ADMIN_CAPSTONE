<?php
// Include your connection function or establish connection here
include 'connections/connect.php';
$conn = connection(); // Assuming connection() function returns the mysqli connection

// Check if email is provided and not empty
if (isset($_POST['email']) && !empty($_POST['email'])) {
    $email = $_POST['email'];

    // Prepare the delete statement
    $sql = "DELETE FROM visitortbl WHERE email = ?";

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);

    // Execute the statement
    if ($stmt->execute()) {
        // Return success JSON response if deletion is successful
        echo json_encode(array('success' => true));
    } else {
        // Return error JSON response if deletion fails
        echo json_encode(array('success' => false, 'message' => 'Failed to delete visitor.'));
    }

    // Close statement
    $stmt->close();
} else {
    // Return error JSON response if email parameter is missing
    echo json_encode(array('success' => false, 'message' => 'Email parameter is missing or empty.'));
}

// Close the database connection
$conn->close();
?>
