<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - ItemLend</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 flex items-center justify-center p-6">

    <div class="bg-white/20 backdrop-blur-lg shadow-2xl rounded-3xl overflow-hidden w-full max-w-5xl grid md:grid-cols-2">

        <!-- LEFT -->
        <div class="hidden md:flex flex-col justify-center p-12 text-white bg-white/10">

            <h1 class="text-5xl font-bold leading-tight">
                Welcome Back 👋
            </h1>

            <p class="mt-6 text-lg text-white/80">
                Login untuk mulai menyewa atau menawarkan barangmu di ItemLend.
            </p>

        </div>

        <!-- RIGHT -->
        <div class="bg-white p-10 md:p-14">

            <div class="mb-8">

                <h2 class="text-4xl font-bold text-gray-800">
                    Login
                </h2>

                <p class="text-gray-500 mt-2">
                    Masuk ke akun ItemLend kamu
                </p>

            </div>

            <form action="actions/login.php" method="POST">

                <div class="mb-5">

                    <label class="block font-semibold mb-2">
                        Username
                    </label>

                    <input
                    type="text"
                    name="username"
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

                    Login

                </button>

            </form>

            <p class="text-center text-gray-500 mt-6">
                Belum punya akun?

                <a
                href="index.php?page=register"
                class="text-blue-500 font-semibold hover:underline">

                    Register

                </a>
            </p>

        </div>

    </div>

</body>
</html>