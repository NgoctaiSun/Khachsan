<?php 
session_start();
$username=$_POST['name'];
$password=$_POST['password'];

$conn =mysqli_connect('localhost','root','','khachsan_db');

$sql=" select * from taikhoan where hoten='$username'";
$result=mysqli_query($conn,$sql);
$row=mysqli_fetch_assoc($result);


if(isset($row))
    {
        if($row['trangthai'] === 'khoa') {
            echo "<script> alert('Tài khoản của bạn đã bị khóa'); window.location='../index.php?page=login'; </script>";
            exit();
        }
        if(password_verify($password, $row['matkhau']))
            {
             $_SESSION['user']=$username;
             $_SESSION['role']=$row['vaitro'];
             if($row['vaitro']=='admin')
                {
                    echo "<script> alert('Đăng nhập thành công'); window.location='../admin/admin.php'; </script>"   ;
                }
            else {echo "<script> alert('Đăng nhập thành công'); window.location='../index.php'; </script>"   ;}
             
            }
        else {echo "<script> alert('Mật khẩu sai');   window.location='../index.php?page=login';</script>";}
    }
else{ echo "<script> alert ('Tên đăng nhập sai') ;  window.location='../index.php?page=login';</script>";}
?>