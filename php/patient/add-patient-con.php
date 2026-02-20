<?php
session_start(); 
include "db_conn.php";


if (isset($_POST['P_ID']) && isset($_POST['P_Name']) && isset($_POST['Gender']) && isset($_POST['Age']) && isset($_POST['P_Type']) && isset($_POST['A_Date'])) {

    $P_Name = mysqli_real_escape_string($conn, $_POST['P_Name']);
    $Gender = mysqli_real_escape_string($conn, $_POST['Gender']);
    $Age = mysqli_real_escape_string($conn, $_POST['Age']);
    $P_Type = mysqli_real_escape_string($conn, $_POST['P_Type']);
    $A_Date = mysqli_real_escape_string($conn, $_POST['A_Date']);


   $sql = "INSERT INTO patients (full_name, gender, age, patient_type, admit_date) VALUES ('$P_Name', '$Gender', '$Age', '$P_Type', '$A_Date')";
   mysqli_query($conn,$sql);
   header("location:patient.php");
}

?>