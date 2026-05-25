<!DOCTYPE html>
<html>
<head>
    <title>Register - ItemLend</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-indigo-600 via-blue-600 to-cyan-500 overflow-hidden">

    <!-- NAVBAR -->
    <div class="absolute top-5 right-8 flex items-center gap-3 z-50">

        <a
        href="index.php"
        class="text-white text-2xl font-bold flex items-center gap-2">

            📦 ItemLend

        </a>

        <a
        href="index.php?page=login"
        class="border border-white text-white px-5 py-2 rounded-full hover:bg-white hover:text-blue-600 transition">

            Login

        </a>

    </div>

    <!-- CONTAINER -->
    <body class="min-h-screen bg-gradient-to-br from-indigo-600 via-blue-600 to-cyan-500 overflow-y-auto">

    <div class="w-full max-w-6xl grid md:grid-cols-2 items-center gap-10 ml-10">

            <!-- LEFT -->
            <div class="text-white">

                <h1 class="text-5xl font-bold leading-tight">
                    Join ItemLend 🚀
                </h1>

                <p class="mt-5 text-lg text-white/80 leading-relaxed max-w-lg">
                    Sewa dan sewakan barang dengan aman,
                    cepat, dan praktis bersama ItemLend.
                </p>

            </div>

            <!-- RIGHT -->
            <div class="flex justify-center">

                <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-6">

                    <div class="mb-4">

                        <h2 class="text-3xl font-bold text-gray-800">
                            Register
                        </h2>

                        <p class="text-gray-500 text-sm mt-1">
                            Buat akun baru
                        </p>

                    </div>

                    <form
                    action="actions/register.php"
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-1">

                        <!-- Username -->
                        <div>

                            <label class="block text-sm font-semibold mb-1">
                                Username
                            </label>

                            <input
                            type="text"
                            name="username"
                            required
                            class="w-full border border-gray-300 px-4 py-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                        </div>

                        <!-- Email -->
                        <div>

                            <label class="block text-sm font-semibold mb-1">
                                Email
                            </label>

                            <input
                            type="email"
                            name="email"
                            required
                            class="w-full border border-gray-300 px-4 py-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                        </div>

                        <!-- Nomor WA -->
                        <div>

                            <label class="block text-sm font-semibold mb-1">
                                Nomor WhatsApp
                            </label>

                            <input
                            type="text"
                            name="nomor_wa"
                            required
                            class="w-full border border-gray-300 px-4 py-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                        </div>

                        <!-- Alamat -->
                        <div>

                            <label class="block text-sm font-semibold mb-1">
                                Alamat
                            </label>

                            <textarea
                            name="alamat"
                            rows="2"
                            required
                            class="w-full border border-gray-300 px-4 py-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>

                        </div>

                        <!-- Role -->
                        <div>

                            <label class="block text-sm font-semibold mb-1">
                                Daftar Sebagai
                            </label>

                            <select
                            name="role"
                            id="role"
                            onchange="toggleRole()"
                            class="w-full border border-gray-300 px-4 py-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                                <option value="user">
                                    User
                                </option>

                                <option value="vendor">
                                    Vendor
                                </option>

                            </select>

                        </div>

                        <!-- USER -->
                        <div id="userFields" class="space-y-3">

                            <div>

                                <label class="block text-sm font-semibold mb-1">
                                    Upload KTP
                                </label>

                                <input
                                type="file"
                                name="ktp_user"
                                required
                                class="w-full border border-gray-300 px-3 py-2 rounded-xl text-sm">

                            </div>

                            <div>

                                <label class="block text-sm font-semibold mb-1">
                                    Upload KTM
                                </label>

                                <input
                                type="file"
                                name="ktm"
                                required
                                class="w-full border border-gray-300 px-3 py-2 rounded-xl text-sm">

                            </div>

                        </div>

                        <!-- VENDOR -->
                        <div
                        id="vendorFields"
                        class="space-y-3 hidden">

                            <div>

                                <label class="block text-sm font-semibold mb-1">
                                    Upload KTP Vendor
                                </label>

                                <input
                                type="file"
                                name="ktp_vendor"
                                class="w-full border border-gray-300 px-3 py-2 rounded-xl text-sm">

                            </div>

                            <div>

                                <label class="block text-sm font-semibold mb-1">
                                    Deskripsi Vendor
                                </label>

                                <textarea
                                name="deskripsi_vendor"
                                rows="2"
                                class="w-full border border-gray-300 px-4 py-2 rounded-xl"></textarea>

                            </div>

                        </div>

                        <!-- Password -->
                        <div>

                            <label class="block text-sm font-semibold mb-1">
                                Password
                            </label>

                            <input
                            type="password"
                            name="password"
                            required
                            class="w-full border border-gray-300 px-4 py-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">

                        </div>

                        <!-- BUTTON -->
                        <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition">

                            Register

                        </button>

                    </form>

                    <p class="text-center text-gray-500 text-sm mt-4">

                        Sudah punya akun?

                        <a
                        href="index.php?page=login"
                        class="text-blue-600 font-semibold hover:underline">

                            Login

                        </a>

                    </p>

                </div>

            </div>

        </div>

    </div>

    <script>

    function toggleRole(){

        let role =
        document.getElementById('role').value;

        if(role == 'vendor'){

            document.getElementById('vendorFields').classList.remove('hidden');

            document.getElementById('userFields').classList.add('hidden');

        }else{

            document.getElementById('vendorFields').classList.add('hidden');

            document.getElementById('userFields').classList.remove('hidden');

        }

    }

    </script>

</body>
</html>