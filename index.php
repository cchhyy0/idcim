<?php
	require("./idcimClass/Database.php");
	$database=new idcimclass\Database();
	$result=$database->fetchAll("SELECT * FROM tbladmins");  
	echo "<pre>";print_r($result);exit;
?>