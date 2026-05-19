<?php
session_start();
require '../config/db.php';

if(!isset($_SESSION['user'])){
    header("Location: ../index.php?page=login");
    exit;
}

$id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
SELECT * FROM users
WHERE id = ?
");

$stmt->execute([$id]);

$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
</head>
<body>

<h1>Profile User</h1>

<form action="actions/update_profile.php"
method="POST"
enctype="multipart/form-data">

    <input
    type="hidden"
    name="id"
    value="<?= $user['id']; ?>">

    Username:
    <br>

    <input
    type="text"
    name="username"
    value="<?= $user['username']; ?>">

    <br><br>

    Email:
    <br>

    <input
    type="email"
    name="email"
    value="<?= $user['email']; ?>">

    <br><br>

    Nomor WA:
    <br>

    <input
    type="text"
    name="nomor_wa"
    value="<?= $user['nomor_wa']; ?>">

    <br><br>

    Alamat:
    <br>

    <textarea
    name="alamat"><?= $user['alamat']; ?></textarea>

    <br><br>

    Password Baru:
    <br>

    <input
    type="password"
    name="password">

    <br><br>

    <?php if($user['role'] == 'user'){ ?>

        Upload KTP:
        <br>

        <input type="file" name="ktp">

        <br><br>

        Upload KTM:
        <br>

        <input type="file" name="ktm">

    <?php } ?>

    <?php if($user['role'] == 'vendor'){ ?>

        Upload KTP Vendor:
        <br>

        <input type="file" name="ktp">

        <br><br>

        Deskripsi Vendor:
        <br>

        <textarea
        name="deskripsi_vendor"><?= $user['deskripsi_vendor']; ?></textarea>

    <?php } ?>

    <br><br>

    <button type="submit">
        Update Profile
    </button>

</form>

</body>
</html>