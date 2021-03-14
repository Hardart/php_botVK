<?php
require_once 'connect/db.php';
require_once 'config.php';
require_once 'functions.php';
require_once 'keyboards.php';

$img_1 = BASE_DIR . '/img/scale/scale_6.JPG';
$img_2 = BASE_DIR . '/img/resume/res_5.1.JPG';
$photo_1 = uploadPhoto(9128124, $img_1); //прикрепленное фото
$photo_2 = uploadPhoto(9128124, $img_2); //прикрепленное фото
$photos = [$photo_1, $photo_2];
$img = BASE_DIR . '/img/scale/scale_1.JPG';

function sendMessage1($peer_id, $message, $attach = array(), $kbd)
{
	return _vkApi_call(SEND_MESSAGE, array(
		'peer_id'    => $peer_id,
		'message'    => $message,
		'attachment' => implode(',', $attach),
		'keyboard' 	 => $kbd,
		'random_id'  => '0'
	));
}
echo $attach;
sendMessage1(9128124, $mess, $photos, keyboard($emptyKbd));
