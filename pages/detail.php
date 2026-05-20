<?php
require 'config/db.php';

$id = $_GET['id'];

$stmt = $conn->prepare("
    SELECT items.*, users.username
    FROM items
    JOIN users ON items.user_id = users.id
    WHERE items.id = ?
");

$stmt->execute([$id]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$item){
    echo "Barang tidak ditemukan";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Barang</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-5xl mx-auto py-10 px-6">

    <div class="bg-white rounded-3xl shadow-lg overflow-hidden grid md:grid-cols-2">

        <!-- GAMBAR -->
        <div>

            <?php
            $gambar = !empty($item['gambar'])
                ? "uploads/" . $item['gambar']
                : "https://via.placeholder.com/600x400?text=No+Image";
            ?>

         <img
            src="uploads/<?= $item['gambar'] ?>"
            class="img-fluid rounded mb-3">

        </div>

        <!-- DETAIL -->
        <div class="p-8">

            <h1 class="text-4xl font-bold text-gray-800">
                <?= htmlspecialchars($item['nama_barang']) ?>
            </h1>

            <div class="mt-4 text-3xl font-bold text-green-600">
                Rp <?= number_format($item['harga']) ?>
                <span class="text-base text-gray-500 font-normal">
                    / hari
                </span>
            </div>

            <div class="mt-6 text-gray-600 leading-relaxed">
                <?= nl2br(htmlspecialchars($item['deskripsi'])) ?>
            </div>

            <div class="mt-6 text-sm text-gray-500">
                Pemilik:
                <span class="font-semibold text-gray-700">
                    <?= htmlspecialchars($item['username']) ?>
                </span>
            </div>

            <div class="mt-8 flex gap-4">

            <?php if($item['user_id'] != $_SESSION['user']['id']): ?>

<a
    href="?page=sewa&id=<?= $item['id'] ?>"
    class="btn btn-primary"
>
    Sewa Sekarang
</a>

<?php else: ?>

<button class="btn btn-secondary" disabled>
    Ini Barang Milik Anda
</button>

<?php endif; ?>

                <?php if(isset($_SESSION['user']) && $_SESSION['user']['id'] == $item['user_id']): ?>

                   

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

</body>
</html>