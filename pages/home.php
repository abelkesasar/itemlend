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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ItemLend</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<!-- NAVBAR -->
<nav class="bg-white shadow-md sticky top-0 z-50">

    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

        <div>
            <h1 class="text-3xl font-bold text-blue-600">
                ItemLend
            </h1>
        </div>

        <div class="flex items-center gap-4">

            <a
            href="index.php?page=login"
            class="border border-blue-500 text-blue-500 px-5 py-2 rounded-xl hover:bg-blue-50 transition">

                Login

            </a>

            <a
            href="index.php?page=register"
            class="bg-blue-500 text-white px-5 py-2 rounded-xl hover:bg-blue-600 transition">

                Register

            </a>

        </div>

    </div>

</nav>

<!-- HERO -->
<section class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-20">

    <div class="max-w-7xl mx-auto px-6">

        <h1 class="text-5xl font-bold leading-tight">
            Sewa Barang Jadi Lebih Mudah
        </h1>

        <p class="mt-5 text-lg opacity-90 max-w-2xl">
            Cari barang yang kamu butuhkan atau sewakan barang milikmu
            dengan aman dan praktis bersama ItemLend.
        </p>

        <div class="mt-8 flex gap-4">

            <a
            href="index.php?page=register"
            class="bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-gray-100 transition">

                Mulai Sekarang

            </a>

            <a
            href="#barang"
            class="border border-white px-6 py-3 rounded-xl font-semibold hover:bg-white hover:text-blue-600 transition">

                Lihat Barang

            </a>

        </div>

    </div>

</section>

<!-- CONTENT -->
<section id="barang" class="max-w-7xl mx-auto px-6 py-12">

    <div class="flex items-center justify-between mb-8">

        <div>
            <h2 class="text-4xl font-bold text-gray-800">
                Daftar Barang
            </h2>

            <p class="text-gray-500 mt-2">
                Temukan barang terbaik untuk disewa
            </p>
        </div>

    </div>

    <?php if(count($items) > 0): ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">

        <?php foreach($items as $item): ?>

<?php
$gambar = "uploads/default.png";

if(isset($item['gambar']) && $item['gambar'] != ''){

    if(file_exists(__DIR__ . "/uploads/" . $item['gambar'])){

        $gambar = "uploads/" . $item['gambar'];

    }

}
?>

<div class="bg-white rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl transition duration-300">

    <!-- GAMBAR -->
    <div class="w-full h-60 bg-gray-200 overflow-hidden">

        <img
        src="<?= $gambar ?>"
        alt="gambar barang"
        class="w-full h-full object-cover">

    </div>

    <!-- ISI -->
    <div class="p-5">

        <h2 class="text-2xl font-bold text-gray-800">
            <?= htmlspecialchars($item['nama_barang']) ?>
        </h2>

        <p class="text-gray-500 mt-2">
            <?= htmlspecialchars($item['deskripsi']) ?>
        </p>

        <div class="mt-4 text-2xl font-bold text-green-600">
            Rp <?= number_format($item['harga']) ?>
        </div>

        <div class="mt-2 text-sm text-gray-500">
            <?= htmlspecialchars($item['username']) ?>
        </div>

        <a
        href="index.php?page=detail&id=<?= $item['id'] ?>"
        class="mt-5 block text-center bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-2xl">

            Lihat Detail

        </a>

    </div>

</div>

<?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="bg-white rounded-3xl shadow-lg p-16 text-center">

            <img
            src="https://cdn-icons-png.flaticon.com/512/4076/4076549.png"
            class="w-32 mx-auto mb-6">

            <h2 class="text-3xl font-bold text-gray-700">
                Belum Ada Barang
            </h2>

            <p class="text-gray-500 mt-3">
                Jadilah yang pertama menambahkan barang untuk disewakan.
            </p>

        </div>

    <?php endif; ?>

</section>

<!-- FOOTER -->
<footer class="bg-white border-t mt-16">

    <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col md:flex-row justify-between items-center">

        <div>

            <h2 class="text-2xl font-bold text-blue-600">
                ItemLend
            </h2>

            <p class="text-gray-500 mt-2">
                Platform penyewaan barang terpercaya
            </p>

        </div>

        <div class="text-gray-400 mt-4 md:mt-0">
            © <?= date('Y') ?> ItemLend. All rights reserved.
        </div>

    </div>

</footer>

</body>
</html>