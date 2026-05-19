<?php
session_start();
require 'config/db.php';

$user1 = $_SESSION['user']['id'];

$user2 = $_GET['user'];

$item_id = $_GET['id'];

$stmt = $conn->prepare("
SELECT *
FROM chats
WHERE
(
sender_id=? AND receiver_id=?
OR
sender_id=? AND receiver_id=?
)
AND item_id=?
ORDER BY created_at ASC
");

$stmt->execute([
    $user1,
    $user2,
    $user2,
    $user1,
    $item_id
]);

$chat = $stmt->fetchAll();
?>

<h1>Chat</h1>

<?php foreach($chat as $c): ?>

<div>

<b>
<?= $c['sender_id'] == $user1 ? 'Saya' : 'Dia' ?>
:
</b>

<?= $c['pesan'] ?>

</div>

<br>

<?php endforeach; ?>

<hr>

<form action="actions/send_chat.php" method="POST">

<input type="hidden"
name="receiver_id"
value="<?= $user2 ?>">

<input type="hidden"
name="item_id"
value="<?= $item_id ?>">

<input type="text"
name="pesan"
placeholder="Ketik pesan">

<button type="submit">
Kirim
</button>

</form>