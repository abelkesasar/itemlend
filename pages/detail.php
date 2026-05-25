<?php
require 'config/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "ID tidak valid";
    exit;
}

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

// session user amanin (karena INT)
$user_login = $_SESSION['user'] ?? null;
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
            $gambar = (!empty($item['gambar']) && file_exists("uploads/" . $item['gambar']))
                ? "uploads/" . $item['gambar']
                : "https://via.placeholder.com/600x400?text=No+Image";
            ?>

            <img
                src="<?= $gambar ?>"
                class="w-full h-full object-cover"
            >
        </div>

        <!-- DETAIL -->
        <div class="p-8">

            <h1 class="text-4xl font-bold text-gray-800">
                <?= htmlspecialchars($item['nama_barang']) ?>
            </h1>

            <div class="mt-4 text-3xl font-bold text-green-600">
                Rp <?= number_format($item['harga']) ?>
                <span class="text-base text-gray-500 font-normal">/ hari</span>
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

            <div class="mt-8">

                <?php if(!$user_login): ?>

                    <a href="index.php?page=login"
                       class="px-4 py-2 bg-blue-500 text-white rounded-xl">
                        Login untuk menyewa
                    </a>

                <?php elseif($item['user_id'] == $user_login): ?>

                    <button class="px-4 py-2 bg-gray-400 text-white rounded-xl" disabled>
                        Ini barang milik Anda
                    </button>

                <?php else: ?>

                    <a href="?page=sewa&id=<?= $item['id'] ?>"
                       class="px-4 py-2 bg-green-500 text-white rounded-xl">
                        Sewa Sekarang
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

</body>
</html>