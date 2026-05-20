<?php
session_start();
require 'config/db.php';

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
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
    <title>Edit Barang</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-2xl mx-auto py-10">

    <div class="bg-white p-8 rounded-3xl shadow-lg">

        <h1 class="text-3xl font-bold mb-8">
            Edit Barang
        </h1>

        <form
        action="actions/edit_barang.php"
        method="POST"
        enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?= $item['id'] ?>">

            <div class="mb-5">

                <label class="font-semibold">
                    Nama Barang
                </label>

                <input
                type="text"
                name="nama_barang"
                value="<?= htmlspecialchars($item['nama_barang']) ?>"
                class="w-full border p-3 rounded-xl mt-2">

            </div>

            <div class="mb-5">

                <label class="font-semibold">
                    Deskripsi
                </label>

                <textarea
                name="deskripsi"
                class="w-full border p-3 rounded-xl mt-2 h-32"><?= htmlspecialchars($item['deskripsi']) ?></textarea>

            </div>

            <div class="mb-5">

                <label class="font-semibold">
                    Harga
                </label>

                <input
                type="number"
                name="harga"
                value="<?= $item['harga'] ?>"
                class="w-full border p-3 rounded-xl mt-2">

            </div>

            <div class="mb-5">

                <label class="font-semibold">
                    Gambar Lama
                </label>

                <img
                src="uploads/<?= $item['gambar'] ?>"
                class="w-full h-60 object-cover rounded-2xl mt-3">
            </div>

            <div class="mb-5">

                <label class="font-semibold">
                    Upload Gambar Baru
                </label>

                <input
                type="file"
                name="gambar"
                class="w-full border p-3 rounded-xl mt-2">

            </div>

            <button
            type="submit"
            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-2xl">

                Update Barang

            </button>

        </form>

    </div>

</div>

</body>
</html>