<?php
require_once '../include/DB_Functions.php';
$db = new DB_Functions(); 

$response = array();

if (isset($_POST['token']) && isset($_POST['lat']) && isset($_POST['lng']) && isset($_POST['order_unique_id'])) {	
	$token = $_POST['token']; 
	$lat = $_POST['lat']; 
	$lng = $_POST['lng']; 
	$order_unique_id = $_POST['order_unique_id']; 
    $db->locationUpdate($token, $lat, $lng);	
	
	$order = $db->getOrderLocation($token, $order_unique_id);
	if ($order != false) {			
		$response["error"] = FALSE;					
		$response["order"] = $order;	
		echo json_encode($response);		
	} else {	
		$response["error"] = TRUE;		
		$response["error_msg"] = "Proses gagal!";
		echo json_encode($response);
	}    
} else {
	$response["error"] = TRUE;			
	$response["error_msg"] = "Parameter yang dibutuhkan tidak ada!";
	echo json_encode($response);
}

?>