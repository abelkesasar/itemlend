<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Barang</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-2xl mx-auto mt-10 bg-white p-8 rounded-2xl shadow">

    <h1 class="text-3xl font-bold mb-6">
        Tambah Barang
    </h1>

    <form
    action="actions/tambah_barang.php"
    method="POST"
    enctype="multipart/form-data">

        <div class="mb-5">

            <label class="font-semibold">
                Nama Barang
            </label>

            <input
            type="text"
            name="nama_barang"
            required
            class="w-full border p-3 rounded-xl mt-2">

        </div>

        <div class="mb-5">

            <label class="font-semibold">
                Deskripsi
            </label>

            <textarea
            name="deskripsi"
            class="w-full border p-3 rounded-xl mt-2 h-32"></textarea>

        </div>

        <div class="mb-5">

            <label class="font-semibold">
                Harga
            </label>

            <input
            type="number"
            name="harga"
            required
            class="w-full border p-3 rounded-xl mt-2">

        </div>

        <div class="mb-5">

            <label class="font-semibold">
                Upload Gambar
            </label>

            <input
            type="file"
            name="gambar"
            accept="image/*"
            required
            class="w-full border p-3 rounded-xl mt-2">

        </div>

        <button
        type="submit"
        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-xl">

            Simpan Barang

        </button>

    </form>

</div>

</body>
</html>