<?php

function endings($n, $text_forms) // проверка окончаний
{
	$n = abs($n) % 100;
	$n1 = $n % 10;
	if ($n > 10 && $n < 20) {
		return $text_forms[2];
	}
	if ($n1 > 1 && $n1 < 5) {
		return $text_forms[1];
	}
	if ($n1 == 1) {
		return $text_forms[0];
	}
	return $text_forms[2];
}

function gen_password($length = 6) // генерация бонуса
{
	$chars = "0123456789abcdefghijklmnopqrstuvwxyz*^@!ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789+-";
	return 'CC_' . substr(str_shuffle($chars), 0, $length) . '_ren';
}

function photo_array() // создание массива случайных фото
{
	$path = scandir("img/fail");
	$images = array();
	foreach ($path as $i) {
		array_push($images, $i);
	}
	array_shift($images);
	array_shift($images);
	shuffle($images);
	shuffle($images);
	return $images;
}

function findSymbol($string, $symbolNum) // ищем символ в строке
{
	$data = iconv('UTF-8', 'windows-1251', $string); //Меняем кодировку на windows-1251
	$data = $data[$symbolNum]; //Получаем требуемый символ строки
	$letter = iconv('windows-1251', 'UTF-8', $data); //Меняем кодировку на windows-1251
	return $letter;
}

function change_coach($old_coach, $new_coach, $ren_login)
{
	$original_file = BASE_DIR . '/tests/' . $old_coach . '.json';
	$new_file = BASE_DIR . '/tests/' . $new_coach . '.json';
	if (file_exists($original_file)) { // если файл есть на сервере
		$content = json_decode(file_get_contents($original_file));
	} else { // если файла нет
		$content = (object)[];
	}
	if (file_exists($new_file)) { // если файл есть на сервере
		$new_content = json_decode(file_get_contents($new_file));
	} else { // если файла нет
		$new_content = (object)[];
	}
	$user = $content->$ren_login;
	if ($user) {
		unset($content->$ren_login);
		$new_content->$ren_login = $user;
		$data = json_encode($content, JSON_UNESCAPED_UNICODE);
		file_put_contents($original_file, $data);
		$data = json_encode($new_content, JSON_UNESCAPED_UNICODE);
		file_put_contents($new_file, $data);
	} else {
		_log_write('Студент с логином - ' . $ren_login . ' отсутвует в списке у тренера - ' . $old_coach . ' или, до этого момента, он не проходил ни одного теста');
	}
}

function log_msg($message)
{
	$message = json_encode($message, JSON_UNESCAPED_UNICODE);
	_log_write('[INFO] ' . $message);
}

function _log_write($message)
{
	$mark = date("H:i:s");
	$log_name = BASE_DIR . "/LOGS/" . date("j.n.Y") . '.txt';
	file_put_contents($log_name, $mark . " : " . $message . "\n", FILE_APPEND);
}

function uploadPhoto($user_id, $file_name)
{
	$user_getServer = getMessagesUpload($user_id);
	$result = vkApi_upload($user_getServer['upload_url'], $file_name);
	$photo = $result['photo'];
	$server = $result['server'];
	$hash = $result['hash'];

	$save_response = saveMessagesPhoto($photo, $server, $hash);
	$photo = array_pop($save_response);
	$attach = 'photo' . $photo['owner_id'] . '_' . $photo['id'];
	return $attach;
}

function vkApi_upload($url, $file_name)
{
	if (!file_exists($file_name)) {
		throw new Exception('File not found: ' . $file_name);
	}

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile($file_name)));
	$json = curl_exec($curl);
	$error = curl_error($curl);
	if ($error) {
		log_msg($error);
		throw new Exception("Failed {$url} request");
	}

	curl_close($curl);

	$response = json_decode($json, true);
	if (!$response) {
		throw new Exception("Invalid response for {$url} request");
	}

	return $response;
}

function getMessagesUpload($peer_id)
{
	return _vkApi_call(GET_PHOTOS, array(
		'peer_id' => $peer_id,
	));
}

function saveMessagesPhoto($photo, $server, $hash)
{
	return _vkApi_call(SAVE_PHOTOS, array(
		'photo'  => $photo,
		'server' => $server,
		'hash'   => $hash,
	));
}

function getUsers($user_id)
{
	return _vkApi_call(GET_USERS, array(
		'user_ids' => $user_id,
	));
}

function messageReply($peer_id, $message, $forward, $kbd)
{
	return _vkApi_call(SEND_MESSAGE, array(
		'peer_id'    => $peer_id,
		'message'    => $message,
		'forward'    => json_encode($forward),
		'keyboard' 	 => $kbd,
		'random_id'  => '0'
	));
}

function sendMessage($peer_id, $message, $attach, $kbd)
{
	return _vkApi_call(SEND_MESSAGE, array(
		'peer_id'    => $peer_id,
		'message'    => $message,
		'attachment' => $attach,
		'keyboard' 	 => $kbd,
		'random_id'  => '0'
	));
}

function _vkApi_call($method, $params = array())
{
	$params['access_token'] = ACCESS_TOKEN;
	$params['v'] = VK_V;

	$query = http_build_query($params);
	$url = file_get_contents(VK_API_URL . $method . '?' . $query);

	$response = json_decode($url, true);
	if (!$response || !isset($response['response'])) {
		log_msg($url);
		throw new Exception("Invalid response for {$method} request");
	}

	return $response['response'];
}

//запросы к базе данных
function select_FROM($db, $table)
{
	$data = $db->query("SELECT * FROM $table");
	return $data;
}

function delete_FROM($db, $table, $field, $value)
{
	$data = $db->prepare("DELETE FROM {$table} WHERE {$field} = ?");
	$data->execute([$value]);
}

function TRUNCATE_table($db, $table)
{
	$data = $db->prepare("TRUNCATE TABLE `$table`");
	$data->execute();
}

function select_FROM_WHERE($db, $table, $field, $value)
{
	$data = $db->prepare("SELECT * FROM {$table} WHERE {$field} = ?");
	$data->execute([$value]);
	return $data;
}

function select_FROM_WHERE2($db, $table, $field1, $value1, $field2, $value2)
{
	$data = $db->prepare("SELECT * FROM {$table} WHERE {$field1} = ? and {$field2} = ?");
	$data->execute([$value1, $value2]);
	return $data;
}

function select_FROM_WHERE_with_ORDER($db, $table, $field, $value, $order, $sortby = "ASC")
{
	$data = $db->prepare("SELECT * FROM {$table} WHERE {$field} = ? ORDER BY {$order} $sortby");
	$data->execute([$value]);
	return $data;
}

function update_FROM_WHERE($db, $table, $field, $value, $updateField, $updateValue)
{
	$data = $db->prepare("UPDATE `$table` SET `$updateField`=$updateValue WHERE `$field` = ?");
	$data->execute([$value]);
}

// клава и кнопки
function keyboard($kbd)
{
	$data = json_encode($kbd, JSON_UNESCAPED_UNICODE);
	return $data;
}

function button($label, $payload, $color)
{
	return [
		'action' => [
			'type' => 'text',
			'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
			'label' => $label
		],
		'color' => $color
	];
}

function new_keyboard($users, $columns = 2, $one_time = true)
{
	$btn = [];
	for ($i = 0; $i < 10; $i++) {
		$arr = [];
		for ($j = $i * $columns; $j < ($i + 1) * $columns; $j++) {
			if ($users[$j]) {
				array_push($arr, $users[$j]);
			}
		}
		if ($arr) {
			array_push($btn, $arr);
		}
	}
	return [
		'one_time' => $one_time,
		'buttons' => $btn
	];
}
