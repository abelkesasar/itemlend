<?php
/**
 * api/api_get_profile.php
 * Endpoint GET untuk ambil data profil user yang sedang login.
 * Dipakai untuk mengisi ProfileScreen dan mengecek kelengkapan metode pembayaran
 * (dipakai untuk validasi sebelum user boleh menambahkan barang).
 *
 * Header: Authorization: Bearer <token>
 */

require 'api_auth_middleware.php'; // sudah include db.php + validasi token, hasilnya ada di $user

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'data' => [
        'id'                    => (int) $user['id'],
        'username'              => $user['username'],
        'email'                 => $user['email'],
        'nomor_wa'              => $user['nomor_wa'],
        'alamat'                => $user['alamat'],
        'role'                  => $user['role'],
        'status'                => $user['status'],
        'deskripsi_vendor'      => $user['deskripsi_vendor'],
        'metode_pembayaran'     => $user['metode_pembayaran'],
        'nama_penyedia'         => $user['nama_penyedia'],
        'nomor_rekening'        => $user['nomor_rekening'],
        'nama_pemilik_rekening' => $user['nama_pemilik_rekening'],
        'foto_profil'           => $user['foto_profil'],
        'foto_qris'             => $user['foto_qris'],
        // Dipakai Flutter buat cek apakah user sudah boleh menambahkan barang
        'metode_pembayaran_lengkap' => !empty($user['nama_penyedia']),
    ],
]);