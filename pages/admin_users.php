<?php
session_start();
require 'config/db.php';

if ($_SESSION['role'] != 'admin') {
    echo "Akses ditolak!";
    exit;
}

$data = $conn->query("SELECT * FROM users WHERE status='pending'");
?>

<h2>Approve User</h2>

<?php while($row = $data->fetch()) { ?>
    <p>
        <?= $row['username'] ?>
        <a href="actions/approve_user.php?id=<?= $row['id'] ?>">Approve</a>
    </p>
<?php } ?>