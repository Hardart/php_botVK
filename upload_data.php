<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
require_once 'connect/db.php';
require_once 'config.php';
require_once 'functions.php';
require_once BASE_DIR . '/parcer/SimpleXLSX.php';

$file_name = "accounts.xlsx";
$endpoint = "reg_data/" . $file_name;
// $upload_file = $_FILES['sheet']['tmp_name'];
if($_FILES){
    move_uploaded_file($_FILES['sheet']['tmp_name'], $endpoint);
}
if ($xlsx = SimpleXLSX::parse('reg_data/' . $file_name)) {
	$sheet = $xlsx->rows();
	array_shift($sheet);
	TRUNCATE_table($dbh, 'reg_data');
	foreach ($sheet as $row) {
		$sql = 'INSERT INTO reg_data(ren_login, ren_pass, welcome_code) values(?, ?, ?)';
		$query = $dbh->prepare($sql);
		$query->execute([$row[0], $row[1], $row[2]]);
	}
} else {
	log_msg(SimpleXLSX::parseError());
}
$result = [];
$user = [];

$stmt = select_FROM($dbh, 'reg_data');
$i = 0;
while ($data = $stmt->fetch()) {
	$user['login'] = $data['ren_login'];
	$user['password'] = $data['ren_pass'];
	$user['w_code'] = $data['welcome_code'];
	$result[$i] = $user;
	$i++;
}

$data = file_get_contents('php://input');
$pdvn = json_decode($data, true);
if ($pdvn || $_FILES) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    header('Location: index.php');
}

