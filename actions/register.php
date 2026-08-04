<?php
require '../config/db.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = md5($_POST['password']);
$alamat = $_POST['alamat'];
$nomor_wa = $_POST['nomor_wa'];

$role = 'user';
$status = 'pending';

$ktp = null;
$ktm = null;
$deskripsi_vendor = null;
$foto_profil = 'default.png';

/*
|--------------------------------------------------------------------------
| UPLOAD DOKUMEN (KTP & KTM)
|--------------------------------------------------------------------------
*/

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