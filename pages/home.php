<?php
include 'connect.php';
$sql = "SELECT * FROM loaiphong";
$result = mysqli_query($conn, $sql);
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
        <img src="images/banner.jpg" alt="ảnh bìa" style="width:100%; height: 400px; object-fit: cover;">
        <input type="search" placeholder="Tìm kiếm...">
    </div>
    <h2 style="text-align: center; margin-top: 20px;">Hạng phòng của chúng tôi</h2>
    <h3 style="text-align: center; margin-top: 10px;">Chúng tôi cung cấp nhiều loại phòng với chất lượng tỉ mỉ, mang đến sự đa dạng phù hợp với mọi nhu cầu của quý khách.</h3>

    <div class="home__flex">

        <?php

        while ($row = mysqli_fetch_assoc($result)) {
            $id = $row['id'];
            $sqlDiscount = "SELECT * FROM khuyenmai WHERE id_loaiphong = $id";
            $resultDiscount = mysqli_query($conn, $sqlDiscount);
            $hasDiscount = ($resultDiscount && mysqli_num_rows($resultDiscount) > 0);
        ?>
            <div class="home__item">
                <!-- Thẻ chứa hình ảnh và nhãn giảm giá -->
                <div style="position: relative; width: 100%; height: 200px;">
                    <img src="images/<?php echo htmlspecialchars($row['anhphong']); ?>"
                        alt="Ảnh phòng"
                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">

                    <!-- Thẻ giảm giá nằm ở góc trên bên phải ảnh -->
                    <?php if ($hasDiscount):
                        $discountData = mysqli_fetch_assoc($resultDiscount);
                    ?>
                        <div style="position: absolute; top: 10px; right: 10px; background: #ffebee; border: 1.5px solid #ff0000; padding: 4px 8px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.15); z-index: 2; text-align: right;">
                            <span style="font-weight: bold; color: #dc3545; font-size: 13px; display: block;">Giảm giá <?= $discountData['phantramgiamgia'] ?>%</span>
                            <span style="font-style: italic; font-size: 11px; color: #555; display: block;">Đặt từ <?= $discountData['dem'] ?> đêm trở lên</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Thông tin phòng phía dưới ảnh -->
                <h4 style="text-align: center; margin-top: 12px;">
                    <?php echo htmlspecialchars($row['tenphong']); ?>
                </h4>

                <p style="text-align: center; margin-top: 5px;">
                    👥 <strong>Số khách:</strong> <?php echo htmlspecialchars($row['sokhach']); ?> người
                </p>
                <p style="text-align: center; margin-top: 5px;">
                    📐 <strong>Diện tích:</strong><?php echo htmlspecialchars($row['dientich']); ?> m²
                </p>

                <p style="text-align: center; margin-top: 5px; font-weight: bold; color: #b38b4d;">
                    Giá: <?php echo number_format($row['gia'], 0, ',', '.'); ?> đ
                </p>

                <div style="text-align: center; margin-top: 10px;">
                    <a href="index.php?page=room_detail&id=<?php echo $row['id']; ?>" class="btn-solid-gold">
                        Đặt phòng ngay
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="home__flex--tienich">
        <h2 style="text-align: center; margin-top: 40px;">Tiện ích ngay tại khách sạn</h2>
        <h3 style="text-align: center; margin-top: 10px;">Chúng tôi cung cấp nhiều tiện ích đa dạng, từ hồ bơi, phòng tập gym, spa, nhà hàng, quán bar, đến các dịch vụ giải trí khác, nhằm mang đến trải nghiệm tuyệt vời cho quý khách.</h3>
        <img src="images/hoboi.jpg" alt="tiện ích">
        <img src="images/spa.jpg" alt="tiện ích">
        <img src="images/nhahang.jpg" alt="tiện ích">
        <img src="images/gym.jpg" alt="tiện ích">
        <img src="images/bar.jpg" alt="tiện ích">
        <img src="images/vuon.jpg" alt="tiện ích">
    </div>

</body>

</html>