<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'koneksi.php'; 

$username = $_POST['username'];
$email = $_POST['email'];
$nomor_wa = $_POST['nomor_wa']; 
$password = md5($_POST['password']); 
$role = isset($_POST['role']) ? $_POST['role'] : 'user'; 

$status = 'approved';
$foto_profil = 'default.png';
$approved_num = 0; 

// Siapkan variabel kosong untuk ktm dan ktp
$nama_ktm = "";
$nama_ktp = "";

$cek_email = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");
if(mysqli_num_rows($cek_email) > 0){
    echo json_encode(["success" => false, "message" => "Email sudah terdaftar!"]);
    exit();
}

// PROSES UPLOAD FOTO
// Di Flutter kita ngirim file-nya dengan key 'dokumen'
if(isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] == 0) {
    $ekstensi = pathinfo($_FILES['dokumen']['name'], PATHINFO_EXTENSION);
    $nama_file_baru = $role . '_' . time() . '.' . $ekstensi; 
    
    // Pindahkan file ke folder uploads
    move_uploaded_file($_FILES['dokumen']['tmp_name'], 'uploads/' . $nama_file_baru);
    
    // LOGIKA PEMISAHAN: Masukkan nama file ke variabel yang tepat
    if ($role == 'user') {
        $nama_ktm = $nama_file_baru;
    } else if ($role == 'vendor') {
        $nama_ktp = $nama_file_baru;
    }
}

// INSERT KE DATABASE (Sekarang menggunakan kolom ktm dan ktp)
$query = "INSERT INTO users (username, email, nomor_wa, password, role, status, approved, foto_profil, ktm, ktp) 
          VALUES ('$username', '$email', '$nomor_wa', '$password', '$role', '$status', '$approved_num', '$foto_profil', '$nama_ktm', '$nama_ktp')";
          
if(mysqli_query($koneksi, $query)){
    echo json_encode(["success" => true, "message" => "Registrasi berhasil"]);
} else {
    echo json_encode(["success" => false, "message" => "Gagal register: " . mysqli_error($koneksi)]);
}
?>