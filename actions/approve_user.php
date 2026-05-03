<?php
require '../config/db.php';

$id = $_GET['id'];

$conn->query("UPDATE users SET status='approved' WHERE id=$id");

header("Location: ../index.php?page=admin_users");
?>