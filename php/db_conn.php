<?php

$sname= "localhost";
$unmae= "root";
$password = "Confrontation@433";

$db_name = "rmu_medical_sickbay";

$conn = mysqli_connect($sname, $unmae, $password, $db_name);

if (!$conn) {
	echo "Connection failed!";
}