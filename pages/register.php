<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h1>Register</h1>

<form action="actions/register.php"
method="POST"
enctype="multipart/form-data">

    <!-- USERNAME -->
    <input
    type="text"
    name="username"
    placeholder="Username"
    required>

    <br><br>

    <!-- EMAIL -->
    <input
    type="email"
    name="email"
    placeholder="Email"
    required>

    <br><br>

    <!-- NOMOR WA -->
    <input
    type="text"
    name="nomor_wa"
    placeholder="Nomor WhatsApp"
    required>

    <br><br>

    <!-- PASSWORD -->
    <input
    type="password"
    name="password"
    placeholder="Password"
    required>

    <br><br>

    <!-- ALAMAT -->
    <textarea
    name="alamat"
    placeholder="Alamat"
    required></textarea>

    <br><br>

    <!-- ROLE -->
    <select name="role" id="role" required>

        <option value="user">
            User
        </option>

        <option value="vendor">
            Vendor
        </option>

    </select>

    <br><br>

    <!-- USER -->
    <div id="userFields">

        <label>Upload KTP</label>
        <br>

        <input type="file" name="ktp_user">

        <br><br>

        <label>Upload KTM</label>
        <br>

        <input type="file" name="ktm_user">

    </div>

    <!-- VENDOR -->
    <div id="vendorFields" style="display:none;">

        <label>Upload KTP Vendor</label>
        <br>

        <input type="file" name="ktp_vendor">

        <br><br>

        <textarea
        name="deskripsi_vendor"
        placeholder="Deskripsi Vendor">
        </textarea>

    </div>

    <br>

    <button type="submit">
        Register
    </button>

</form>

<script>

const role =
document.getElementById('role');

const userFields =
document.getElementById('userFields');

const vendorFields =
document.getElementById('vendorFields');

role.addEventListener('change', function(){

    if(this.value == 'vendor'){

        vendorFields.style.display = 'block';
        userFields.style.display = 'none';

    }else{

        vendorFields.style.display = 'none';
        userFields.style.display = 'block';

    }

});

</script>

</body>
</html>