<?php
session_start();
require 'config/db.php';

$page = $_GET['page'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ItemLend</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>

        body{
            background:#f4f7fb;
        }

        .navbar-brand{
            font-weight:bold;
            color:#0d6efd !important;
            font-size:24px;
        }

        .hero{
            background: linear-gradient(135deg, #0d6efd, #4f46e5);
            color:white;
            border-radius:25px;
            padding:60px;
        }

        .card-item{
            border:none;
            border-radius:20px;
            overflow:hidden;
            transition:0.3s;
        }

        .card-item:hover{
            transform:translateY(-5px);
            box-shadow:0 10px 30px rgba(0,0,0,0.1);
        }

        .card-item img{
            height:230px;
            object-fit:cover;
        }

        .btn-custom{
            border-radius:12px;
        }

    </style>

</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">

    <div class="container">

        <a class="navbar-brand" href="index.php">
            <i class="bi bi-box-seam"></i> ItemLend
        </a>

        <div class="ms-auto">

        <?php if (isset($_SESSION['role'])): ?>

<span class="me-3 text-muted">
    Hello, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
</span>

<a href="?page=profile" class="btn btn-outline-dark btn-sm me-2">
    Profil
</a>

<a href="actions/logout.php" class="btn btn-outline-danger btn-sm">
    Logout
</a>

<?php else: ?>

                <a href="?page=login" class="btn btn-outline-primary btn-sm btn-custom me-2">
                    Login
                </a>

                <a href="?page=register" class="btn btn-primary btn-sm btn-custom">
                    Register
                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>

<div class="container py-5">

<?php

switch($page){

    case 'login':
        require 'pages/login.php';
        break;

    case 'register':
        require 'pages/register.php';
        break;

    case 'tambah_barang':
        require 'pages/tambah_barang.php';
        break;

    case 'detail':
        require 'pages/detail.php';
        break;

    case 'sewa':
        require 'pages/sewa.php';
        break;

    case 'edit_barang':
        require 'pages/edit_barang.php';
        break;

    case 'wishlist':
        require 'pages/wishlist.php';
        break;
    case 'profile':
        require 'pages/profile.php';
        break;
    case 'chat':
        require 'pages/chat.php';
        break;

    case 'profile':
        require 'pages/profile.php';
        break;

    default:

        $stmt = $conn->query("
            SELECT items.*, users.username
            FROM items
            JOIN users ON items.user_id = users.id
            ORDER BY items.id DESC
        ");

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- HERO -->
<div class="hero mb-5 shadow">

    <div class="row align-items-center">

        <div class="col-md-8">

            <h1 class="display-4 fw-bold">
                Sewa Barang Jadi Lebih Mudah
            </h1>

            <p class="mt-3 fs-5">
                Cari barang yang kamu butuhkan atau sewakan barang milikmu
                dengan aman dan praktis bersama ItemLend.
            </p>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'user'): ?>

                <a href="?page=tambah_barang"
                   class="btn btn-light btn-lg mt-3 btn-custom">

                    + Tambah Barang

                </a>

            <?php endif; ?>

        </div>

    </div>

</div>

<!-- TITLE -->
<div class="d-flex justify-content-between align-items-center mb-4">

    <h2 class="fw-bold">
        Daftar Barang
    </h2>

</div>

<!-- LIST BARANG -->
<div class="row g-4">

<?php foreach($items as $item): ?>

<?php

if(!empty($item['gambar']) && file_exists("uploads/" . $item['gambar'])){

    $gambar = "uploads/" . $item['gambar'];

}else{

    $gambar = "https://via.placeholder.com/400x300?text=No+Image";

}

?>

<div class="col-md-4">

    <div class="card card-item shadow-sm h-100">

        <!-- GAMBAR -->
        <img src="<?= $gambar ?>">

        <!-- BODY -->
        <div class="card-body d-flex flex-column">

            <h4 class="fw-bold">
                <?= htmlspecialchars($item['nama_barang']) ?>
            </h4>

            <p class="text-muted">
                <?= htmlspecialchars(substr($item['deskripsi'], 0, 70)) ?>...
            </p>

            <div class="mt-auto">

                <h5 class="text-primary fw-bold">
                    Rp <?= number_format($item['harga']) ?>
                </h5>

                <small class="text-muted">
                    Upload by <?= htmlspecialchars($item['username']) ?>
                </small>

                <a href="?page=detail&id=<?= $item['id'] ?>"
                   class="btn btn-primary w-100 mt-3 btn-custom">

                    Lihat Detail

                </a>

            </div>

        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

<?php
break;
}
?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>