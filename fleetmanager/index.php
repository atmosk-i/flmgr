<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once "./config/error_reporting.php";
include_once "./config/database.php";
include_once "./objects/unit.php";

$request = getenv("REQUEST_METHOD");
$dir = explode("/", $_REQUEST['q']);

$unit_type = "_" . $dir[0];

$database = new Database();
$conn = $database->GetConnection();

if (!in_array($unit_type, GetPublicUnits($conn))) {
	http_response_code(400);
	echo json_encode(array("message" => "unknown unit type."));
	return;
}

$unit = new Unit($conn, $unit_type);

$json_arr = json_decode(file_get_contents("php://input"), true);

if ($json_arr) {

	switch ($request) {
	
		case "GET":
			
			switch ($dir[1]) {
					
				case "get-list-modified":
					$rs = $unit->QueryModified($json_arr);
					break;
					
			}
			
			break;
		
		case "POST":
			$rs = $unit->Insert($json_arr);
			break;
		
		case "PUT":
			$rs = $unit->Update($json_arr);
			break;
		
		case "PATCH":
			$rs = $unit->Patch($json_arr);
			break;
		
		case "DELETE":
			$rs = $unit->Delete($json_arr);
			break;
	}
	
	echo $rs;
	
} else if ($request === "GET") {
			
	switch ($dir[1]) {
		
		case "get-list-all":
			$rs = $unit->QueryAll();
			break;
			
		case "get-list-one":
			$rs = $unit->QueryOne();
			break;
			
	}
	
	echo $rs;
	
} else {	
	http_response_code(400);
	echo json_encode(array("message" => "Could not decode json string."));
	
}

function GetPublicUnits($conn) {
	
	$query_string = "SELECT table_name FROM information_schema.tables WHERE table_name LIKE '\_%'";
	$rs = $conn->prepare($query_string);
	$rs->execute();
	
	if ($rs->rowCount() <= 0) {	
		
		http_response_code(503);
		return json_encode(array("error:" => array("id" => "1", "msg" => "no units found from the database, run init_data.php")));
		
	} else {
		$output_array = [];	
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$output_array[] = $row["table_name"];
		}	
		return $output_array;
		
	}
}

?>