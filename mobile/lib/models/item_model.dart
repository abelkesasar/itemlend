class Item {
  final int? id;
  final String namaBarang;
  final String kategoriBarang;
  final String deskripsi;
  final String hargaSewa;
  final String fotoBarang;

  Item({
    this.id,
    required this.namaBarang,
    required this.kategoriBarang,
    required this.deskripsi,
    required this.hargaSewa,
    required this.fotoBarang,
  });
}