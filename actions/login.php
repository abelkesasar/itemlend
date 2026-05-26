<?php
session_start();

require '../config/db.php';

$username = $_POST['username'];
$password = md5($_POST['password']);

$stmt = $conn->prepare("
SELECT * FROM users
WHERE username = ?
");

$stmt->execute([$username]);

$user = $stmt->fetch();

if(!$user){

    echo "
    <script>
    alert('Email tidak ditemukan!');
    window.location='../index.php?page=login';
    </script>
    ";
    exit;
}

if($user['password'] != $password){

    echo "
    <script>
    alert('Password salah!');
    window.location='../index.php?page=login';
    </script>
    ";
    exit;
}

/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

$_SESSION['user'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

if($user['role'] == 'admin'){

    header("Location: ../admin/dashboard.php");
    exit;

}else{

    header("Location: ../index.php?page=home");
    exit;
}
?>