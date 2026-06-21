<?php
require_once 'includes/config.php';
foreach(['applications','jobs','companies','coding_problems'] as $t){
    echo "<h3>$t</h3><pre>";
    $r=$conn->query("SHOW COLUMNS FROM $t");
    while($row=$r->fetch_assoc()) echo $row['Field'].' - '.$row['Type']."\n";
    echo "</pre>";
}
