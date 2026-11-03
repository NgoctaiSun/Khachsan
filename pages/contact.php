<?php
$conn = mysqli_connect('localhost', 'root', '', 'khachsan_db');
if(isset($_SESSION['id'])) {
    $sql = "SELECT * FROM taikhoan where id=" . $_SESSION['id'];
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
} else {
    $row = null;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Liên hệ - HARMONY Hotel</title>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background: #f5f3ef;
        color: #333;
    }
</style>
</head>

<body>

<div class="lienheflex">

    <!-- Tiêu đề -->
    <div class="contact-heading">
        <span>HARMONY HOTEL & RESORT</span>
        <h2>LIÊN HỆ VỚI CHÚNG TÔI</h2>
        <p>Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ quý khách.</p>
    </div>

    <div class="contact-card__main">

        <!-- Cột trái: Thông tin khách sạn -->
        <div class="contact-card__main--left">

            <p class="hotel-name">HARMONY Hotel & Resort</p>

            <p class="hotel-description">
                Mang đến không gian nghỉ dưỡng thoải mái,
                dịch vụ tận tâm và những trải nghiệm đáng nhớ
                dành cho quý khách.
            </p>

            <div class="contact-info">
                <p>
                    📍 <strong>Địa chỉ</strong><br>
                    Khu vực I - Hưng Phú - TP. Cần Thơ
                </p>

                <p>
                    ✉ <strong>Email</strong><br>
                    harmonycantho@gmail.com
                </p>

                <p>
                    ☎ <strong>Điện thoại</strong><br>
                    0335935101
                </p>
            </div>

            <div class="contact-features">
                <span class="feature-tag">✔ Sạch sẽ & đầy đủ tiện nghi</span>
                <span class="feature-tag">✔ Dịch vụ thân thiện, chu đáo</span>
                <span class="feature-tag">✔ Bảo mật thông tin khách hàng</span>
            </div>

            <div class="hotline">
                Quý khách cần hỗ trợ?
                <b>0335935101</b>
            </div>

        </div>

        <!-- Cột phải: Form liên hệ -->
        <div class="contact-card__main--right">

            <h3>Gửi tin nhắn cho chúng tôi</h3>

            <p class="form-description">
                Vui lòng điền thông tin bên dưới.
                Chúng tôi sẽ tiếp nhận và phản hồi quý khách sớm nhất.
            </p>

            <form class="contact-form" method="post" action="pages/process_contact.php">

                <label for="hoten">Họ và tên *</label>
                <input type="text" id="hoten" name="name"
                    placeholder="Nhập họ và tên" required value="<?= isset($row['hoten']) ? $row['hoten'] : '' ?>">

                <label for="sdt">Số điện thoại *</label>
                <input type="tel" id="sdt" name="phone"
                    placeholder="Nhập số điện thoại" required value="<?= isset($row['sdt']) ? $row['sdt'] : '' ?>">

                <label for="email">Email *</label>
                <input type="email" id="email" name="email"
                    placeholder="Nhập địa chỉ email" required value="<?= isset($row['email']) ? $row['email'] : '' ?>">

                <label for="diachi">Địa chỉ</label>
                <input type="text" id="diachi" name="address"
                    placeholder="Nhập địa chỉ của bạn" value="<?= isset($row['diachi']) ? $row['diachi'] : '' ?>">

                <label for="noidung">Nội dung *</label>
                <textarea id="noidung" name="content"
                    placeholder="Nhập nội dung liên hệ..." required></textarea>

                <button type="submit">📩 GỬI LIÊN HỆ</button>

            </form>

        </div>

    </div>
</div>

</body>
</html>