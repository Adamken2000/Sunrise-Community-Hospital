<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'hospital_management';

$conn = mysqli_connect($host,$user,$password,$database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}




?>