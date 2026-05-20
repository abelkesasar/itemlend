<?php
if (!isset($_SESSION['user'])) {
    echo "Harus login!";
    exit;
}

$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT * FROM items
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$user_id]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="fw-bold mb-4">Profil Saya</h2>

<div class="row g-4">

<?php foreach($items as $item): ?>

<?php
$gambar = "uploads/" . $item['gambar'];

if(empty($item['gambar']) || !file_exists($gambar)){
    $gambar = "https://via.placeholder.com/400x300?text=No+Image";
}
?>

<div class="col-md-4">

    <div class="card shadow-sm border-0 h-100">

        <img
            src="<?= $gambar ?>"
            class="card-img-top"
            style="height:220px; object-fit:cover;"
        >

        <div class="card-body d-flex flex-column">

            <h5 class="fw-bold">
                <?= htmlspecialchars($item['nama_barang']) ?>
            </h5>

            <p class="text-muted">
                <?= htmlspecialchars(substr($item['deskripsi'],0,80)) ?>...
            </p>

            <div class="mt-auto">

                <h5 class="text-primary fw-bold">
                    Rp <?= number_format($item['harga']) ?>
                </h5>

                <a
                    href="?page=edit_barang&id=<?= $item['id'] ?>"
                    class="btn btn-warning w-100 mt-3"
                >
                    Edit Barang
                </a>

            </div>

        </div>

    </div>

</div>

<?php endforeach; ?>

</div>