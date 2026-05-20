<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    echo "Harus login!";
    exit;
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM items WHERE id=?");
$stmt->execute([$id]);

$item = $stmt->fetch();
?>

<div class="container mt-5">

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden">

        <div class="row g-0">

            <!-- GAMBAR -->
            <div class="col-md-5">

                <img 
                src="uploads/<?= $item['gambar'] ?>" 
                class="w-100 h-100 object-fit-cover"
                style="height: 450px; object-fit: cover;">

            </div>

            <!-- FORM -->
            <div class="col-md-7">

                <div class="card-body p-5">

                    <h2 class="fw-bold mb-3">
                        <?= htmlspecialchars($item['nama_barang']) ?>
                    </h2>

                    <p class="text-muted">
                        <?= htmlspecialchars($item['deskripsi']) ?>
                    </p>

                    <h3 class="text-primary fw-bold mb-4">
                        Rp <?= number_format($item['harga']) ?> / hari
                    </h3>

                    <form action="actions/sewa.php" method="POST">

                        <input type="hidden" name="item_id" value="<?= $id ?>">

                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Tanggal Mulai
                            </label>

                            <input 
                            type="date" 
                            name="start" 
                            class="form-control"
                            min="<?= date('Y-m-d') ?>"
                            required>

                        </div>

                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Tanggal Selesai
                            </label>

                            <input 
                            type="date" 
                            name="end" 
                            class="form-control"
                            min="<?= date('Y-m-d') ?>"
                            required>

                        </div>

                        <button 
                        type="submit"
                        class="btn btn-primary w-100 py-2 rounded-3">

                            Sewa Sekarang

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>