<!DOCTYPE html>
<html lang="en">

<head>
    <title>RMU MEDICAL SICKBAY</title>
    <link rel="shortcut icon" href="https://juniv.edu/images/favicon.ico">

    <!-- fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mochiy+Pop+P+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">

    <style>
        body {
            background-color: #8CD2AB;
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

        table, th, td {
            border: 2px solid black;
            border-collapse: collapse;
        }

        h2 {
            font-size: 40px;
        }

        th {
            padding: 7px;
            font-size: 20px;
        }

        td {
            padding: 5px 10px 5px 20px;
            font-size: 15px;
            font-weight: bolder;
        }

        .badge {
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: bold;
        }

        .badge-active {
            background-color: #a8e6a3;
            color: #1a5c17;
        }

        .badge-inactive {
            background-color: #f5b7b1;
            color: #7b241c;
        }

        .category-badge {
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 12px;
            background-color: #d6eaf8;
            color: #1a5276;
            font-weight: bold;
        }

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

        a {
            text-decoration: none;
            color: black;
            font-weight: bold;
        }

        /* Summary cards */
        .summary-cards {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .card {
            background: rgba(255,255,255,0.6);
            border: 2px solid #333;
            border-radius: 10px;
            padding: 10px 20px;
            text-align: center;
            min-width: 120px;
        }

        .card .card-number {
            font-size: 28px;
            font-weight: bolder;
            color: rgb(16, 95, 98);
        }

        .card .card-label {
            font-size: 13px;
            font-weight: bold;
            color: #333;
        }
    </style>

</head>

<body>

    <!-- sidebar starts -->
    <div class="slidebar">
        <header>
            <span>
                <i class="fas fa-users-cog"></i><br>
            </span>
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
            <div class="logout">LOG OUT</div>
        </a>
    </div>
    <!-- sidebar ends -->

    <div class="container">
        <div class="row">
            <div style="margin-left: 50%; margin-top: 5%;">
                <h2>MANAGE <b>TESTS</b></h2>
            </div>
            <div style="margin-left: 82%; margin-top: 3%;">
                <button><a href="/php/test/add-test.php">ADD TEST</a></button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div style="margin-left: 20%; margin-top: 1%;">

            <?php
            include 'db_conn.php';

            // Summary counts
            $total      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tests"))[0];
            $active     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tests WHERE is_active = 1"))[0];
            $inactive   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tests WHERE is_active = 0"))[0];
            ?>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="card">
                    <div class="card-number"><?php echo $total; ?></div>
                    <div class="card-label">Total Tests</div>
                </div>
                <div class="card">
                    <div class="card-number" style="color:green;"><?php echo $active; ?></div>
                    <div class="card-label">Active</div>
                </div>
                <div class="card">
                    <div class="card-number" style="color:red;"><?php echo $inactive; ?></div>
                    <div class="card-label">Inactive</div>
                </div>
            </div>

            <!-- SEARCH FORM -->
            <form method="post" action="search.php">
                <input type="text" name="search" placeholder="Search by name or code..." required />
                <input type="submit" value="Search" />
            </form>
            <br>

            <table id="dataTable" width="90%" cellspacing="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>TEST CODE</th>
                        <th>TEST NAME</th>
                        <th>CATEGORY</th>
                        <th>PRICE (GH₵)</th>
                        <th>DURATION</th>
                        <th>STATUS</th>
                        <th>OPERATION</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $sql = "SELECT * FROM tests ORDER BY category, test_name";
                    $query = mysqli_query($conn, $sql);

                    if (!$query) {
                        echo "<tr><td colspan='8'>Query error: " . mysqli_error($conn) . "</td></tr>";
                    } elseif (mysqli_num_rows($query) === 0) {
                        echo "<tr><td colspan='8' style='text-align:center;'>No tests found. <a href='/php/test/add-test.php'>Add one now.</a></td></tr>";
                    } else {
                        $row_num = 1;
                        while ($test = mysqli_fetch_assoc($query)) {
                    ?>
                        <tr>
                            <td><?php echo $row_num++; ?></td>
                            <td><b><?php echo htmlspecialchars($test['test_code']); ?></b></td>
                            <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                            <td><span class="category-badge"><?php echo htmlspecialchars($test['category']); ?></span></td>
                            <td><?php echo number_format($test['price'], 2); ?></td>
                            <td><?php echo $test['duration_mins'] ? $test['duration_mins'] . ' mins' : 'N/A'; ?></td>
                            <td>
                                <?php if ($test['is_active']): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="width: 140px;">
                                <button><a href="/php/test/update.php?id=<?php echo $test['id']; ?>"><b>UPDATE</b></a></button>
                                <button><a href="/php/test/Delete.php?id=<?php echo $test['id']; ?>"><b>DELETE</b></a></button>
                            </td>
                        </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>