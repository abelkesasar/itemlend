<?php
session_start();
require 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kamu harus login dulu']);
    exit;
}

$sender_id   = $_SESSION['user'];
$receiver_id = $_POST['receiver_id'] ?? null;
$pesan       = trim($_POST['pesan'] ?? '');
$item_id     = isset($_POST['item_id']) && $_POST['item_id'] !== '' ? (int)$_POST['item_id'] : null;

if (!$receiver_id || $pesan === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'receiver_id dan pesan wajib diisi']);
    exit;
}

if ((int)$sender_id === (int)$receiver_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tidak bisa mengirim pesan ke diri sendiri']);
    exit;
}

$itemCard = null;

// Setiap kali ada lampiran barang yang ikut dikirim, kartunya SELALU di-insert
// sebagai baris chat baru — boleh berkali-kali (mis. untuk ngingetin penjual
// barang mana yang dimaksud kalau chat udah panjang), sama seperti Tokopedia.
if ($item_id) {
    $stmt = $conn->prepare(
        "INSERT INTO chats (sender_id, receiver_id, item_id, type, pesan, created_at)
         VALUES (?, ?, ?, 'item', '', NOW())"
    );
    $stmt->execute([$sender_id, $receiver_id, $item_id]);

    $stmt = $conn->prepare("SELECT nama_barang, harga, gambar FROM items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $gambarRaw = $item['gambar'];
        $gambarUrl = 'assets/default-item.png';
        $list = json_decode($gambarRaw, true);
        if (is_array($list) && !empty($list[0]) && file_exists("uploads/" . $list[0])) {
            $gambarUrl = "uploads/" . $list[0];
        } elseif (!empty($gambarRaw) && !is_array($list) && file_exists("uploads/" . $gambarRaw)) {
            $gambarUrl = "uploads/" . $gambarRaw;
        }

        $itemCard = [
            'item_id'     => $item_id,
            'nama_barang' => htmlspecialchars($item['nama_barang']),
            'harga'       => $item['harga'],
            'gambar'      => $gambarUrl,
        ];
    }
}

$stmt = $conn->prepare(
    "INSERT INTO chats (sender_id, receiver_id, item_id, pesan, type, created_at)
     VALUES (?, ?, NULL, ?, 'text', NOW())"
);
$ok = $stmt->execute([$sender_id, $receiver_id, $pesan]);

if ($ok) {
    echo json_encode([
        'success'   => true,
        'item_card' => $itemCard,
        'data' => [
            'id'          => $conn->lastInsertId(),
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'pesan'       => htmlspecialchars($pesan),
            'type'        => 'text',
            'created_at'  => date('Y-m-d H:i:s'),
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengirim pesan']);
}