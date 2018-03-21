<?php
	require("./idcimClass/database/database.php");
	$database=new idcimclass\Database\Pdosql();
	$result=$database->fetchAll("SELECT * FROM tbladmins");  
	echo "<pre>";print_r($result);exit;
?>