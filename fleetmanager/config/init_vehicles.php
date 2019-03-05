<?php
include_once "../config/error_reporting.php";include_once "../config/database.php";

$database = new Database();
$conn = $database->GetConnection();

$table_name = 'vehicle';
$file_dir = "../data/vehicles.csv";

if ($conn->query("SELECT 1 'result' FROM information_schema.tables WHERE table_name = '" . $table_name . "'")->fetch()['result'] == 1) {
	echo("table exists");
	die();
}

else {
	try {
		$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );//Error Handling
		$create = "CREATE TABLE IF NOT EXISTS $table_name ( 
				unique_id int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,				manufacturer varchar(256) NOT NULL, 
				model varchar(256) NOT NULL, 
				model_year int(4) NOT NULL, 
				registration_number varchar(10) NOT NULL, 
				inspection_date datetime NOT NULL, 
				engine_size float NOT NULL, 
				engine_power double NOT NULL 
			) 
			ENGINE=InnoDB 
			DEFAULT CHARACTER SET utf8 
			AUTO_INCREMENT 19";
		$r = $conn->exec($create);
		
		print("Created Table.\n");		
		
	} catch (PDOException $e) {
		echo $e->getMessage();
	}	
	$insert_header = "INSERT INTO $table_name (manufacturer, model, model_year, registration_number, inspection_date, engine_size, engine_power) VALUES ";	
	$pointer = fopen($file_dir, "r");	
	if (!$pointer) {		
		echo "error opening file";	
	} else {		
		$header_skip = false;
		while (($row = fgets($pointer)) !== false) {
			if (!$header_skip) {				
				$header_skip = true;				
				continue;			
			}			
			$row = explode(",", $row);			
			$insert_tail = "('" . $row[46] . "', '"
								. $row[47] . "', '"
								. $row[63] . "', '"
								. GetRandomRegNumber("nnn-ccc")
								. "', CAST('" . GetRandomDateTime() . "' AS datetime), '"
								. floatval($row[23]) . "', '"
								. GetRandomEnginePower() . "')";
			echo $insert_header . $insert_tail . "<br>";
			$conn->exec($insert_header . $insert_tail);		
		}	
	}	
	fclose($pointer);
}
$database = null;

function GetRandomRegNumber($format) {	
	$format = str_split($format);
	$output = "";
	foreach ($format as $char) {
		if ($char == "n") {
			$output .= chr(rand(48,57));
		} else if ($char == "c") {
			$output .= chr(rand(65,90));		
		} else {
			$output .= $char;
		}
	}
	return $output;
}
function GetRandomEnginePower() {	
	return rand(500,4000)/10.0;
}
function GetRandomDateTime() {	
	return date("Y-m-d H:i:s", rand(1, time()));
}
?>

