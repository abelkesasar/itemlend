<?php
session_start();
require 'config/db.php';

$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ItemLend</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        body { background-color: #f8f9fa; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-white bg-white shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-box-seam me-2"></i>ItemLend</a>
        <div class="ms-auto">
            <?php if (isset($_SESSION['role'])): ?>
                <span class="me-3 text-muted">Hello, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                <a href="actions/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            <?php else: ?>
                <a href="?page=login" class="btn btn-outline-primary btn-sm me-2">Login</a>
                <a href="?page=register" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container pb-5">
<?php
switch($page) {
    case 'login':
        require 'pages/login.php';
        break;

    case 'register':
        require 'pages/register.php';
        break;

    case 'admin_users':
        require 'pages/admin_users.php';
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
                
    default:
        echo "<div class='d-flex justify-content-between align-items-center mb-4'>";
        echo "<h2>Daftar Barang</h2>";
        if (isset($_SESSION['role']) && $_SESSION['role'] == 'user') {
            echo "<a href='?page=tambah_barang' class='btn btn-primary'>+ Tambah Barang</a>";
        }
        echo "</div>";

        if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
            echo "<div class='alert alert-info shadow-sm d-flex justify-content-between align-items-center'>";
            echo "<span><i class='bi bi-shield-lock me-2'></i>Anda login sebagai <strong>ADMIN</strong></span>";
            echo "<div>";
            echo "<a href='admin/dashboard.php' class='btn btn-primary btn-sm me-2'><i class='bi bi-speedometer2 me-1'></i>Dashboard Admin</a>";
            echo "<a href='?page=admin_users' class='btn btn-outline-primary btn-sm'><i class='bi bi-people me-1'></i>Approve User</a>";
            echo "</div>";
            echo "</div>";
        }

        echo "<div class='row g-4'>";
        $data = $conn->query("SELECT * FROM items");
        while($row = $data->fetch()) {
            echo "
            <div class='col-md-4'>
                <div class='card h-100 shadow-sm border-0'>
                    <div class='card-body'>
                        <h5 class='card-title'>".htmlspecialchars($row['nama_barang'])."</h5>
                        <p class='card-text text-muted'>".htmlspecialchars(substr($row['deskripsi'] ?? '', 0, 100))."...</p>
                        <div class='d-flex justify-content-between align-items-center'>
                            <span class='text-primary fw-bold'>Rp ".number_format($row['harga'])."</span>
                            <a href='?page=detail&id=".$row['id']."' class='btn btn-sm btn-outline-primary'>Detail</a>
                        </div>
                    </div>
                </div>
            </div>";
        }
        echo "</div>";
}
?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
