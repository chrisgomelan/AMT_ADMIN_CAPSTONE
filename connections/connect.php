<?php
function connection() {
    $host = "localhost";
    $username = "root";
    $password = ""; // Or your MySQL password
    $database = "dostlib_db";

    $mysqli = new mysqli($host, $username, $password, $database);
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    } else {
        return $mysqli;
    }
}
?>
