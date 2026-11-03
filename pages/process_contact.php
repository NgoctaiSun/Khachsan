<?php
session_start();
$conn = mysqli_connect('localhost', 'root', '', 'khachsan_db');
$name = $_POST['name'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$address = $_POST['address'];
$content = $_POST['content'];

    $id_taikhoan = isset($_SESSION['id']) ? $_SESSION['id'] : NULL;
   if ($id_taikhoan === NULL) {
    $sql = "INSERT INTO lienhe
    (id_taikhoan, hoten, email, sdt, diachi, noidung, thoigian)
    VALUES (NULL, '$name', '$email', '$phone', '$address', '$content', NOW())";
} else {
    $sql = "INSERT INTO lienhe
    (id_taikhoan, hoten, email, sdt, diachi, noidung, thoigian)
    VALUES ('$id_taikhoan', '$name', '$email', '$phone', '$address', '$content', NOW())";
}

if (mysqli_query($conn, $sql)) {
    header("Location: ../index.php?page=contact");
    exit();
} else {
    echo "Lỗi: " . mysqli_error($conn);
}
?>