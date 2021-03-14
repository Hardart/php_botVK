<?php

function Send($paramsArray)
{
	$url = 'http://194.58.120.177/post';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// указываем, что у нас POST запрос
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramsArray)); // добавляем переменные
	$output = curl_exec($ch);
	echo $output;
	curl_close($ch);
}

function Message($title, $user_id, $ps, $sp)
{  // функция отправки сообщени
	$paramsArray = [
		'USER_NAME' => $user_id,
		'qt' => $title,
		'ps' =>  $ps,
		'sp' =>  $sp,
	];
	Send($paramsArray);
}
$test = 'ИТ Доп. Процедуры 5.4';
$studentLogin = 'train_CCuser2';
$passing_score = '80';
$points = '80';

Message($test, $studentLogin, $passing_score, $points);
