<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
    $page=isset($_GET['page'])? $_GET['page']:'home';
    if($page=='home'){
        include 'pages/home.php';}
    else if($page=='login'){
        include 'pages/login.php';}
    else if($page=='register'){
        include 'pages/register.php';}
    else {
        include 'pages/home.php';}
    ?>
    <?php include 'pages/footer.php'; ?>
</body>
</html>