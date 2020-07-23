<?php
/*if(rand(1,5)==2){
	$response['result'] = '-';
	$response['message'] = 'Informasi telah diperbaharui.';			
	echo json_encode($response);		
	exit;
}*/
if (isset($_POST['token'])) {
	 	
    $token = $_POST['token'];    
	require_once 'DB_Functions_Account.php';
	$db = new DB_Functions_Account($token); 
	
	if($db->checkLogin()){		
		if(isset($_POST['appuid'])){
			$appId = $db->getAppIdByViewUId($_POST['appuid']);
		} else {
			$appId = -1;
		}
		
		if (isset($_POST['driver_lat']) && isset($_POST['driver_lng'])){
			$account = $db->updateDriverLocation($_POST['driver_lat'], $_POST['driver_lng'], $appId);	
		} else {
			$account = $db->getDriverLocation($appId);	
		}
		$response['result'] = $account;
		$response['message'] = 'Informasi telah diperbaharui.';			
		echo json_encode($response);		
	} else {
		$response["error"] = TRUE;
		$response["error_msg"] = "User tidak ditemukan";
		echo json_encode($response);
	}
	
} else {
    $response["error"] = TRUE;
    $response["error_msg"] = "Parameter yang dibutuhkan tidak ada!";
    echo json_encode($response);
}
?>