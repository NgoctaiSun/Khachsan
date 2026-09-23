<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Liên hệ</title>
<style>
    body{
        background-color: #c9b38a;
    }
.lienheflex{
width:60%;
margin:auto;
background:white;
padding:30px;
border: #d11d1d 1px solid;
}

h2{
border-bottom:3px solid #2c5aa0;
padding-bottom:10px;
text-align: center;
}

.contact-card__main--left{
text-align:center;
line-height:1.8;
margin-bottom:40px;
}
.contact-card__main--right{
width:50%;}

.hotline{
margin-top:20px;
font-weight:bold;
}

.contact-form{
display:flex;
flex-direction:column;
gap:20px;
}
.contact-form input,
.contact-form textarea{
border:none;
border-bottom:2px solid #ccc;
padding:10px;
font-size:16px;
outline:none;
}

.contact-form textarea{
height:100px;
}

button{
width:120px;
margin:auto;
padding:10px;
background:#1e6f7a;
color:white;
border:none;
border-radius:5px;
cursor:pointer;
font-size:16px;
}

button:hover{
background:#0d5aa7;
}
.contact-card__main{
    display: flex;
    border: #079110 1px solid;
}
</style>
</head>

<body>

<div class="lienheflex">

<h2>LIÊN HỆ</h2>
<div class="contact-card__main">
<div class="contact-card__main--left">
<p><b>Happy Travel - đơn vị tổ chức tour chuyên nghiệp</b></p>

<p>HAPPY TRAVEL</p>

<p>📍 123 ĐƯỜNG VÕ TRƯỜNG TOẢN - NINH KIỀU - CẦN THƠ</p>

<p>✉ happytravel@gmail.com</p>

<p>Thời gian làm việc tại văn phòng:</p>

<p>8:00 - 17:00 Thứ 2 đến thứ 6</p>

<p>08:00 - 12:00 Thứ 7</p>
<p class="hotline">
Quý khách vui lòng liên hệ: <b>0335935101</b>
</p>
</div>

<div class="contact-card__main--right" style="border: #0d5aa7 1px solid;">
<form class="contact-form" method="post" action="xuly_lienhe.php">
<label for="hoten">Họ và tên:</label>
<input type="text" placeholder="Họ và tên *" name="hoten" required>

<label for="sdt">Số điện thoại:</label>
<input type="text" placeholder="Số điện thoại *" name="sdt" required>

<label for="email">Email:</label>
<input type="email" placeholder="Email *" name="email" required>

<label for="diachi">Địa chỉ:</label>
<input type="text" placeholder="Địa chỉ" name="diachi">

<label for="noidung">Nội dung:</label>
<textarea placeholder="Nội dung" name="noidung"></textarea>

<button type="submit">📩 Gửi</button>

</form>
</div>


</div>

</div>

</body>
</html>