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
line-height:1.8;
margin-bottom:40px;
flex:1;
border:#d11d1d 1px solid;
text-shadow: 2px 2px 4px rgba(189, 146, 5, 0.5);
}
.contact-card__main--right{
flex:1;}

.hotline{
margin-top:20px;
font-weight:bold;
text-align:center;
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
    flex-wrap: wrap;
    border: #079110 1px solid;
}
.contact-features {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 15px 0;
}
</style>
</head>

<body>

<div class="lienheflex">

<h2>LIÊN HỆ</h2>
<div class="contact-card__main">
<div class="contact-card__main--left">
<p style="font-weight:bold; text-align:center;">HARMONY Hotel & Resort</p>
<div class="contact-info">
<p>📍 Địa chỉ: Khu vực I - Hưng Phú - TP Cần Thơ</p>

<p>✉ Email: harmonycantho@gmail.com</p>
</div>
<div class="contact-features">
        <span class="feature-tag">✔ Sạch sẽ & Đầy đủ tiện nghi</span>
        <span class="feature-tag">✔ Dịch vụ thân thiện, chu đáo</span>
        <span class="feature-tag">✔ Bảo mật thông tin khách hàng</span>
    </div>

<p class="hotline">
Quý khách vui lòng liên hệ: <b>0335935101</b>
</p>
</div>

<div class="contact-card__main--right" style="border: #0d5aa7 1px solid;">
<form class="contact-form" method="post" action="pages/process_contact.php">
<label for="hoten">Họ và tên:</label>
<input type="text" placeholder="Họ và tên *" name="name" required>

<label for="sdt">Số điện thoại:</label>
<input type="text" placeholder="Số điện thoại *" name="phone" required>

<label for="email">Email:</label>
<input type="email" placeholder="Email *" name="email" required>

<label for="diachi">Địa chỉ:</label>
<input type="text" placeholder="Địa chỉ" name="address">

<label for="noidung">Nội dung:</label>
<textarea placeholder="Nội dung" name="content"></textarea>

<button type="submit">📩 Gửi</button>

</form>
</div>


</div>

</div>

</body>
</html>