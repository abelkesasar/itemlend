<?php
if (!isset($_SESSION['user'])) {
    echo "Harus login!";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

$foto = (!empty($me['foto_profil']) && file_exists("uploads/" . $me['foto_profil']))
    ? "uploads/" . $me['foto_profil']
    : "https://ui-avatars.com/api/?name=" . urlencode($me['username']) . "&background=3d4bff&color=fff&size=120";
?>

<style>
    .profile-wrap { max-width: 560px; margin: 0 auto; }
    .profile-title { font-size: 24px; font-weight: 800; color: #1a1d2e; margin-bottom: 20px; }
    .profile-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    }
    .profile-alert {
        background: #e9f9f0; border: 1px solid #a7f3d0; color: #1a7a46;
        padding: 12px 16px; border-radius: 10px; font-size: 13.5px;
        font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
    }
    .profile-avatar-box { text-align: center; margin-bottom: 24px; }
    .profile-avatar-box img {
        width: 96px; height: 96px; border-radius: 50%; object-fit: cover;
        border: 3px solid #eef0ff; display: block; margin: 0 auto 12px;
    }
    .profile-avatar-box input[type=file] {
        font-size: 12.5px; color: #6b7280; margin: 0 auto; display: block;
        max-width: 220px;
    }
    .profile-group { margin-bottom: 16px; }
    .profile-group label {
        display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 5px;
    }
    .profile-group input, .profile-group textarea {
        width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
        padding: 10px 14px; font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 13.5px; color: #1a1d2e; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .profile-group input:focus, .profile-group textarea:focus {
        border-color: #3d4bff; box-shadow: 0 0 0 3px rgba(61,75,255,0.1);
    }
    .profile-group textarea { resize: none; }
    .profile-hint { font-size: 11.5px; color: #9ca3af; font-weight: 400; }
    .profile-submit {
        width: 100%; padding: 13px; background: #3d4bff; color: #fff;
        border: none; border-radius: 10px; font-family: inherit;
        font-size: 14.5px; font-weight: 700; cursor: pointer;
        margin-top: 6px; transition: background 0.15s;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .profile-submit:hover { background: #2c38d4; }
</style>

<div class="profile-wrap">
    <div class="profile-title">Profil Saya</div>

    <?php if (isset($_GET['success'])): ?>
        <div class="profile-alert">
            <i class="ti ti-circle-check"></i> Profil berhasil diperbarui!
        </div>
    <?php endif; ?>

    <div class="profile-card">
        <form action="actions/update_profile.php" method="POST" enctype="multipart/form-data">

            <div class="profile-avatar-box">
                <img src="<?= $foto ?>">
                <input type="file" name="foto_profil" accept="image/*">
            </div>

            <div class="profile-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($me['username']) ?>" required>
            </div>

            <div class="profile-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($me['email']) ?>" required>
            </div>

            <div class="profile-group">
                <label>Nomor WhatsApp</label>
                <input type="text" name="nomor_wa" value="<?= htmlspecialchars($me['nomor_wa']) ?>" required>
            </div>

            <div class="profile-group">
                <label>Alamat</label>
                <textarea name="alamat" rows="2" required><?= htmlspecialchars($me['alamat']) ?></textarea>
            </div>

            <?php if ($me['role'] === 'vendor'): ?>
            <div class="profile-group">
                <label>Deskripsi Usaha</label>
                <textarea name="deskripsi_vendor" rows="2"><?= htmlspecialchars($me['deskripsi_vendor'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>

            <div class="profile-group">
                <label>Password Baru <span class="profile-hint">(kosongkan jika tidak ingin ganti)</span></label>
                <input type="password" name="password" placeholder="••••••••">
            </div>

            <button type="submit" class="profile-submit">
                <i class="ti ti-device-floppy"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>