<?php 
$conn =mysqli_connect('localhost','root','','khachsan_db');
$name=$_POST['name'];
$phone=$_POST['phone'];
$email=$_POST['email'];
$address=$_POST['address'];
$content=$_POST['content'];


$sql = " INSERT INTO lienhe(hoten, email,sdt, diachi, noidung,thoigian) VALUES ('$name', '$email', '$phone', '$address', '$content', NOW())";

if(mysqli_query($conn, $sql)) {
    header("Location: ../index.php?page=contact");
    exit();
} else {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}
?>