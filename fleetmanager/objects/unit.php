<?php
class Unit {

	private $connection;
	private $table_name;
	private $public_vars = [];
	private $primary_key = null;
	
	public function __construct($connection, $table_name){
	
		$this->connection = $connection;
		$this->table_name = $table_name;
		
		$rs = $this->connection->query("DESCRIBE $this->table_name");
		
		foreach ($rs as $row) {
			if ($row["Key"] === "PRI") {
				$this->primary_key = $row["Field"];
			}
			
			$type_data = $this->MySqlTypeToPHPType($row["Type"]);
			$this->public_vars[$row["Field"]] = array("value" => NULL, "type" => $type_data[0], "length" => $type_data[1]);
		}
		
	}
	
	function QueryModified($json) {
	
		$query_string = "SELECT * FROM $this->table_name ";
		
		$query_items = [];
		$query_array = [];
		
		foreach ($json as $key => $value) {
			
			if (in_array($key, $this->GetPublicVars())) {
				
				if (gettype($json[$key]) === "array" ) {
					
					$query_items[] = $key . " >= ?";
					$query_array[] = $json[$key]["min"];
					$query_items[] = $key . " <= ?";
					$query_array[] = $json[$key]["max"];
					
				}
				else {
					
					$query_items[] = $key . " = ?";
					$query_array[] = $value;
					
				}
				
			} else {
				http_response_code(400);        
				return json_encode(array("message" => "Param: '" . $key . "' is not allowed."));  
			}
			
		}
		
		if ($query_items) {
			$query_string .= " WHERE ".implode(" AND ", $query_items);
		}
		
		$rs = $this->connection->prepare($query_string);
		$rs->execute($query_array);
		
		if ($rs->rowCount() <= 0) {	
		
			http_response_code(400);
			return json_encode(array("error:" => array("id" => "1", "msg" => "no records found")));
			
		} else {
		
			$output_array = [];	
			while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
				$public_vars = $this->GetPublicVars();
				$item_array = [];
				foreach ($public_vars as $key) {
					$item_array[$key] = $row[$key]; 
				}
				$output_array[] = $item_array;
			}	
			http_response_code(200);
			return json_encode($output_array);
			
		}
		
	}
	
	function QueryOne() {
		
		$query_string = "SELECT * FROM $this->table_name LIMIT 1";
		$rs = $this->connection->prepare($query_string);
		$rs->execute();
		
		if ($rs->rowCount() <= 0) {	
		
			http_response_code(400);
			return json_encode(array("error:" => array("id" => "1", "msg" => "no records found")));
			
		} else {
		
			$output_array = [];	
			while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
				$public_vars = $this->GetPublicVars();
				$item_array = [];
				foreach ($public_vars as $key) {
					$item_array[$key] = $row[$key]; 
				}
				$output_array[] = $item_array;
			}	
			http_response_code(200);
			return json_encode($output_array);
			
		}
		
	}
	
	function QueryAll() {
		
		$query_string = "SELECT * FROM $this->table_name";
		$rs = $this->connection->prepare($query_string);
		$rs->execute();
		
		if ($rs->rowCount() <= 0) {	
		
			http_response_code(400);
			return json_encode(array("error:" => array("id" => "1", "msg" => "no records found")));
			
		} else {
		
			$output_array = [];	
			while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
				$public_vars = $this->GetPublicVars();
				$item_array = [];
				foreach ($public_vars as $key) {
					$item_array[$key] = $row[$key]; 
				}
				$output_array[] = $item_array;
			}	
			http_response_code(200);
			return json_encode($output_array);
			
		}
		
	}
	
	function Insert($json) {
		
		$insert_params = [];
		$insert_values = [];
		
		$insert_string = "INSERT INTO $this->table_name (";
		
		foreach ($json as $key => $value) {
		
			if ($key !== $this->primary_key && in_array($key, $this->GetPublicVars())) {
				$insert_params[] = $key;
				$insert_values[":" . $key] = $value;
			} else {
				http_response_code(400);        
				return json_encode(array("message" => "Param: '" . $key . "' is not allowed."));  
			}
			
		}
		
		$insert_string .= implode(", " , $insert_params) . ") VALUES (:" . implode(", :", $insert_params) . ")";
		
		$st = $this->connection->prepare($insert_string);
		$st->execute($insert_values);
		
		if ($st->rowCount() > 0) {
			http_response_code(201);        
			return json_encode(array("message" => "Created new: " . get_class($unit) . "."));	
		}
		else {
			http_response_code(400);        
			return json_encode(array("message" => "Unable to create new: " . get_class($unit) . "."));  
		}
		
	}
	
	function Update($json) {
	
		$id = $json[$this->primary_key];
		if ($this->Validate($id, $this->primary_key)) {
			
			$update_params = [];
			$update_values = [];
			
			$update_string = "UPDATE $this->table_name SET ";
			$update_tail = " WHERE $this->primary_key = :$this->primary_key";
			foreach ($json as $key => $value) {
		
				if (in_array($key, $this->GetPublicVars())) {
					
					if ($key !== $this->primary_key) {
						$update_params[] = $key . " = :" . $key;
					}
					$update_values[":" . $key] = $value;
					echo $key . ":" . $value . "|";
					
				} else {
					http_response_code(400);        
					return json_encode(array("message" => "Param: '" . $key . "' is not allowed."));  
				}
				
			}
			
			if (count($update_values) <> count($this->GetPublicVars())) {
				http_response_code(400);        
				return json_encode(array("message" => "Incorrect param count")); 
			}
			
			$update_string .= implode(", ", $update_params) . $update_tail;
			
			$st = $this->connection->prepare($update_string);
			$st->execute($update_values);
			
			if ($st->rowCount() > 0) {
				http_response_code(200);        
				return json_encode(array("message" => get_class($unit) . " was updated."));	
			}
			else {
				http_response_code(400);        
				return json_encode(array("message" => get_class($unit) . " update failed"));    
			}
			
		}
	}
	
	function Patch($json) {
		$id = $json[$this->primary_key];
		if ($this->Validate($id, $this->primary_key)) {
		
			$param = $json["target"];
			$new_value = $json["new_value"];
			
			if (!in_array($param, $this->GetPublicVars())) {
				http_response_code(400);        
				return json_encode(array("message" => "Unknown parameter."));
			} else if (!$this->Validate($new_value, $param)) {
				http_response_code(400);        
				return json_encode(array("message" => "Incorrect value type."));
			}
			
			$patch_string = 
			"UPDATE $this->table_name 
				SET " . $json["target"] . " = :new_value
			 WHERE unique_id = :unique_id";
			
			$patch_array = array(':new_value' => $json["new_value"],
								 ':unique_id' => $id);

			$st = $this->connection->prepare($patch_string);
			$st->execute($patch_array);
			if ($st->rowCount() > 0) {
				http_response_code(200);        
				return json_encode(array("message" => get_class($unit) . ", param was updated."));	
			}
			else {
				http_response_code(400);        
				return json_encode(array("message" => get_class($unit) . ", param update failed"));    
			}
			
		}
		
	}
	
	function Delete($json) {
		
		$id = $json[$this->primary_key];
		if ($this->Validate($id, $this->primary_key)) {
			
			$delete_string = 
			"DELETE FROM $this->table_name 
			 WHERE $this->primary_key = :primary_key";
			 
			$delete_array = array(':primary_key' => $id);
			$st = $this->connection->prepare($delete_string);
			$st->execute($delete_array);
			
			if ($st->rowCount() > 0) {
				http_response_code(200);
				return json_encode(array("message" => get_class($unit) . ", was deleted successfully."));	
			}
			else {
				http_response_code(400);
				return json_encode(array("message" => get_class($unit) . ", delete failed."));    
			}
			
		}
	}
	
	function Validate($var, $param) {
		if (gettype($var) === $this->public_vars[$param]["type"] && strlen($var) <= $this->public_vars[$param]["length"]) {
			return true;
		} else {
			return false;
		}
	}
	
	function MySqlTypeToPHPType($type) {
		$type = explode("(", rtrim($type, ")"));
		
		switch ($type[0]) {
			case "varchar":
				$type[0] = "string";
				break;
			case "float":
				$type[0] = "double";
				break;
			case "datetime":
				$type[0] = "string";
				break;
			case "int":
				$type[0] = "integer";
				break;
		}
		
		if (count($type) === 1) {
			$type[1] = null;
		}
		
		return $type;
	}
	
	function GetPublicVars() {
		return array_keys($this->public_vars);
	}
}
?>