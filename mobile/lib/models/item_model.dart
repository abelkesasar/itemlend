class ItemModel {
  final int id;
  final String namaBarang;
  final String kategori; 
  final String deskripsi;
  final int harga;
  final String gambar;
  final String lokasi;

  ItemModel({
    required this.id,
    required this.namaBarang,
    required this.kategori,
    required this.deskripsi,
    required this.harga,
    required this.gambar,
    required this.lokasi,
  });

  factory ItemModel.fromJson(Map json) {
    return ItemModel(
      id: int.parse(json['id'].toString()),
      namaBarang: json['nama_barang'] ?? '',
      kategori: json['kategori'] ?? 'Dokumentasi',
      deskripsi: json['deskripsi'] ?? '',
      harga: int.parse(json['harga'].toString()),
      gambar: json['gambar'] ?? '',
      lokasi: json['lokasi'] ?? '',
    );
  }
}