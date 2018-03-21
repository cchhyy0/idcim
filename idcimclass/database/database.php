<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace idcimclass\Database;   
use PDO;
use PDOException;

/**
 * 
 */

class Pdosql
{
		
	private $host;
	private $user;
	private $password;
	private $database;
	private $conn;
	private $sql_error = true;
	//构造函数
	public function __construct($host,$port,$user,$password,$database,$conn,$charset,$prefix){
     	$this->prefix=$prefix;   
     	$this->database=$database;
		$dsn="mysql:dbname=$database;host=$host;port:$port";
		$options = array(
			PDO::ATTR_CASE              =>  PDO::CASE_LOWER,
			PDO::ATTR_ERRMODE           =>  PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_ORACLE_NULLS      =>  PDO::NULL_NATURAL,
			PDO::ATTR_STRINGIFY_FETCHES =>  false,
		);
		try{
			$this->pdo=new PDO($dsn,$user,$password,$options);
			$this->pdo->exec("SET NAMES $charset");
		}catch(PDOException $e){
			$this->sql_error($e->getMessage());
		}		
    }
	/**
	 * 查询select 
	 * @param  string $table 表名
	 * @param  string $condition 条件 默认所有数据
	 * @param  string $columnName 输出的字段 默认所有字段
	 * @return array
	 */
	public function select($table='',$condition="",$columnName="*"){	
		try{
			$sql="SELECT $columnName FROM `$this->prefix$table` $condition";
			$re=$this->pdo->query($sql);
			return $re->fetchAll(PDO::FETCH_ASSOC);
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}
	/**
	 * 输出一条数据
	 * @param  string $table 表名
	 * @param  string $condition 条件 默认所有数据
	 * @param  string $columnName 输出的字段 默认所有字段
	 * @return array
	 */
	public function selectone($table,$condition="",$columnName="*",$orderby,$sorting){
		$orderby=(!empty($orderby))?$orderby:'id';
		$sorting=(!empty($sorting))?$sorting:'ASC';
		try{	
			$sql="SELECT $columnName FROM `$this->prefix$table` $condition ORDER BY $orderby $sorting LIMIT 1";
			$re=$this->pdo->query($sql);
			return $re->fetch(PDO::FETCH_ASSOC);
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}
	/**
	 * 计算数据数量
	 * @param  string $table 表名
	 * @param  string $condition 条件 默认所有数据
	 * @param  string $columnName 计算的字段 默认字段为ID
	 * @return int
	 */
	public function selectcount($table,$condition="",$columnName="*"){
		try{
			$sql="SELECT COUNT($columnName) AS count FROM `$this->prefix$table` $condition";
			$re=$this->pdo->query($sql);
			$count = $re->fetch(PDO::FETCH_ASSOC);		
			return $count['count'];
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}
	/**
	 * 插入insert
	 * @param  string $table 表名
	 * @param  array $array 插入的数组
	 * @return int
	 */
	public function insert($table,$array){
	
		$num_fields=$this->num_fields($table,"field");
		foreach($num_fields as $field_k=>$field_v){
			if(!isset($array[$field_k]) || trim($array[$field_k])===""){
				$array[$field_k]=$field_v;
			}
		}
		try{	
			foreach ($array as $key => $val){
				$keys[] ="`". $key."`" ;
				$values[] = "'".((get_magic_quotes_gpc()) ? trim($val) : addslashes(trim($val)))."'";
			}
			$sql="INSERT INTO `$this->prefix$table` (".implode(',', $keys).") VALUES (".implode(',', $values).")";
			$this->pdo->exec($sql);
			return $this->pdo->lastInsertId();
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}
	/**
	 * 批量插入insert
	 * @param  string $table 表名
	 * @param  array $key 插入的数据的键(一维数组)
	 * @param  array $array 插入的数组(二位数组)
	 * @return int
	 */
	public function batchInsert($table,$key,$array){
		$num_fields=$this->num_fields($table,"field");
		$arr=array();
		if(count($num_fields)!=count($key)){
			$key_flip=array_flip($key);
			foreach($num_fields as $field_k=>$field_v){
				if(!isset($key_flip[$field_k])){
					$key[]=$field_k;
					$arr[$field_k]=$field_v;
				}
			}
		}
		try{
			foreach ($key as $val){
				$keys[] ="`". $val."`" ;
			}
			$values="";$ke=0;
			foreach ($array as $val){
				$value=array();
				$val=array_merge($val,$arr);			
				foreach($val as $k=>$v){
					if(trim($v)==="") $v=$num_fields[$k];					
					$value[] = "'".((get_magic_quotes_gpc()) ? trim($v) : addslashes(trim($v)))."'";
				}
					
				$values.="(".implode(',', $value).")";
				if(($ke+1)<count($array))$values.=",";
				$ke++;
			}
			$sql="INSERT INTO `$this->prefix$table` (".implode(',', $keys).") VALUES $values";
			$this->pdo->exec($sql);
			return $this->pdo->lastInsertId();
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}  	
	/**
	 * 修改update
	 * @param  string $table 表名
	 * @param  array $array 插入的数组
	 * @param  string $id 修改的字段
	 * @return string
	 */ 
	public function update($table,$array,$id){  
		try{
			foreach ($array as $key => $val){
				$valstr[] = "`". $key . "` = '" . ((get_magic_quotes_gpc()) ? $val : addslashes($val)) . "'";
			}
			$sql="UPDATE `$this->prefix$table` SET ".implode(' , ', $valstr)." WHERE ".$id;		
			return $this->pdo->exec($sql);
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}
	/**
	 * 批量修改update
	 * @param  string $table 表名
	 * @param  array $array_key 插入的数据的键(一维数组)
	 * @param  array $array 插入的数组(二维数组)
	 * @param  string $kid 修改的条件值是唯一的
	 * @param  string $where 修改的条件
	 * @return string
	 */ 
	public function batchUpdate($table,$array_key,$array,$kid="id",$where){  
		try{
			$when="";$in_id="";$ke=0;
			$where=(!empty($where))?" AND ".$where:"";
			foreach ($array_key as $val){
				$keys[] ="`". $val."`" ;
				$when.="`$val`=CASE `$kid`";
				foreach ($array as $k => $v){
					//$when.=" WHEN '".$v[$kid]."' THEN '".$v[$val]."'";	
					$when.=" WHEN '".$v[$kid]."' THEN '" . ((get_magic_quotes_gpc()) ? $v[$val] : addslashes($v[$val])) . "'";	
					
					if($ke==0) $in_id.="'".$v[$kid]."',";	
				}
				$when.=" END";
				if(($ke+1)<count($array_key))$when.=",";
				$ke++;
			}			
			$sql="UPDATE `$this->prefix$table` SET ".$when." WHERE ".$kid." IN (".trim($in_id,",").") $where";
			return $this->pdo->exec($sql);
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	} 	
	/**
	 * 删除delete
	 * @param  string $table 表名
	 * @param  string $condition 条件 
	 * @return string
	 */ 
	public function delete($table,$condition){  
		try{
			$sql="DELETE FROM `$this->prefix$table` WHERE $condition";
			return $this->pdo->exec($sql);
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	} 
	/**
	 * 批量删除delete
	 * @param  string $table 表名
	 * @param  array $array 条件值(一维数组)
	 * @param  string $condition 条件键
	 * @return string
	 */ 
	public function batchDelete($table,$array,$condition="id"){ 
		try{
			$in_id="";
			foreach($array as $v){
				$in_id.=$v.",";
			}
			$sql="DELETE FROM `$this->prefix$table` WHERE $condition IN (".trim($in_id,",").")";
			return $this->pdo->exec($sql);
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}	
	/**
	 * 数据库执行语句
	 * @param  string $sql sql语句
	 * @return string,array
	 */ 
	public function query($sql){		   	    	 
		try{
    		if(strpos(trim(strtolower($sql)),'select') === 0){
				$re = $this->pdo->query($sql);		
				return $re->fetchAll(PDO::FETCH_ASSOC);				
			}else{
				return $this->pdo->exec($sql);
			}			
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}			
	/**
	 * 输出显示错误sql语句
	 * @param  string $message 错误提示
	 * @param  string $sql sql语句
	 */
	public function sql_error($error,$sql=""){
		if(DEBUG===false)_error();
		else{
			echo'<!DOCTYPE html>
				<html>
				<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>错误提示!</title>
				<style>
				system{FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; COLOR: #333; background:#fff; FONT-SIZE: 12px;padding:0px;margin:0px;}
				a{text-decoration:none;color:#3071BF}
				a:hover{text-decoration:underline;}
				.error_title{border-bottom:1px #ddd solid;font-size:20px;line-height:28px; height:28px;font-weight:600}
				.error_box{border-left:3px solid #f00;font-size:14px; line-height:22px; padding:6px 15px;background:#f3f3f3}
				.error_tip{margin-top:15px;padding:6px;font-size:12px;padding-left:15px;background:#f7f7f7}
				</style>
				</head>
				<body>
				<div style="margin:30px auto; width:1000px;">
				<div class="error_title">PDO错误提示</div>
				<div style="height:10px"></div>			
				<div class="error_box">出错信息：'.$error.'</div>
				<div class="error_box">SQL语句:'.$sql.'</div>
				</div>
				</body>
				</html>';
			exit;	
		}
	}	
	/**
	 * 创建添加新的数据库
	 * @param  string $database 数据库名称
	 */
	public function create_database($database_name){
		try{
			$sqlDatabase = 'CREATE DATABASE '.$database_name;
			$this->pdo->query($sqlDatabase);
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}	
	/**
	 * 查询服务器所有数据库
	 * @param  string $database 数据库名称
	 * @return array
	 */	
	public function databases(){
		try{
			$re =$this->pdo->query("SHOW DATABASES");
			$dblist = array();
			while($row = $re->fetch(PDO::FETCH_ASSOC)){
				$dblist[]=$row['database'];
			} 
			return $dblist;
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}	
	/**
	 * 查询数据库下所有的表
	 * @param  string $database 数据库名称
	 * @return array
	 */
	function show_tables($database_name){
		if(empty($database_name))$database_name=$this->database;
		try{
			$re =$this->pdo->query("SHOW TABLES FROM `$database_name`");
			$tabList = array();
			while($row = $re->fetch(PDO::FETCH_ASSOC)){
				$tabList[] = $row["tables_in_".$database_name];
			} 
			return $tabList;
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}	 
	/**
	 * 查询字段数量
	 * @param  string $table 数据表名称
	 * @return array
	 */
	public function num_fields($table_name,$type="all"){ 
		$field_default=array(
			'tinyint'=>0,
			'smallint'=>0,
			'mediumint'=>0,
			'int'=>0,
			'bigint'=>0,
			'float'=>0,
			'double'=>0,
			'decimal'=>0,
			'date'=>'0000-00-00',//
			'time'=>'00:00:00',
			'datetime'=>'0000-00-00 00:00:00',
			'timestamp'=>'00000000000000',
			'year'=>'0000',
		);
		try{
			$re =$this->pdo->query("SHOW COLUMNS FROM `$table_name`");
			while($row = $re->fetch(PDO::FETCH_ASSOC)){
				if($type=="field"){
					if($row['extra']=="" && $row['default']==""){
						$strpos=strpos($row['type'],"(");
						if($strpos>0) $row['type']=substr($row['type'],0,$strpos);
						$row['type']=strtolower($row['type']);
						if(isset($field_default[$row['type']])){
							$values=$field_default[$row['type']];
						}else{
							$values="";
						}
						$fields[$row['field']]=$values;
					}
				}else{
					$fields[]=$row;
				}				
			}
			return $fields;
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}
	/**
	 * 创建数据表时sql代码
	 * @param  string $table 数据表名称
	 * @return array
	 */
	public function create_table($table_name){ 	
		try{
			$re =$this->pdo->query("SHOW CREATE TABLE `$table_name`");
			$row = $re->fetch(PDO::FETCH_ASSOC);
			return $row;
		}catch(PDOException $e){
			$this->sql_error($e->getMessage(),$sql);
		}
	}
	/**
	 * 导出数据表结构
	 * @param  string $database_name 数据库名,为空当前数据库
	 * @param  string $file_name 保存的文件名
	 */
	public function exportdb_make($file_name='',$database_name){
		if(empty($database_name))$database_name=$this->database;
		$tablist=$this->show_tables($database_name);
		$file_name=(empty($file_name)) ? $database_name."_make.sql" : $file_name;
		$rn=PHP_EOL;
		$sql_h = "-- ----------------------------".$rn;
		$sql_h .= "-- Author:xionglingyuan".$rn;
		$sql_h .= "-- 日期：".date("Y-m-d H:i:s",time()).$rn;
		$sql_h .= "-- ----------------------------".$rn.$rn;
		$createtable='';
		foreach($tablist as $v){
			$table=$this->create_table($v);
			$info = "-- ----------------------------".$rn;
			$info .= "-- Table structure for `".$table['table']."`".$rn;
			$info .= "-- ----------------------------".$rn;
			$info .= "DROP TABLE IF EXISTS `".$table['table']."`;".$rn;
			$createtable .= $info.$table['create table'].";".$rn.$rn;			
		}
		$database=$sql_h.$createtable;
		file_put_contents($file_name,$database);
	}
	/**
	 * 导出数据表数据
	 * @param  string $database_name 数据库名
	 * @param  string $file_name 保存的文件名
	 */
	public function exportdb_data($file_name='',$database_name){
		if(empty($database_name))$database_name=$this->database;
		$tablist=$this->show_tables($database_name);
		$file_name=(empty($file_name)) ? $database_name."_data.sql" : $file_name;
		$rn=PHP_EOL;
		$sql_h = "-- ----------------------------".$rn;
		$sql_h .= "-- Author:xionglingyuan".$rn;
		$sql_h .= "-- 日期：".date("Y-m-d H:i:s",time()).$rn;
		$sql_h .= "-- ----------------------------".$rn.$rn;
		if(file_exists($file_name)){
			file_put_contents($file_name,'');
		}
		file_put_contents($file_name,$sql_h,FILE_APPEND);
		foreach($tablist as $val){
			$sql = "select * from `".$val."`";
			$re = $this->pdo->query($sql);
			if($re->columnCount()<1) continue;
			$info = "-- ----------------------------".$rn;
			$info .= "-- Records for `".$val."`".$rn;
			$info .= "-- ----------------------------".$rn;
			file_put_contents($file_name,$info,FILE_APPEND);
			while($row = $re->fetch(PDO::FETCH_ASSOC)){
				$sqlStr = "INSERT INTO `".$val."` VALUES (";
				foreach($row as $fields){
					$sqlStr .= "'".((get_magic_quotes_gpc()) ? $fields : addslashes($fields))."', ";
				}
				$sqlStr = trim($sqlStr,", ");
				$sqlStr .= ");".$rn;
				file_put_contents($file_name,$sqlStr,FILE_APPEND);
			}
		}
	}
	/**
	 * 导出数据库
	 * @param  string $database_name 数据库名
	 * @param  string $file_name 保存的文件名
	 */
	public function exportdb($file_name='',$database_name){
		if(empty($database_name))$database_name=$this->database;
		$tablist=$this->show_tables($database_name);
		$file_name=(empty($file_name)) ? $database_name."_data.sql" : $file_name;
		$rn=PHP_EOL;
		$sql_h = "-- ----------------------------".$rn;
		$sql_h .= "-- Author:xionglingyuan".$rn;
		$sql_h .= "-- 日期：".date("Y-m-d H:i:s",time()).$rn;
		$sql_h .= "-- ----------------------------".$rn.$rn;
		if(file_exists($file_name)){
			file_put_contents($file_name,'');
		}
		file_put_contents($file_name,$sql_h,FILE_APPEND);
		foreach($tablist as $val){
			$createtable='';
			$table=$this->create_table($val);
			$createinfo = "-- ----------------------------".$rn;
			$createinfo .= "-- Table structure for `".$table['table']."`".$rn;
			$createinfo .= "-- ----------------------------".$rn;
			$createinfo .= "DROP TABLE IF EXISTS `".$table['table']."`;".$rn;
			$createtable .= $createinfo.$table['create table'].";".$rn.$rn;	
			
			
			$sql = "select * from `".$val."`";
			$re = $this->pdo->query($sql);
			if($re->columnCount()<1){
				file_put_contents($file_name,$createtable,FILE_APPEND);
				continue;
			}
			$info =$createtable;
			$info .= "-- ----------------------------".$rn;
			$info .= "-- Records for `".$val."`".$rn;
			$info .= "-- ----------------------------".$rn;
			file_put_contents($file_name,$info,FILE_APPEND);
			while($row = $re->fetch(PDO::FETCH_ASSOC)){
				$sqlStr = "INSERT INTO `".$val."` VALUES (";
				foreach($row as $fields){
					$sqlStr .= "'".((get_magic_quotes_gpc()) ? $fields : addslashes($fields))."', ";
				}
				$sqlStr = trim($sqlStr,", ");
				$sqlStr .= ");".$rn;
				file_put_contents($file_name,$sqlStr,FILE_APPEND);
			}
		}
	}
	//析构函数，自动关闭数据库,垃圾回收机制
	public function __destruct(){
		$this->pdo=null;
	}	
}