<?php
	require("./idcimClass/database/Connection.php");
	$database=new Cchhyy\Database\Connection("mysql:dbname=whmcspojie;host=127.0.0.1;port:3306", "root", "root");
	$result=$database->fetchAll("SELECT * FROM tbladmins");  
	echo "<pre>";print_r($result);exit;
?>