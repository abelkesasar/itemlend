<?php
require '../config/db.php';

$username = $_POST['username'];
$password = md5($_POST['password']);
$alamat = $_POST['alamat'];
$role = $_POST['role'];
$email = $_POST['email'];
$nomor_wa = $_POST['nomor_wa'];
$status = 'pending';

$ktp = null;
$ktm = null;
$deskripsi_vendor = null;

/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
*/
if($role == 'user'){

    $ktp = $_FILES['ktp_user']['name'];
    $ktm = $_FILES['ktm']['name'];

    $tmpKtp = $_FILES['ktp_user']['tmp_name'];
    $tmpKtm = $_FILES['ktm']['tmp_name'];

    move_uploaded_file(
        $tmpKtp,
        "../uploads/" . $ktp
    );

    move_uploaded_file(
        $tmpKtm,
        "../uploads/" . $ktm
    );
}

/*
|--------------------------------------------------------------------------
| VENDOR
|--------------------------------------------------------------------------
*/
else if($role == 'vendor'){

    $ktp = $_FILES['ktp_vendor']['name'];

    $tmpKtp = $_FILES['ktp_vendor']['tmp_name'];

    move_uploaded_file(
        $tmpKtp,
        "../uploads/" . $ktp
    );

    $deskripsi_vendor =
    $_POST['deskripsi_vendor'];
}

/*
|--------------------------------------------------------------------------
| INSERT DATABASE
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
INSERT INTO users
(
    username,
    password,
    alamat,
    role,
    ktp,
    ktm,
    deskripsi_vendor,
    status
)

VALUES
(
    ?,?,?,?,?,?,?,?
)
");

$stmt->execute([
    $username,
    $password,
    $alamat,
    $role,
    $ktp,
    $ktm,
    $deskripsi_vendor,
    $status
]);

echo "
<script>
alert('Register berhasil, tunggu approval admin!');
window.location='../index.php?page=login';
</script>
";
?>