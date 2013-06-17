<?php

echo "Installing Winner Winner Module<br>";

// mysql table name(s) used for this module
$table = "wwinner_config";

// list of the columns to be included in the $table table.  Add/subtract values to this list and trigger a reinstall to alter the table
// this table is used to store module config info
$cols['id'] = "INT NOT NULL";
$cols['waittime'] = "INT NOT NULL";
$cols['retrytime'] = "INT NOT NULL";
$cols['extensionlength'] = "INT NOT NULL";
$cols['cid'] = "VARCHAR(30)";
$cols['cnam'] = "VARCHAR(30)";
$cols['operator_mode'] = "INT NOT NULL";
$cols['operator_extensions'] = "VARCHAR(30)";
$cols['application'] = "VARCHAR(30)";
$cols['data'] = "VARCHAR(30)";


// create the $table table if it doesn't already exist
$sql = "CREATE TABLE IF NOT EXISTS ".$table." (";
foreach($cols as $key=>$val)  {
	$sql .= $key.' '.$val.', ';
}
$sql .= "PRIMARY KEY (id))";
$check = $db->query($sql);
if (DB::IsError($check)) {
	die_freepbx( "Can not create ".$table." table: ".$sql." - ".$check->getMessage() .  "<br>");
}

//check status of exist columns in the $table table and change as required
$curret_cols = array();
$sql = "DESC ".$table;
$res = $db->query($sql);
while($row = $res->fetchRow()) {
	if(array_key_exists($row[0],$cols))  {
		$curret_cols[] = $row[0];
		//make sure it has the latest definition
		$sql = "ALTER TABLE ".$table." MODIFY ".$row[0]." ".$cols[$row[0]];
		$check = $db->query($sql);
		if (DB::IsError($check)) {
			die_freepbx( "Can not update column ".$row[0].": " . $check->getMessage() .  "<br>");
		}
	} 
	else {
		//remove the column
		$sql = "ALTER TABLE ".$table." DROP COLUMN ".$row[0];
		$check = $db->query($sql);
		if(DB::IsError($check))  {
			die_freepbx( "Can not remove column ".$row[0].": " . $check->getMessage() .  "<br>");
		}
		else {
			print 'Removed no longer needed column '.$row[0].' from '.$table.' table.<br>';
		}
	}
}


//add missing columns to the $table table
foreach($cols as $key=>$val)  {
	if(!in_array($key,$curret_cols))  {
		$sql = "ALTER TABLE ".$table." ADD ".$key." ".$val;
		$check = $db->query($sql);
		if (DB::IsError($check)) {
			die_freepbx( "Can not add column ".$key.": " . $check->getMessage() .  "<br>");
		}
		else {
			print 'Added column '.$key.' to '.$table.' table.<br>';
		}
	}
}


//  Set default values - need mechanism to prevent overwriting existing values 
echo "Installing Default Values<br>";
$sql ="INSERT INTO ".$table." (maxretries, waittime, retrytime, cnam,             cid,    operator_mode, operator_extensions, extensionlength, application, data) ";
$sql .= "               VALUES ('3',        '60',     '60',      'Wake Up Calls',  '*68',  '1',           '00 , 01',           '4',             'AGI',        'wakeconfirm.php')";

$check = $db->query($sql);

// Register FeatureCode - WinnerWinner
$fcc = new featurecode('wwinner', 'reset');
$fcc->setDescription('Reset Contest Counter');
$fcc->setDefault('*2668378');
$fcc->update();
unset($fcc);

?>