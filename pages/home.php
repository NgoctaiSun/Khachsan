<?php 
include '../connect.php'; 
$conn =mysqli_connect('localhost','root','','khachsan_db');
$sql="SELECT * FROM loaiphong";
$result=mysqli_query($conn,$sql);

$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ</title>
    <style>
        .home__flex {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }   
        .home__item {
            width: 300px;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            box-sizing: border-box;
        }
        .home__flex--tienich {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        .home__flex--tienich img {
            width: 300px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div>
        <img src="image/anhbia.jpg" alt="ảnh bìa" style="width:100%; height: 400px; object-fit: cover;">
        <input type="search" placeholder="Tìm kiếm...">
    </div>
    <h2 style="text-align: center; margin-top: 20px;">Hạng phòng của chúng tôi</h2>
        <h3 style="text-align: center; margin-top: 10px;">Chúng tôi cung cấp nhiều loại phòng với chất lượng tỉ mỉ, mang đến sự đa dạng phù hợp với mọi nhu cầu của quý khách.</h3>

    <div class="home__flex">
        
        <div class="home__item" >
            <img src="image/<?php echo $row['anh'] ?? ''; ?>" alt="ảnh phòng" style="width: 100%; height: 300px; object-fit: cover; margin-top: 20px;">
            <h4 style="text-align: center; margin-top: 10px;"><?php echo $row['tenloaiphong'] ?? ''; ?></h4>
            <p style="text-align: center; margin-top: 10px;"><?php echo $row['sokhach'] ?? ''; ?> <?php echo $row['dientich'] ?? ''; ?></p>
            <p style="text-align: center; margin-top: 10px;">Giá: <?php echo $row['gia'] ?? 0; ?> đ</p>
            <input type="button" value="Đặt phòng" style="display: block; margin: 20px auto; padding: 10px 20px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
        </div>
    </div>

    <div class="home__flex--tienich">
        <h2 style="text-align: center; margin-top: 40px;">Tiện ích ngay tại khách sạn</h2>
        <h3 style="text-align: center; margin-top: 10px;">Chúng tôi cung cấp nhiều tiện ích đa dạng, từ hồ bơi, phòng tập gym, spa, nhà hàng, quán bar, đến các dịch vụ giải trí khác, nhằm mang đến trải nghiệm tuyệt vời cho quý khách.</h3>
        <img src="image/tienich.jpg" alt="tiện ích" >
        <img src="image/tienich2.jpg" alt="tiện ích" >
        <img src="image/tienich3.jpg" alt="tiện ích" >
        <img src="image/tienich4.jpg" alt="tiện ích" >
        <img src="image/tienich5.jpg" alt="tiện ích" >
        <img src="image/tienich6.jpg" alt="tiện ích" >
    </div>

</body>
</html>