<?php
class DBController {
	private $host = "localhost:3306";
	private $user = "gee";
	private $password = "Pergamon2023!";
	private $database = "geeapp";
	private $conns;
	
	function __construct() {
		$this->conns = $this->connectDB();
	}
	
	function connectDB() {
		$conns = mysqli_connect($this->host,$this->user,$this->password,$this->database);
		return $conns;
	}
	
	function runQuery($query) {
		$result = mysqli_query($this->conns,$query);
		while($row=mysqli_fetch_assoc($result)) {
			$resultset[] = $row;
		}		
		if(!empty($resultset))
			return $resultset;
	}
	
	function numRows($query) {
		$result  = mysqli_query($this->conns,$query);
		$rowcount = mysqli_num_rows($result);
		return $rowcount;	
	}
}