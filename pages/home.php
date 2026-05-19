<?php
require 'config/db.php';

$stmt = $conn->query("
    SELECT items.*, users.username 
    FROM items
    JOIN users ON items.user_id = users.id
    ORDER BY items.id DESC
");

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>ItemLend</title>

    <style>
        body{
            font-family: Arial;
            background:#f5f5f5;
            margin:0;
        }

        .navbar{
            background:white;
            padding:15px 30px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            border-bottom:1px solid #ddd;
        }

        .logo{
            font-size:30px;
            font-weight:bold;
            color:#2563eb;
        }

        .menu a{
            text-decoration:none;
            margin-left:10px;
            padding:8px 15px;
            border-radius:5px;
        }

        .login{
            border:1px solid #2563eb;
            color:#2563eb;
        }

        .register{
            background:#2563eb;
            color:white;
        }

        .container{
            width:90%;
            margin:auto;
            margin-top:30px;
        }

        .grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
            gap:20px;
        }

        .card{
            background:white;
            border-radius:10px;
            padding:15px;
            box-shadow:0 2px 5px rgba(0,0,0,0.1);
        }

        .card img{
            width:100%;
            height:200px;
            object-fit:cover;
            border-radius:10px;
        }

        .nama{
            font-size:20px;
            font-weight:bold;
            margin-top:10px;
        }

        .harga{
            color:green;
            margin-top:5px;
        }

        .owner{
            color:gray;
            font-size:14px;
            margin-top:5px;
        }

        .btn{
            display:inline-block;
            margin-top:10px;
            background:#2563eb;
            color:white;
            padding:8px 15px;
            border-radius:5px;
            text-decoration:none;
        }

        .kosong{
            background:white;
            padding:20px;
            border-radius:10px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">ItemLend</div>

    <div class="menu">
        <a class="login" href="index.php?page=login">Login</a>
        <a class="register" href="index.php?page=register">Register</a>
    </div>
</div>

<div class="container">

    <h1>Daftar Barang</h1>

    <?php if(count($items) > 0): ?>

        <div class="grid">

            <?php foreach($items as $item): ?>

                <div class="card">

                    <?php if($item['gambar'] != ''): ?>
                        <img src="uploads/<?= $item['gambar'] ?>">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/300x200">
                    <?php endif; ?>

                    <div class="nama">
                        <?= $item['nama_barang'] ?>
                    </div>

                    <div class="harga">
                        Rp <?= number_format($item['harga']) ?> / hari
                    </div>

                    <div class="owner">
                        Pemilik: <?= $item['username'] ?>
                    </div>

                    <a class="btn" href="index.php?page=detail&id=<?= $item['id'] ?>">
                        Detail
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="kosong">
            Belum ada barang
        </div>

    <?php endif; ?>

</div>

</body>
</html>