<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
    footer{ 
        display: grid;
        grid-template-columns: 1fr 2fr 1fr 1fr;
        column-gap: 20px;
        background-color: #2F3E46;
        padding: 20px;
        border-top: 1px solid #dee2e6;
        color: #fff;}
    a{
        color: #fff;
        text-decoration: none;
    }
    footer h3{
        color: #d4af7c;
        text-shadow: 1px 1px 2px rgba(79, 111, 3, 0.5);
    }
    img:hover{
        transform: scale(1.1);
        transition: 0.3s;
    }
    .footer-one p:hover{
        color: #d4af7c;
        transition: 0.3s;
        transform: scale(1.1);
    }
    </style>
</head>
<body>
<footer>
    <div class="footer-one">
        <h3>Xin chào</h3>
        <p><a href="index.php?page=home">Trang chủ</a></p>
        <p><a href="index.php?page=about">Giới thiệu</a></p>
        <p><a href="index.php?page=contact">Liên hệ</a></p>
    </div>
    <div>
        <h3>Thông tin liên hệ</h3>
        <p>Địa chỉ: Khu vực I, Hưng Phú, TP Cần Thơ</p>
        <p>Email: harmonycantho@company.com</p>
        <p>Điện thoại: 0123456789</p>
    </div>
    <div>
        <h3>Theo dõi chúng tôi</h3>
        <p><img src="images/facebook.jpg" alt="Facebook" width="30" height="30"> Facebook</p>
        <p><img src="images/instagram.jpg" alt="Instagram" width="30" height="30"> Instagram</p>
        <p><img src="images/zalo.jpg" alt="Zalo" width="30" height="30"> Zalo</p>
    </div>
    <div>
        <h3>Phương thức thanh toán</h3>
        <img src="images/visa.jpg" alt="Visa" width="50" height="30" style="border-radius: 20px;">
        <img src="images/momo.jpg" alt="MasterCard" width="50" height="30" style="border-radius: 20px;">
        <img src="images/zalopay.jpg" alt="PayPal" width="50" height="30" style="border-radius: 20px;">

    </div>
</footer>
</body>
</html>