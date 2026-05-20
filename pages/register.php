<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - ItemLend</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-indigo-500 via-blue-500 to-cyan-500 flex items-center justify-center p-6">

    <div class="bg-white/20 backdrop-blur-lg shadow-2xl rounded-3xl overflow-hidden w-full max-w-6xl grid md:grid-cols-2">

        <!-- LEFT -->
        <div class="hidden md:flex flex-col justify-center p-12 text-white bg-white/10">

            <h1 class="text-5xl font-bold leading-tight">
                Join ItemLend 🚀
            </h1>

            <p class="mt-6 text-lg text-white/80">
                Buat akun dan mulai sewakan barang atau cari barang yang kamu butuhkan.
            </p>

        </div>

        <!-- RIGHT -->
        <div class="bg-white p-10 md:p-14">

            <div class="mb-8">

                <h2 class="text-4xl font-bold text-gray-800">
                    Register
                </h2>

                <p class="text-gray-500 mt-2">
                    Buat akun baru
                </p>

            </div>

            <form action="actions/register.php" method="POST">

                <div class="mb-4">

                    <label class="block font-semibold mb-2">
                        Nama Lengkap
                    </label>

                    <input
                    type="text"
                    name="nama_lengkap"
                    required
                    class="w-full border border-gray-300 p-4 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                </div>

                <div class="mb-4">

                    <label class="block font-semibold mb-2">
                        Username
                    </label>

                    <input
                    type="text"
                    name="username"
                    required
                    class="w-full border border-gray-300 p-4 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                </div>

                <div class="mb-4">

                    <label class="block font-semibold mb-2">
                        Email
                    </label>

                    <input
                    type="email"
                    name="email"
                    required
                    class="w-full border border-gray-300 p-4 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                </div>

                <div class="mb-4">

                    <label class="block font-semibold mb-2">
                        Nomor WhatsApp
                    </label>

                    <input
                    type="text"
                    name="nomor_hp"
                    required
                    class="w-full border border-gray-300 p-4 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                </div>

                <div class="mb-6">

                    <label class="block font-semibold mb-2">
                        Password
                    </label>

                    <input
                    type="password"
                    name="password"
                    required
                    class="w-full border border-gray-300 p-4 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                </div>

                <button
                type="submit"
                class="w-full bg-blue-500 hover:bg-blue-600 text-white py-4 rounded-2xl font-semibold text-lg transition">

                    Register

                </button>

            </form>

            <p class="text-center text-gray-500 mt-6">
                Sudah punya akun?

                <a
                href="index.php?page=login"
                class="text-blue-500 font-semibold hover:underline">

                    Login

                </a>
            </p>

        </div>

    </div>

</body>
</html>