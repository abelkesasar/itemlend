<?php
session_start();
require '../config/db.php';

$id = $_SESSION['user'];

$username = $_POST['username'];
$email = $_POST['email'];
$nomor_wa = $_POST['nomor_wa'];
$alamat = $_POST['alamat'];

$stmtUser = $conn->prepare("
SELECT * FROM users
WHERE id = ?
");

$stmtUser->execute([$id]);

$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

$foto_profil = $user['foto_profil'] ?? '';

if(isset($_FILES['foto_profil']) &&
$_FILES['foto_profil']['name'] != ''){

    $namaFile = time() . '_' . $_FILES['foto_profil']['name'];

    move_uploaded_file(
        $_FILES['foto_profil']['tmp_name'],
        "../uploads/" . $namaFile
    );

    $foto_profil = $namaFile;
}

$stmt = $conn->prepare("
UPDATE users
SET
username = ?,
email = ?,
nomor_wa = ?,
alamat = ?,
foto_profil = ?
WHERE id = ?
");

$stmt->execute([
    $username,
    $email,
    $nomor_wa,
    $alamat,
    $foto_profil,
    $id
]);

$_SESSION['username'] = $username;

echo "
<script>
alert('Profil berhasil diupdate!');
window.location='../index.php?page=profile';
</script>
";
?>