<?php
class Database {

	public function GetConnection() {
		
		$server_name = "server";
		$username = "username";
		$password = "password";
		$db_name = "dbname";

		$connection_string = "mysql:host=" . $server_name . ";dbname=" . $db_name;

		$connection = null;
		
		try {
			$this->connection = new PDO($connection_string, $username, $password);
		}
		catch (PDOException $e) {
			echo "error: " . $e->getMessage();
		}
		
		return $this->connection;

	}

}
?>