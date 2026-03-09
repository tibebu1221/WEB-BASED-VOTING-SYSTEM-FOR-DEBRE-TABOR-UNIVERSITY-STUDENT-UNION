<?php
$conn = mysqli_connect("localhost", "root", "", "onlinevote");

if (mysqli_connect_errno()) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>