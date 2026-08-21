<?php
include 'koneksi.php';

$conn = $conn ?? $koneksi ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $rating = (int)$_POST['rating'];
    $komentar = mysqli_real_escape_string($conn, $_POST['komentar']);

    $query = "INSERT INTO ratings (nama, rating, komentar) VALUES ('$nama', $rating, '$komentar')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: index.php?status=sukses");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>