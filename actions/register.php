<?php
require '../config/db.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = md5($_POST['password']);
$alamat = $_POST['alamat'];
$role = $_POST['role'];
$nomor_wa = $_POST['nomor_wa'];

$status = 'pending';

$ktp = null;
$ktm = null;
$deskripsi_vendor = null;
$foto_profil = 'default.png';

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
    email,
    password,
    alamat,
    nomor_wa,
    role,
    status,
    foto_profil,
    ktp,
    ktm,
    deskripsi_vendor
)

VALUES
(
    ?,?,?,?,?,?,?,?,?,?,?
)
");

$stmt->execute([
    $username,
    $email,
    $password,
    $alamat,
    $nomor_wa,
    $role,
    $status,
    $foto_profil,
    $ktp,
    $ktm,
    $deskripsi_vendor
]);

echo "
<script>
alert('Register berhasil!');
window.location='../index.php?page=login';
</script>
";
?>