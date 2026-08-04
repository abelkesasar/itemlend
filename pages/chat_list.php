<?php
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}

$my_id = $_SESSION['user'];

// Ambil pesan TERAKHIR per lawan chat — sekarang digroup per orang, bukan per barang lagi
$sql = "
  SELECT c.*
  FROM chats c
  INNER JOIN (
      SELECT
        IF(sender_id = ?, receiver_id, sender_id) AS lawan_id,
        MAX(id) AS last_id
      FROM chats
      WHERE sender_id = ? OR receiver_id = ?
      GROUP BY lawan_id
  ) t ON c.id = t.last_id
  ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$my_id, $my_id, $my_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$conversations = [];
foreach ($rows as $row) {
    $lawan_id = $row['sender_id'] == $my_id ? $row['receiver_id'] : $row['sender_id'];

    $stmt = $conn->prepare("SELECT username, foto_profil FROM users WHERE id = ?");
    $stmt->execute([$lawan_id]);
    $lawan = $stmt->fetch(PDO::FETCH_ASSOC);

    $preview = $row['type'] === 'item' ? '📦 Mengirim info barang' : $row['pesan'];

    $conversations[] = [
        'lawan_id'       => $lawan_id,
        'lawan_nama'     => $lawan['username'] ?? 'Pengguna',
        'lawan_foto'     => $lawan['foto_profil'] ?? 'assets/default-user.png',
        'pesan_terakhir' => $preview,
        'waktu'          => $row['created_at'],
        'dari_saya'      => $row['sender_id'] == $my_id,
    ];
}
?>
<style>
  .header { padding: 16px; font-size: 18px; font-weight: bold; border-bottom: 1px solid #eee; }
  .convo { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #f0f0f0; text-decoration: none; color: inherit; }
  .convo:hover { background: #f9f9f9; }
  .convo img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; background: #ddd; }
  .convo .info { flex: 1; min-width: 0; }
  .convo .top-row { display: flex; justify-content: space-between; }
  .convo .nama { font-weight: bold; font-size: 14px; }
  .convo .waktu { font-size: 11px; color: #999; }
  .convo .preview { font-size: 13px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .empty { padding: 40px 16px; text-align: center; color: #999; }
</style>

<div class="header">Chat</div>

<?php if (empty($conversations)): ?>
  <div class="empty">Belum ada percakapan.</div>
<?php else: ?>
  <?php foreach ($conversations as $c): ?>
    <a class="convo" href="index.php?page=chat&seller_id=<?= $c['lawan_id'] ?>">
      <img src="<?= htmlspecialchars($c['lawan_foto']) ?>" alt="">
      <div class="info">
        <div class="top-row">
          <span class="nama"><?= htmlspecialchars($c['lawan_nama']) ?></span>
          <span class="waktu"><?= date('d M, H:i', strtotime($c['waktu'])) ?></span>
        </div>
        <div class="preview">
          <?= $c['dari_saya'] ? 'Kamu: ' : '' ?><?= htmlspecialchars($c['pesan_terakhir']) ?>
        </div>
      </div>
    </a>
  <?php endforeach; ?>
<?php endif; ?>