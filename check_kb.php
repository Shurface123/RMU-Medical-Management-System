<?php
require 'php/db_conn.php';
$r = mysqli_query($conn, "DESCRIBE chatbot_knowledge_base");
while($row = mysqli_fetch_assoc($r)) echo $row['Field'] . ' | ' . $row['Type'] . PHP_EOL;
mysqli_close($conn);
