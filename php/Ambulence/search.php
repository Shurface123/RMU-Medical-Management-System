<!DOCTYPE html>
<html lang="en">

<head>
    <title>RMU MEDICAL SICKBAY</title>
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="shortcut icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="apple-touch-icon" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mochiy+Pop+P+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">

    <style>
        body {
            background: rgba(96, 193, 138, 0.722);
        }

        .slidebar {
            border: 20px;
            width: 15%;
            height: 700px;
            position: fixed;
            top: 0px;
            background-color: rgba(195, 179, 179, 0.493);
        }

        .slidebar header {
            padding: 30px 10px;
            text-align: center;
            font-family: 'Mochiy Pop P One', sans-serif;
            font-size: 30px;
            font-weight: bolder;
            color: rgb(16, 95, 98);
        }

        .slidebar header span {
            font-size: 50px;
            color: rgb(89, 161, 87);
        }

        .slidebar ul li {
            list-style: none;
            padding: 10px;
            font-family: 'Lucida Sans', 'Lucida Sans Regular', 'Lucida Grande', 'Lucida Sans Unicode', Geneva, Verdana, sans-serif;
            font-weight: bolder;
        }

        .slidebar ul li:hover {
            transform: scale(1.1);
            transition: .5s;
            border-radius: 10px;
            background-color: rgb(89, 150, 152);
            margin-right: 20px;
        }

        .slidebar ul li a {
            text-decoration: none;
        }

        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }

        h2 { font-size: 40px; }
        table { border: 2px solid black; }
        th { padding: 7px; border: 2px solid black; font-size: 22px; }
        td { border: 2px solid black; padding: 5px 10px 5px 20px; font-size: 15px; font-weight: bolder; }

        button {
            border: solid 2px black;
            border-radius: 15px;
            padding: 2px 6px;
            background-color: rgb(167, 127, 169);
        }

        button a {
            text-decoration: none;
            color: black;
            font-weight: bolder;
            padding: 2px 3px;
        }

        button:hover {
            background-color: greenyellow;
            transition: .5s;
            transform: scale(1.1);
        }

        .logout {
            text-align: center;
            margin-top: 10px;
            font-family: 'Mochiy Pop P One', sans-serif;
            font-weight: bolder;
        }

        .logout:hover {
            transform: scale(1.2);
            color: red;
            transition: 1s;
        }
    </style>
</head>

<body>

    <div class="slidebar">
        <header>
            <span><i class="fas fa-users-cog"></i><br></span>
            ADMIN
        </header>
        <ul>
            <li><a href="/php/home.php"><i class="fas fa-home"></i> HOME</a></li>
            <li><a href="/php/Doctor/doctor.php"><i class="fas fa-user-md"></i> DOCTORS</a></li>
            <li><a href="/php/staff/staff.php"><i class="fas fa-user-nurse"></i> STAFFS</a></li>
            <li><a href="/php/patient/patient.php"><i class="fas fa-user-injured"></i> PATIENTS</a></li>
            <li><a href="/php/test/test.php"><i class="fas fa-file-medical-alt"></i> TESTS</a></li>
            <li><a href="/php/bed/bed.php"><i class="fas fa-procedures"></i> BED</a></li>
            <li><a href="/php/Ambulence/ambulence.php"><i class="fas fa-ambulance"></i> AMBULANCE</a></li>
            <li><a href="/php/medicine/medicine.php"><i class="fas fa-medkit"></i> MEDICINE</a></li>
        </ul>
        <a style="text-decoration: none;" href="/php/index.php">
            <div class="logout">Log Out</div>
        </a>
    </div>

    <div class="container">
        <div class="row">
            <div style="margin-left: 50%; margin-top: 5%;">
                <h2>MANAGE <b>AMBULANCE</b></h2>
            </div>
            <div style="margin-left: 80%; margin-top: 3%;">
                <button><a href="/php/Ambulence/add-ambulence.php">ADD AMBULANCE</a></button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div style="margin-left: 20%; margin-top: 1%;">

            <form method="post" action="search.php">
                <input type="text" name="search" required />
                <input type="submit" value="Search" />
            </form>
            <br>

            <table id="dataTable" width="90%" cellspacing="10">
                <thead>
                    <tr>
                        <th>AMBULANCE ID</th>
                        <th>VEHICLE NUMBER</th>
                        <th>DRIVER</th>
                        <th>STATUS</th>
                        <th>OPERATION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['search'])) {
    include 'db_conn.php';
    $search = mysqli_real_escape_string($conn, $_POST['search']);
    $sql = "SELECT * FROM ambulances 
                                WHERE ambulance_id LIKE '%$search%' 
                                   OR vehicle_number LIKE '%$search%' 
                                   OR driver_name LIKE '%$search%' 
                                   OR status LIKE '%$search%'";
    $query = mysqli_query($conn, $sql);
    if ($query && mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_assoc($query)) {
?>
                        <tr>
                            <td><b><?php echo htmlspecialchars($row['ambulance_id']); ?></b></td>
                            <td><?php echo htmlspecialchars($row['vehicle_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['driver_name'] ?? 'Unassigned'); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td style="width: 160px;">
                                <button><a href="/php/Ambulence/update.php?id=<?php echo $row['id']; ?>"><b>UPDATE</b></a></button>
                                <button><a href="/php/Ambulence/Delete.php?id=<?php echo $row['id']; ?>"
                                    onclick="return confirm('Delete this ambulance?');"><b>DELETE</b></a></button>
                            </td>
                        </tr>
                    <?php
        }
    }
    else {
        echo '<tr><td colspan="5" style="text-align:center;padding:1rem;">No results found.</td></tr>';
    }
}
?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>