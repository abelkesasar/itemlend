<?php
// data dummy (nanti diganti dari database)
$barang = [
    ["nama" => "Kamera DSLR", "harga" => 50000],
    ["nama" => "Laptop Gaming", "harga" => 100000],
    ["nama" => "Projector", "harga" => 75000],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Lend</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <nav class="bg-white p-4 shadow flex justify-between">
        <h1 class="font-bold text-xl">Item Lend</h1>
    </nav>

    <section class="p-8">
        <h2 class="text-2xl mb-6">Daftar Barang</h2>

        <div class="grid grid-cols-3 gap-6">
            
            <?php foreach($barang as $b): ?>
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="font-bold"><?= $b['nama']; ?></h3>
                    <p>Rp <?= number_format($b['harga']); ?> / hari</p>
                    <button class="mt-2 bg-amber-500 text-white px-3 py-1 rounded">
                        Sewa
                    </button>
                </div>
            <?php endforeach; ?>

        </div>
    </section>

</body>
</html>