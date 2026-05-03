<?php
session_start();
require 'config/db.php';

$page = $_GET['page'] ?? 'home';

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
        echo "<h2>Daftar Barang</h2>";

        $data = $conn->query("SELECT * FROM items");

        while($row = $data->fetch()) {
            echo "<p>
                <a href='?page=detail&id=".$row['id']."'>
                    ".$row['nama_barang']."
                </a> - Rp ".$row['harga']."
            </p>";
        }

        echo "<hr>";

        if (!isset($_SESSION['role'])) {
            echo "<a href='?page=login'>Login</a> | ";
            echo "<a href='?page=register'>Register</a>";
        } 
        else if ($_SESSION['role'] == 'admin') {
            echo "<p>Login sebagai ADMIN</p>";
            echo "<a href='?page=admin_users'>Approve User</a><br>";
            echo "<a href='actions/logout.php'>Logout</a>";
        } 
        else {
            echo "<p>Login sebagai USER</p>";
            
            echo "<a href='?page=tambah_barang'>+ Tambah Barang</a><br><br>";
            
            echo "<a href='actions/logout.php'>Logout</a>";
        }
}
?>