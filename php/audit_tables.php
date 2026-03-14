<?php
require_once 'db_conn.php';
$tables_to_describe = ['users','staff','staff_directory','notifications','audit_log'];
foreach($tables_to_describe as $t){
    echo "\n=== $t ===\n";
    $res = mysqli_query($conn,"DESCRIBE `$t`");
    if(!$res){echo "NOT FOUND\n";continue;}
    while($r=mysqli_fetch_assoc($res)) echo $r['Field']."  ".$r['Type']."\n";
}
?>
