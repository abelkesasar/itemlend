<?php
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}

$my_id       = $_SESSION['user'];
$receiver_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
$item_id     = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if (!$receiver_id) {
    die('Penjual tidak ditemukan.');
}

$stmt = $conn->prepare("SELECT username, foto_profil FROM users WHERE id = ?");
$stmt->execute([$receiver_id]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

// Resolve foto profil lawan chat — di DB cuma nama file polos, perlu ditambah folder uploads/
$sellerFoto = (!empty($seller['foto_profil']) && file_exists("uploads/" . $seller['foto_profil']))
    ? "uploads/" . $seller['foto_profil']
    : "assets/default-user.png";

function resolveGambarItem($gambarRaw) {
    $default = 'assets/default-item.png';
    if (empty($gambarRaw)) return $default;
    $list = json_decode($gambarRaw, true);
    if (is_array($list) && !empty($list[0]) && file_exists("uploads/" . $list[0])) {
        return "uploads/" . $list[0];
    }
    if (!is_array($list) && file_exists("uploads/" . $gambarRaw)) {
        return "uploads/" . $gambarRaw;
    }
    return $default;
}

// Ambil info barang untuk kartu STAGED (lampiran sementara di atas kotak chat)
// TIDAK di-insert ke DB di sini — cuma ditampilkan, baru masuk chat kalau user kirim.
$stagedItem = null;
if ($item_id) {
    $stmt = $conn->prepare("SELECT id, nama_barang, harga, gambar FROM items WHERE id = ?");
    $stmt->execute([$item_id]);
    $stagedItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Ambil SEMUA riwayat (teks + kartu barang) dengan lawan ini, digabung 1 thread, urut waktu
$stmt = $conn->prepare(
    "SELECT c.id, c.sender_id, c.receiver_id, c.item_id, c.pesan, c.type, c.created_at,
            i.nama_barang, i.harga, i.gambar
     FROM chats c
     LEFT JOIN items i ON c.item_id = i.id
     WHERE (c.sender_id = ? AND c.receiver_id = ?) OR (c.sender_id = ? AND c.receiver_id = ?)
     ORDER BY c.created_at ASC, c.id ASC"
);
$stmt->execute([$my_id, $receiver_id, $receiver_id, $my_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  #chat-box { padding: 16px; height: calc(100vh - 340px); overflow-y: auto; background: #f2f2f2; border-radius: 12px; }
  .chat-header { background: #fff; padding: 12px 16px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #ddd; border-radius: 12px 12px 0 0; }
  .chat-header img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; background: #ccc; }
  .chat-header .title { font-weight: bold; }
  .bubble { max-width: 65%; padding: 8px 12px; border-radius: 14px; margin-bottom: 8px; clear: both; font-size: 14px; line-height: 1.4; }
  .bubble.me { background: #6c5ce7; color: #fff; float: right; border-bottom-right-radius: 2px; }
  .bubble.them { background: #fff; color: #222; float: left; border-bottom-left-radius: 2px; box-shadow: 0 1px 1px rgba(0,0,0,.08); }
  .bubble .time { display: block; font-size: 10px; opacity: .7; margin-top: 4px; }
  .chat-item-card {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1px solid #eee;
    padding: 10px 14px; margin: 0 auto 8px; border-radius: 12px;
    max-width: 75%; clear: both; text-decoration: none; color: inherit;
    transition: box-shadow 0.15s;
  }
  .chat-item-card:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
  .chat-item-card img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; background: #eee; flex-shrink: 0; }
  .chat-item-card .info { flex: 1; min-width: 0; }
  .chat-item-card .nama { font-size: 13px; font-weight: 600; color: #222; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .chat-item-card .harga { font-size: 13px; color: #6c5ce7; font-weight: 700; margin-top: 2px; }

  #staged-item-wrap { padding: 0 16px; background: #fff; }
  .staged-item-card {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1px solid #eee;
    padding: 8px 10px; border-radius: 10px; position: relative;
    margin-top: 10px;
  }
  .staged-item-card img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background: #eee; flex-shrink: 0; }
  .staged-item-card .info { flex: 1; min-width: 0; }
  .staged-item-card .nama { font-size: 13px; font-weight: 600; color: #222; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
  .staged-item-card .harga { font-size: 12.5px; color: #6c5ce7; font-weight: 700; margin-top: 2px; }
  .staged-item-card .close-btn {
    background: #eee; border: none; border-radius: 50%;
    width: 22px; height: 22px; flex-shrink: 0; cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: 13px; color: #666;
  }
  .staged-item-card .close-btn:hover { background: #ddd; }

  #chat-form { display: flex; gap: 8px; padding: 12px 16px; background: #fff; border-top: 1px solid #ddd; border-radius: 0 0 12px 12px; }
  #chat-form input[type=text] { flex: 1; padding: 10px 14px; border-radius: 20px; border: 1px solid #ccc; }
  #chat-form button { background: #6c5ce7; color: #fff; border: none; border-radius: 20px; padding: 0 18px; cursor: pointer; }
</style>

<div class="chat-header">
  <img src="<?= htmlspecialchars($sellerFoto) ?>" alt="">
  <div class="title"><?= htmlspecialchars($seller['username'] ?? 'Penjual') ?></div>
</div>

<div id="chat-box">
  <?php foreach ($messages as $m): ?>
    <?php if ($m['type'] === 'item'): ?>
      <a class="chat-item-card" href="index.php?page=detail&id=<?= $m['item_id'] ?>">
        <img src="<?= htmlspecialchars(resolveGambarItem($m['gambar'])) ?>" alt="">
        <div class="info">
          <div class="nama"><?= htmlspecialchars($m['nama_barang'] ?? 'Barang') ?></div>
          <div class="harga">Rp<?= number_format($m['harga'] ?? 0, 0, ',', '.') ?></div>
        </div>
      </a>
    <?php else: ?>
      <div class="bubble <?= $m['sender_id'] == $my_id ? 'me' : 'them' ?>">
        <?= nl2br(htmlspecialchars($m['pesan'])) ?>
        <span class="time"><?= date('d M, H:i', strtotime($m['created_at'])) ?></span>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<!-- Lampiran sementara: hanya tampil kalau datang dari halaman detail (?item_id=...) -->
<div id="staged-item-wrap">
  <?php if ($stagedItem): ?>
    <div class="staged-item-card" id="staged-item-card">
      <img src="<?= htmlspecialchars(resolveGambarItem($stagedItem['gambar'])) ?>" alt="">
      <div class="info">
        <div class="nama"><?= htmlspecialchars($stagedItem['nama_barang']) ?></div>
        <div class="harga">Rp<?= number_format($stagedItem['harga'], 0, ',', '.') ?></div>
      </div>
      <button type="button" class="close-btn" id="staged-item-remove">✕</button>
    </div>
  <?php endif; ?>
</div>

<form id="chat-form">
  <input type="hidden" id="receiver_id" value="<?= $receiver_id ?>">
  <input type="hidden" id="staged_item_id" value="<?= $stagedItem ? (int)$stagedItem['id'] : '' ?>">
  <input type="text" id="pesan" placeholder="Kirim pesan..." autocomplete="off" required>
  <button type="submit">Kirim</button>
</form>

<script>
const myId = <?= (int)$my_id ?>;
const chatBox = document.getElementById('chat-box');
const form = document.getElementById('chat-form');
const pesanInput = document.getElementById('pesan');
const receiverId = document.getElementById('receiver_id').value;
const stagedItemIdInput = document.getElementById('staged_item_id');
const removeBtn = document.getElementById('staged-item-remove');

function scrollToBottom() {
  chatBox.scrollTop = chatBox.scrollHeight;
}
scrollToBottom();

if (removeBtn) {
  removeBtn.addEventListener('click', () => {
    document.getElementById('staged-item-card').remove();
    stagedItemIdInput.value = '';
  });
}

function appendBubble(pesan, isMe, time) {
  const div = document.createElement('div');
  div.className = 'bubble ' + (isMe ? 'me' : 'them');
  div.innerHTML = pesan.replace(/\n/g, '<br>') + '<span class="time">' + time + '</span>';
  chatBox.appendChild(div);
  scrollToBottom();
}

function appendItemCard(item) {
  const a = document.createElement('a');
  a.className = 'chat-item-card';
  a.href = 'index.php?page=detail&id=' + item.item_id;
  a.innerHTML = `
    <img src="${item.gambar}" alt="">
    <div class="info">
      <div class="nama">${item.nama_barang}</div>
      <div class="harga">Rp${Number(item.harga).toLocaleString('id-ID')}</div>
    </div>`;
  chatBox.appendChild(a);
  scrollToBottom();
}

let lastCount = <?= count($messages) ?>;

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const pesan = pesanInput.value.trim();
  if (!pesan) return;

  const fd = new FormData();
  fd.append('receiver_id', receiverId);
  fd.append('pesan', pesan);
  if (stagedItemIdInput.value) {
    fd.append('item_id', stagedItemIdInput.value);
  }

  try {
    const res = await fetch('send_chat.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      if (data.item_card) {
        appendItemCard(data.item_card);
        lastCount++;
      }
      appendBubble(data.data.pesan, true, 'Baru saja');
      lastCount++;

      pesanInput.value = '';
      if (document.getElementById('staged-item-card')) {
        document.getElementById('staged-item-card').remove();
      }
      stagedItemIdInput.value = '';
    } else {
      alert(data.message || 'Gagal mengirim pesan');
    }
  } catch (err) {
    alert('Gagal terhubung ke server');
  }
});

setInterval(async () => {
  try {
    const res = await fetch(`get_chat.php?receiver_id=${receiverId}`);
    const data = await res.json();
    if (data.success && data.data.length > lastCount) {
      for (let i = lastCount; i < data.data.length; i++) {
        const m = data.data[i];
        if (m.type === 'text') {
          appendBubble(m.pesan, m.sender_id == myId, new Date(m.created_at).toLocaleString());
        }
      }
      lastCount = data.data.length;
    }
  } catch (err) { /* diamkan saja */ }
}, 4000);
</script>