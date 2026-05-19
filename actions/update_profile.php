<?php
session_start();
require '../config/db.php';

$id = $_POST['id'];
$username = $_POST['username'];
$email = $_POST['email'];
$nomor_wa = $_POST['nomor_wa'];
$alamat = $_POST['alamat'];

$stmtUser = $conn->prepare("
SELECT * FROM users
WHERE id = ?
");

$stmtUser->execute([$id]);

$user = $stmtUser->fetch();

$password = $user['password'];

if(!empty($_POST['password'])){
    $password = md5($_POST['password']);
}

$ktp = $user['ktp'];
$ktm = $user['ktm'];

if(isset($_FILES['ktp']) &&
$_FILES['ktp']['name'] != ''){

    $ktp = $_FILES['ktp']['name'];

    move_uploaded_file(
        $_FILES['ktp']['tmp_name'],
        "../uploads/" . $ktp
    );
}

if(isset($_FILES['ktm']) &&
$_FILES['ktm']['name'] != ''){

    $ktm = $_FILES['ktm']['name'];

    move_uploaded_file(
        $_FILES['ktm']['tmp_name'],
        "../uploads/" . $ktm
    );
}

$deskripsi_vendor = $user['deskripsi_vendor'];

if(isset($_POST['deskripsi_vendor'])){
    $deskripsi_vendor =
    $_POST['deskripsi_vendor'];
}

$stmt = $conn->prepare("
UPDATE users
SET
username = ?,
password = ?,
email = ?,
nomor_wa = ?,
alamat = ?,
ktp = ?,
ktm = ?,
deskripsi_vendor = ?
WHERE id = ?
");

$stmt->execute([
    $username,
    $password,
    $email,
    $nomor_wa,
    $alamat,
    $ktp,
    $ktm,
    $deskripsi_vendor,
    $id
]);

$_SESSION['user']['username'] =
$username;

echo "
<script>
alert('Profile berhasil di update!');
window.location='../index.php?page=profile';
</script>
";
?>