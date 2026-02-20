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

    <!-- font ends -->

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
            border: 2px solid black;
            font-size: 20px;
        }

        td {
            border: 2px solid black;
            padding: 5px 10px 5px 20px;
            font-size: 15px;
            font-weight: bolder;
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

        /* Stock level badges */
        .badge {
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: bold;
        }

        .badge-ok {
            background-color: #a8e6a3;
            color: #1a5c17;
        }

        .badge-low {
            background-color: #fde8a0;
            color: #7d5a00;
        }

        .badge-critical {
            background-color: #f5b7b1;
            color: #7b241c;
        }

        .rx-badge {
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            background-color: #d6eaf8;
            color: #1a5276;
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
            min-width: 130px;
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
            <div style="margin-left: 45%; margin-top: 5%;">
                <h2>MANAGE <b>MEDICINES</b></h2>
            </div>
            <div style="margin-left: 81%; margin-top: 3%;">
                <button><a href="/php/medicine/add-medicine.php">ADD MEDICINE</a></button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div style="margin-left: 20%; margin-top: 1%;">

            <?php
            include 'db_conn.php';

            // Summary counts
            $total      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines"))[0];
            $low_stock  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE stock_quantity <= reorder_level AND stock_quantity > 0"))[0];
            $out_stock  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines WHERE stock_quantity = 0"))[0];
            ?>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="card">
                    <div class="card-number"><?php echo $total; ?></div>
                    <div class="card-label">Total Medicines</div>
                </div>
                <div class="card">
                    <div class="card-number" style="color: orange;"><?php echo $low_stock; ?></div>
                    <div class="card-label">Low Stock</div>
                </div>
                <div class="card">
                    <div class="card-number" style="color: red;"><?php echo $out_stock; ?></div>
                    <div class="card-label">Out of Stock</div>
                </div>
            </div>

            <!-- SEARCH FORM -->
            <form method="post" action="search.php">
                <input type="text" name="search" placeholder="Search by name or category..." required />
                <input type="submit" value="Search" />
            </form>
            <br>

            <table id="dataTable" width="90%" cellspacing="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>MEDICINE ID</th>
                        <th>MEDICINE NAME</th>
                        <th>GENERIC NAME</th>
                        <th>CATEGORY</th>
                        <th>UNIT PRICE (GH₵)</th>
                        <th>STOCK QTY</th>
                        <th>EXPIRY DATE</th>
                        <th>PRESCRIPTION</th>
                        <th>OPERATION</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $sql = "SELECT * FROM medicines ORDER BY category, medicine_name";
                    $query = mysqli_query($conn, $sql);

                    if (!$query) {
                        echo "<tr><td colspan='10'>Query error: " . mysqli_error($conn) . "</td></tr>";
                    } elseif (mysqli_num_rows($query) === 0) {
                        echo "<tr><td colspan='10' style='text-align:center;'>No medicines found. <a href='/php/medicine/add-medicine.php'>Add one now.</a></td></tr>";
                    } else {
                        $row_num = 1;
                        while ($med = mysqli_fetch_assoc($query)) {

                            // Determine stock badge
                            if ($med['stock_quantity'] == 0) {
                                $stock_badge = 'badge-critical';
                                $stock_label = 'Out of Stock';
                            } elseif ($med['stock_quantity'] <= $med['reorder_level']) {
                                $stock_badge = 'badge-low';
                                $stock_label = 'Low';
                            } else {
                                $stock_badge = 'badge-ok';
                                $stock_label = 'OK';
                            }
                    ?>
                        <tr>
                            <td><?php echo $row_num++; ?></td>
                            <td><b><?php echo htmlspecialchars($med['medicine_id']); ?></b></td>
                            <td><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                            <td><?php echo htmlspecialchars($med['generic_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($med['category'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($med['unit_price'], 2); ?></td>
                            <td>
                                <?php echo $med['stock_quantity']; ?>
                                <span class="badge <?php echo $stock_badge; ?>"><?php echo $stock_label; ?></span>
                            </td>
                            <td><?php echo $med['expiry_date'] ? $med['expiry_date'] : 'N/A'; ?></td>
                            <td>
                                <?php if ($med['is_prescription_required']): ?>
                                    <span class="rx-badge">Rx Required</span>
                                <?php else: ?>
                                    <span class="rx-badge" style="background-color:#d5f5e3; color:#1e8449;">OTC</span>
                                <?php endif; ?>
                            </td>
                            <td style="width: 140px;">
                                <button><a href="/php/medicine/update.php?medicine_id=<?php echo $med['medicine_id']; ?>"><b>UPDATE</b></a></button>
                                <button><a href="/php/medicine/Delete.php?medicine_id=<?php echo $med['medicine_id']; ?>"><b>DELETE</b></a></button>
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