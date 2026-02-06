<?php
session_start(); 
include "db_conn.php";


if (isset($_POST['P_ID']) && isset($_POST['P_Name']) && isset($_POST['Gender']) && isset($_POST['Age']) && isset($_POST['P_Type']) && isset($_POST['A_Date'])) {

 
    $P_ID = $_POST['P_ID'];
    $P_Name = $_POST['P_Name'];
    $Gender = $_POST['Gender'];
    $Age = $_POST['Age'];
    $P_Type = $_POST['P_Type'];
    $A_Date = $_POST['A_Date'];


   $sql = "insert into patient(P_ID,P_Name,Gender,Age,P_Type,A_Date) values('$P_ID','$P_Name','$Gender','$Age','$P_Type','$A_Date')";
   if(mysqli_query($conn,$sql)){
       echo 'Registration successfully...';
   }else{
    echo 'Error';
   }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMU MEDICAL SICKBAY</title>
    <link rel="shortcut icon" href="https://juniv.edu/images/favicon.ico">
    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- custom css file link  -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/book.css">

</head>

<body>

    <!-- header section starts  -->

    <header class="header">

        <img src="/image/logo-ju-small.png" alt style="height: 70px; width: 65px;">
        <a href="" class="logo">RMU MEDICAL SICKBAY</a>

        <nav class="navbar">
            <a href="/RMU-Medical-Management-System/html/index.html">HOME</a>
            <a href="/RMU-Medical-Management-System/html/services.html">SERVICES</a>
            <a href="/RMU-Medical-Management-System/html/about.html">ABOUT</a>
            <a href="/RMU-Medical-Management-System/html/director.html">DIRECTOR</a>
            <a href="/RMU-Medical-Management-System/html/doctors.html">DOCTORS</a>
            <a href="/RMU-Medical-Management-System/html/staff.html">STAFF</a>
            <a href="/RMU-Medical-Management-System/php/booking.php">BOOKING</a>
            <a href="/RMU-Medical-Management-System/php/index.php">LOGIN</a>
        </nav>

        <div id="menu-btn" class="fas fa-bars"></div>

    </header>

    <!-- header section ends -->


<div style="margin-left: 40%; margin-top: 10%;">
    <h1>REGISTRATION SUCCESSFULLY !!!!</h1>
</div>


    <!-- footer section starts  -->

    <section class="footer">

        <div class="box-container">

            <div class="box">
                <h3>QUICK LINKS</h3>
                <a href="/RMU-Medical-Management-System/html/index.html"> <i class="fas fa-chevron-right"></i> HOME </a>
                <a href="/RMU-Medical-Management-System/html/services.html"> <i class="fas fa-chevron-right"></i> SERVICES </a>
                <a href="/RMU-Medical-Management-System/html/about.html"> <i class="fas fa-chevron-right"></i> ABOUT </a>
                <a href="/RMU-Medical-Management-System/html/doctors.html"> <i class="fas fa-chevron-right"></i> DOCTORS </a>
                <a href="/RMU-Medical-Management-System/php/booking.php"> <i class="fas fa-chevron-right"></i> BOOKING </a>
            </div>

            <div class="box">
                <h3>OUR SERVICES</h3>
                <a href="/html/services.html"> <i class="fas fa-chevron-right"></i> FREE CHECKUPS </a>
                <a href="/html/services.html"> <i class="fas fa-chevron-right"></i> 24/7 AMBULANCE </a>
                <a href="/html/services.html"> <i class="fas fa-chevron-right"></i> MEDICINES </a>
                <a href="/html/doctors.html"> <i class="fas fa-chevron-right"></i> EXPERT DOCTORS </a>
                <a href="/html/services.html"> <i class="fas fa-chevron-right"></i> BED FACILITY </a>
            </div>

            <div class="box">
                <h3>CONTACT INFORMATION</h3>
                <a href="#"> <i class="fas fa-phone"></i> 153 </a>
                <a href="#"> <i class="fas fa-phone"></i> 0502371207 </a>
                <a href="#"> <i class="fas fa-envelope"></i> medicalju123@gmail.com </a>
                <a href="https://www.google.com/maps/place/Regional+Maritime+University/@5.8613756,-0.3410349,10z/data=!4m23!1m16!4m15!1m6!1m2!1s0xfdf8688ea341e25:0xdae24b44d8dd6c04!2sRegional+Maritime+University,+Accra!2m2!1d-0.0644747!2d5.6085149!1m6!1m2!1s0xfdf8688ea341e25:0xdae24b44d8dd6c04!2sRegional+Maritime+University,+Accra!2m2!1d-0.0644747!2d5.6085149!3e0!3m5!1s0xfdf8688ea341e25:0xdae24b44d8dd6c04!8m2!3d5.6085149!4d-0.0644747!16s%2Fm%2F025w3kk?entry=ttu&g_ep=EgoyMDI2MDIwMS4wIKXMDSoKLDEwMDc5MjA3MUgBUAM%3D"> <i class="fas fa-map-marker-alt"></i> REGIONAL MARITIME UNIVERSITY, ACCRA, GHANA. </a>
            </div>

            <div class="box">
                <h3>FOLLOW US</h3>
                <a href="https://www.facebook.com/rmuofficial/"> <i class="fab fa-facebook-f"></i> FACEBOOK </a>
                <a href="https://x.com/rmuofficial?lang=ens"> <i class="fab fa-twitter"></i> TWITTER </a>
                <a href="https://www.instagram.com/rmuofficial/?hl=en"> <i class="fab fa-instagram"></i> INSTAGRAM </a>
                <a href="https://www.linkedin.com/school/regional-maritime-university/?originalSubdomain=ghss"> <i class="fab fa-linkedin"></i> linkedin </a>
                <!-- <a href="#"> <i class="fab fa-pinterest"></i> pinterest </a> -->
            </div>

        </div>

        <div class="credit"> CREATED BY <span>LOVELACE & CRAIG (GROUP SIX)</span> | ALL RIGHTS RESERVED </div>

    </section>

    <!-- footer section ends -->
