<?php 
session_start();
//read https://www.geeksforgeeks.org/how-to-receive-json-post-with-php/
$json = file_get_contents('php://input'); // Takes raw data from the request as file
$data = json_decode($json); // Converts it into a PHP object

//Receive ajax call
if ($data && (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
	if ($data->random == $_SESSION["random"]) {
		$_SESSION["login_account"] = $data->account;
		$_SESSION["login_check"] = true;
		echo json_encode(
			array('account' => $data->account, 'success' => 1)
		);
	}
} else {
	echo json_encode(array('success' => 0));
}
?>
