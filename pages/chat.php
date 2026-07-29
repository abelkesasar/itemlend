<?php
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}

$my_id       = $_SESSION['user'];
$item_id     = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$receiver_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if (!$item_id || !$receiver_id) {
    die('Item atau penjual tidak ditemukan.');
}

// Ambil info barang & lawan chat, buat header halaman
$stmt = $conn->prepare("SELECT nama_barang, gambar FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
$item_gambar = (!empty($item['gambar']) && file_exists("uploads/" . $item['gambar']))
    ? "uploads/" . $item['gambar'] : 'assets/default-item.png';

$stmt = $conn->prepare("SELECT username, foto_profil FROM users WHERE id = ?");
$stmt->execute([$receiver_id]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil semua riwayat pesan antara saya <-> penjual ini, khusus untuk barang ini
$stmt = $conn->prepare(
    "SELECT id, sender_id, receiver_id, pesan, created_at FROM chats
     WHERE item_id = ?
       AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
     ORDER BY created_at ASC"
);
$stmt->execute([$item_id, $my_id, $receiver_id, $receiver_id, $my_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Chat - <?= htmlspecialchars($seller['username'] ?? 'Penjual') ?></title>
<style>
  body { font-family: Arial, sans-serif; background: #f2f2f2; margin: 0; }
  .chat-header { background: #fff; padding: 12px 16px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #ddd; position: sticky; top: 0; }
  .chat-header img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; background: #ccc; }
  .chat-header .title { font-weight: bold; }
  .chat-header .subtitle { font-size: 12px; color: #777; }
  #chat-box { padding: 16px; height: calc(100vh - 140px); overflow-y: auto; }
  .bubble { max-width: 65%; padding: 8px 12px; border-radius: 14px; margin-bottom: 8px; clear: both; font-size: 14px; line-height: 1.4; }
  .bubble.me { background: #6c5ce7; color: #fff; float: right; border-bottom-right-radius: 2px; }
  .bubble.them { background: #fff; color: #222; float: left; border-bottom-left-radius: 2px; box-shadow: 0 1px 1px rgba(0,0,0,.08); }
  .bubble .time { display: block; font-size: 10px; opacity: .7; margin-top: 4px; }
  #chat-form { display: flex; gap: 8px; padding: 12px 16px; background: #fff; border-top: 1px solid #ddd; position: sticky; bottom: 0; }
  #chat-form input[type=text] { flex: 1; padding: 10px 14px; border-radius: 20px; border: 1px solid #ccc; }
  #chat-form button { background: #6c5ce7; color: #fff; border: none; border-radius: 20px; padding: 0 18px; cursor: pointer; }
</style>
</head>
<body>

<div class="chat-header">
  <img src="<?= htmlspecialchars($item_gambar) ?>" alt="">
  <div>
    <div class="title"><?= htmlspecialchars($seller['username'] ?? 'Penjual') ?></div>
    <div class="subtitle"><?= htmlspecialchars($item['nama_barang'] ?? 'Barang') ?></div>
  </div>
</div>

<div id="chat-box">
  <?php foreach ($messages as $m): ?>
    <div class="bubble <?= $m['sender_id'] == $my_id ? 'me' : 'them' ?>">
      <?= nl2br(htmlspecialchars($m['pesan'])) ?>
      <span class="time"><?= date('d M, H:i', strtotime($m['created_at'])) ?></span>
    </div>
  <?php endforeach; ?>
</div>

<form id="chat-form">
  <input type="hidden" id="item_id" value="<?= $item_id ?>">
  <input type="hidden" id="receiver_id" value="<?= $receiver_id ?>">
  <input type="text" id="pesan" placeholder="Tulis pesan..." autocomplete="off" required>
  <button type="submit">Kirim</button>
</form>

<script>
const myId = <?= (int)$my_id ?>;
const chatBox = document.getElementById('chat-box');
const form = document.getElementById('chat-form');
const pesanInput = document.getElementById('pesan');
const itemId = document.getElementById('item_id').value;
const receiverId = document.getElementById('receiver_id').value;

function scrollToBottom() {
  chatBox.scrollTop = chatBox.scrollHeight;
}
scrollToBottom();

function appendBubble(pesan, isMe, time) {
  const div = document.createElement('div');
  div.className = 'bubble ' + (isMe ? 'me' : 'them');
  div.innerHTML = pesan.replace(/\n/g, '<br>') + '<span class="time">' + time + '</span>';
  chatBox.appendChild(div);
  scrollToBottom();
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const pesan = pesanInput.value.trim();
  if (!pesan) return;

  const fd = new FormData();
  fd.append('item_id', itemId);
  fd.append('receiver_id', receiverId);
  fd.append('pesan', pesan);

  try {
    const res = await fetch('send_chat.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      appendBubble(data.data.pesan, true, 'Baru saja');
      pesanInput.value = '';
    } else {
      alert(data.message || 'Gagal mengirim pesan');
    }
  } catch (err) {
    alert('Gagal terhubung ke server');
  }
});

// Polling sederhana tiap 4 detik biar pesan baru dari lawan chat ikut muncul
let lastCount = <?= count($messages) ?>;
setInterval(async () => {
  try {
    const res = await fetch(`get_chat.php?item_id=${itemId}&receiver_id=${receiverId}`);
    const data = await res.json();
    if (data.success && data.data.length > lastCount) {
      for (let i = lastCount; i < data.data.length; i++) {
        const m = data.data[i];
        appendBubble(m.pesan, m.sender_id == myId, new Date(m.created_at).toLocaleString());
      }
      lastCount = data.data.length;
    }
  } catch (err) { /* diamkan saja kalau polling gagal sesekali */ }
}, 4000);
</script>

</body>
</html>