<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<div class='alert alert-danger'>Akses ditolak!</div>";
    exit;
}

$data = $conn->query("SELECT * FROM users WHERE status='pending'");
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person-check me-2"></i>Monitoring Registrasi User</h2>
        <a href="admin/dashboard.php" class="btn btn-outline-primary btn-sm">Ke Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    while($row = $data->fetch()) { 
                        $count++;
                    ?>
                        <tr>
                            <td class="ps-4"><?= $row['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-secondary bg-opacity-10 p-2 rounded-circle me-2">
                                        <i class="bi bi-person text-secondary"></i>
                                    </div>
                                    <?= htmlspecialchars($row['username']) ?>
                                </div>
                            </td>
                            <td><span class="badge bg-info text-dark"><?= ucfirst($row['role']) ?></span></td>
                            <td><span class="badge bg-warning"><?= ucfirst($row['status']) ?></span></td>
                            <td class="text-center">
                                <a href="actions/approve_user.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-lg me-1"></i> Approve
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                    
                    <?php if ($count == 0) { ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada user yang menunggu persetujuan.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
