<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
        <link rel="stylesheet" href="css/hotel.css">
    <link rel="stylesheet" href="css/form.css">
    <style>
        body {
           min-height: 100vh;
         display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <?php 
   $page = isset($_GET['page']) ? $_GET['page'] : 'home';

if ($page == 'home') {
    include 'pages/home.php';
}
else if ($page == 'login') {
    include 'pages/login.php';
}
else if ($page == 'register') {
    include 'pages/register.php';
}
else if ($page == 'rooms') {
    include 'pages/rooms.php';
}
else if ($page == 'room_detail') {
    include 'pages/room_detail.php';
}
else if ($page == 'booking') {
    include 'pages/booking.php';
}
else if ($page == 'about') {
    include 'pages/about.php';
}
else if ($page == 'contact') {
    include 'pages/contact.php';
}
else if ($page == 'profile') {
    include 'pages/profile.php';
}
else {
    include 'pages/home.php';
}
?>
</body>
</html>
