<?php

echo "Uninstalling Winner Winner Module<br>";

// mysql table name(s) used for this module
$table = "wwinner_config";


//Drop Tables
out("Dropping all relevant tables");
$sql = "DROP TABLE `".$table."`";
$result = sql($sql);