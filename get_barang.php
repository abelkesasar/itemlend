query($sql);

$items = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "message" => "Data barang berhasil diambil",
    "data" => $items
]);

$conn->close();
?>