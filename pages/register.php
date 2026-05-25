<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ItemLend</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #3d4bff 0%, #6366f1 50%, #8b5cf6 100%);
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
        .topnav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 32px;
            flex-shrink: 0;
        }
        .topnav-brand {
            display: flex; align-items: center; gap: 8px;
            font-size: 20px; font-weight: 800; color: #fff;
            text-decoration: none;
        }
        .topnav-brand i { font-size: 22px; }
        .topnav-login {
            border: 1.5px solid rgba(255,255,255,0.6);
            color: #fff; font-size: 13.5px; font-weight: 600;
            padding: 8px 20px; border-radius: 20px;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .topnav-login:hover { background: #fff; color: #3d4bff; }

        /* ── MAIN GRID ── */
        .page-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            padding: 20px 32px 48px;
            align-items: center;
        }

        /* ── LEFT PANEL ── */
        .left-panel { color: #fff; }
        .left-panel h1 {
            font-size: 44px; font-weight: 800; line-height: 1.15;
            margin-bottom: 16px;
        }
        .left-panel p {
            font-size: 16px; color: rgba(255,255,255,0.8);
            line-height: 1.65; max-width: 420px;
        }
        .left-features { margin-top: 36px; display: flex; flex-direction: column; gap: 16px; }
        .left-feature {
            display: flex; align-items: center; gap: 12px;
        }
        .feature-icon {
            width: 40px; height: 40px; border-radius: 10px;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .feature-icon i { font-size: 20px; color: #fff; }
        .feature-text { font-size: 14px; color: rgba(255,255,255,0.85); font-weight: 500; }

        /* ── RIGHT PANEL (FORM CARD) ── */
        .form-card {
            background: #fff;
            border-radius: 24px;
            padding: 36px 32px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.18);
            width: 100%;
        }
        .form-card h2 {
            font-size: 26px; font-weight: 800; color: #1a1d2e;
            margin-bottom: 4px;
        }
        .form-card .subtitle {
            font-size: 13px; color: #6b7280; margin-bottom: 24px;
        }

        /* ── FORM ELEMENTS ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full { grid-column: 1 / -1; }
        label {
            font-size: 12.5px; font-weight: 600; color: #374151;
        }
        input[type=text],
        input[type=email],
        input[type=password],
        select,
        textarea {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 14px;
            font-family: inherit;
            font-size: 13.5px;
            color: #1a1d2e;
            background: #fff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #3d4bff;
            box-shadow: 0 0 0 3px rgba(61,75,255,0.1);
        }
        textarea { resize: none; }
        input[type=file] {
            width: 100%;
            border: 1.5px dashed #d1d5db;
            border-radius: 10px;
            padding: 10px 14px;
            font-family: inherit;
            font-size: 13px;
            color: #6b7280;
            background: #f9fafb;
            cursor: pointer;
            outline: none;
        }
        input[type=file]:focus { border-color: #3d4bff; }

        /* ── ROLE TOGGLE ── */
        .role-toggle {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 8px; margin-bottom: 0;
        }
        .role-opt { position: relative; }
        .role-opt input[type=radio] { position: absolute; opacity: 0; width: 0; }
        .role-opt label {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            border: 1.5px solid #e5e7eb; border-radius: 10px;
            padding: 10px; cursor: pointer; font-size: 13.5px; font-weight: 600;
            color: #6b7280; background: #f9fafb;
            transition: all 0.15s;
        }
        .role-opt label i { font-size: 18px; }
        .role-opt input[type=radio]:checked + label {
            border-color: #3d4bff; background: #eef0ff; color: #3d4bff;
        }

        /* ── CONDITIONAL FIELDS ── */
        .cond-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .cond-fields.hidden { display: none; }

        /* ── DIVIDER ── */
        .divider {
            height: 1px; background: #f0f1f3;
            grid-column: 1 / -1; margin: 4px 0;
        }

        /* ── SUBMIT ── */
        .btn-submit {
            width: 100%; padding: 13px;
            background: #3d4bff; color: #fff;
            border: none; border-radius: 12px;
            font-family: inherit; font-size: 15px; font-weight: 700;
            cursor: pointer; margin-top: 4px;
            transition: background 0.15s, transform 0.1s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { background: #2c38d4; }
        .btn-submit:active { transform: scale(0.98); }

        .login-link {
            text-align: center; font-size: 13px; color: #6b7280; margin-top: 14px;
        }
        .login-link a { color: #3d4bff; font-weight: 600; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }

        /* ── RESPONSIVE ── */
        @media (max-width: 860px) {
            .page-grid { grid-template-columns: 1fr; padding: 16px 20px 40px; gap: 24px; }
            .left-panel { text-align: center; }
            .left-panel h1 { font-size: 32px; }
            .left-panel p { margin: 0 auto; }
            .left-features { align-items: center; }
        }

        @media (max-width: 500px) {
            .topnav { padding: 16px 20px; }
            .form-card { padding: 28px 20px; border-radius: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .cond-fields { grid-template-columns: 1fr; }
            .left-panel { display: none; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="topnav">
        <a href="index.php" class="topnav-brand">
            <i class="ti ti-briefcase"></i> ItemLend
        </a>
        <a href="index.php?page=login" class="topnav-login">Login</a>
    </nav>

    <!-- GRID -->
    <div class="page-grid">

        <!-- LEFT -->
        <div class="left-panel">
            <h1>Bergabung<br>dengan ItemLend 🚀</h1>
            <p>Buat akun dan mulai sewakan barang atau cari barang yang kamu butuhkan dengan aman dan mudah.</p>
            <div class="left-features">
                <div class="left-feature">
                    <div class="feature-icon"><i class="ti ti-shield-check"></i></div>
                    <span class="feature-text">Transaksi aman & terverifikasi</span>
                </div>
                <div class="left-feature">
                    <div class="feature-icon"><i class="ti ti-users"></i></div>
                    <span class="feature-text">Komunitas penyewa terpercaya</span>
                </div>
                <div class="left-feature">
                    <div class="feature-icon"><i class="ti ti-coin"></i></div>
                    <span class="feature-text">Hasilkan uang dari barang idle</span>
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="form-card">
            <h2>Buat Akun Baru</h2>
            <p class="subtitle">Isi data diri kamu dengan lengkap</p>

            <form action="actions/register.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">

                    <!-- Username -->
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="contoh: johndoe" required>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="email@kamu.com" required>
                    </div>

                    <!-- Nomor WA -->
                    <div class="form-group">
                        <label>Nomor WhatsApp</label>
                        <input type="text" name="nomor_wa" placeholder="08xxxxxxxxxx" required>
                    </div>

                    <!-- Alamat -->
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" rows="2" placeholder="Jl. Contoh No. 1, Kota" required></textarea>
                    </div>

                    <!-- Daftar Sebagai -->
                    <div class="form-group full">
                        <label>Daftar Sebagai</label>
                        <div class="role-toggle">
                            <div class="role-opt">
                                <input type="radio" name="role" id="role_user" value="user" checked onchange="toggleRole()">
                                <label for="role_user"><i class="ti ti-user"></i> User</label>
                            </div>
                            <div class="role-opt">
                                <input type="radio" name="role" id="role_vendor" value="vendor" onchange="toggleRole()">
                                <label for="role_vendor"><i class="ti ti-store"></i> Vendor</label>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- USER FIELDS -->
                    <div class="cond-fields" id="userFields">
                        <div class="form-group">
                            <label>Upload KTP</label>
                            <input type="file" name="ktp_user" accept="image/*,.pdf" required>
                        </div>
                        <div class="form-group">
                            <label>Upload KTM</label>
                            <input type="file" name="ktm" accept="image/*,.pdf" required>
                        </div>
                    </div>

                    <!-- VENDOR FIELDS -->
                    <div class="cond-fields hidden" id="vendorFields">
                        <div class="form-group">
                            <label>Upload KTP Vendor</label>
                            <input type="file" name="ktp_vendor" accept="image/*,.pdf">
                        </div>
                        <div class="form-group">
                            <label>Deskripsi Usaha</label>
                            <textarea name="deskripsi_vendor" rows="2" placeholder="Ceritakan usaha kamu..."></textarea>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Password -->
                    <div class="form-group full">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Min. 8 karakter" required>
                    </div>

                    <!-- Submit -->
                    <div class="form-group full">
                        <button type="submit" class="btn-submit">
                            <i class="ti ti-user-plus"></i> Daftar Sekarang
                        </button>
                        <p class="login-link">Sudah punya akun? <a href="index.php?page=login">Login</a></p>
                    </div>

                </div>
            </form>
        </div>

    </div>

    <script>
    function toggleRole() {
        const role = document.querySelector('input[name="role"]:checked').value;
        const userFields   = document.getElementById('userFields');
        const vendorFields = document.getElementById('vendorFields');

        if (role === 'vendor') {
            vendorFields.classList.remove('hidden');
            userFields.classList.add('hidden');
            // hapus required dari field user, pasang ke vendor
            userFields.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        } else {
            userFields.classList.remove('hidden');
            vendorFields.classList.add('hidden');
            vendorFields.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
        }
    }
    </script>

</body>
</html>