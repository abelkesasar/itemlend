<!DOCTYPE html>
<html>
<head>
    <title>Login - ItemLend</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-indigo-600 via-blue-600 to-cyan-500 relative overflow-x-hidden">

    

    <!-- CONTAINER -->
    <div class="max-w-7xl mx-auto min-h-screen grid md:grid-cols-2 items-center px-10">

        <!-- LEFT -->
        <div class="text-white pr-20">

            <h1 class="text-6xl font-bold leading-tight">
                Welcome Back 👋
            </h1>

            <p class="mt-6 text-xl text-white/80 leading-relaxed">
                Login untuk mulai mencari barang
                atau menyewakan barang milikmu
                bersama ItemLend.
            </p>

        </div>

        <!-- RIGHT -->
        <div class="flex justify-center">

            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-8">

                <div class="mb-8">

                    <h2 class="text-4xl font-bold text-gray-800">
                        Login
                    </h2>

                    <p class="text-gray-500 mt-2">
                        Masuk ke akun ItemLend
                    </p>

                </div>

                <form
                action="actions/login.php"
                method="POST">

                    <!-- Username -->
                    <div class="mb-4">

                        <label class="block font-semibold mb-2">
                            Username
                        </label>

                        <input
                        type="text"
                        name="username"
                        required
                        class="w-full border border-gray-300 px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                    </div>

                    <!-- Password -->
                    <div class="mb-6">

                        <label class="block font-semibold mb-2">
                            Password
                        </label>

                        <input
                        type="password"
                        name="password"
                        required
                        class="w-full border border-gray-300 px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                    </div>

                    <!-- BUTTON -->
                    <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold text-lg transition">

                        Login

                    </button>

                </form>

                <p class="text-center text-gray-500 mt-6">

                    Belum punya akun?

                    <a
                    href="index.php?page=register"
                    class="text-blue-600 font-semibold hover:underline">

                        Register

                    </a>

                </p>

            </div>

        </div>

    </div>

</body>
</html>