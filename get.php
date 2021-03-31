<?php
require_once 'connect/db.php';
require_once 'config.php';
require_once 'functions.php';
require_once 'keyboards.php';
require_once 'emodji.php';
header('Access-Control-Allow-Origin: *');
if (!isset($_REQUEST)) {
	return;
}

//переменные iSpring
$username = $_POST['USER_NAME']; // логин студента
$quiz_title = $_POST['qt']; // название теста
$passing_score = $_POST['ps']; // проходной балл
$points = $_POST['sp']; // набранные очки

if ($username) { // переменная логин студента в POST запросе не пустая
	// шаблоны ответов на тесты
	$easy_fail = [
		"Не стоит беспокоиться, в следующий раз точно получится" . _happyFaceWithHands,
		"У тебя есть возможность ещё раз пройти тест и закрепить свои знания" . _strength,
		"Нажми на кнопку «Повторить» и пройди тест снова.\nЯ верю в тебя " . _holy
	];

	$easy_msg = [
		"Тадаааам " . _cracker . "Ты прошёл easy-тест! Лови 3 балла " . _robo,
		"Урааа, начало положено, вот твои 3 балла" . _thumbUp . "\nЖду результаты следующего теста?!" . _smirk,
		"Я вижу, что ты прошёл easy-тест" . _complite . "\nЭто говорит о том, что 3 балла падают в твою копилочку " . _moneyBag,
		"А ты целеустремленный ученик " . _shy . "\nТы победил easy-тест, 3 балла твои " . _handsUp
	];

	$medium_msg = [
		"Огого, что я вижу?! Ты прошёл medium-тест" . _monocle . "\nПрибавлю к твоему счету 4 балла" . _handsUp,
		"Хммм, мне кажется или ты прошёл medium-тест?! " . _thinking . "\nЗачисляю тебе 4 балла" . _ok
	];

	$hard_msg = [
		"Откуда у тебя столько энергии?! " . _smirk . "5 баллов от hard-теста у тебя в кармане " . _happyFaceWithHands,
		"Оказывается ты супер сильный космонавт и прошёл hard-овую проверку " . _strength . "5 баллов твои " . _wink
	];

	//проверка логина студента в базе
	$stmt = select_FROM_WHERE($dbh, STUDENTS, 'ren_login', $username);
	$student = $stmt->fetch();
	if ($student) { // если студент есть в базе
		$student_vk_id = $student['vk_id'];
		$student_points = $student['test_points'];
		$coach_id = $student['coach'];
		$stmt = select_FROM_WHERE($dbh, 'coaches', 'id', $coach_id);
		$coach = $stmt->fetch();
		$coach_name = $coach['name'];

		$letter = findSymbol($quiz_title, 0); // первая буква названия теста
		$digit = findSymbol($quiz_title, 3); // первая цифра в названии теста MEDIUM/HARD

		//запись в файл JSON результат теста
		$file = __DIR__ . '/tests/' . $coach_name . '.json';
		if (file_exists($file)) { // если файл уже есть на сервере
			$string = json_decode(file_get_contents($file));
		} else { // если файла нет
			$string = (object)[];
		}
		$test_complite = $string->$username->$quiz_title;
		$photo = $string->$username->photo;

		if ($points < $passing_score) { // если студент не прошел тест
			if ($letter != 'Д') { // EASY
				if (!$photo[0]) { //если массив пуст, заполняем его снова фотками
					$photo = photo_array();
				}
				$img = 'img/fail/' . $photo[0];
				$attach = uploadPhoto($student_vk_id, $img); //прикрепляем фото
				$mess = $easy_fail[rand(0, 2)];
				sendMessage($student_vk_id, $mess, $attach, keyboard($menuBtns));
				array_shift($photo);
				$string->$username->photo = $photo;
				$data = json_encode($string, JSON_UNESCAPED_UNICODE);
				file_put_contents($file, $data);
			} else { // MEDIUM/HARD
				$string->$username->$quiz_title = true;
				$data = json_encode($string, JSON_UNESCAPED_UNICODE);
				file_put_contents($file, $data);
			}
		} else { // если студент прошел тест
			// выставляем значение бонуса в зависимости от сложности теста
			$bonus = 3;
			if ($letter == "Д") {
				$bonus = 4;
				if ($digit == "2") {
					$bonus = 5;
				}
			}

			if ($test_complite && $letter != 'Д') { // если тест EASY был пройден положительно повторно
				$mess = "Ты уже проходил этот тест " . _wink . "\nПоэтому баллы не начислены.";
			} elseif ($test_complite && $letter == 'Д') { // если тест Medium/Hard был пройден повторно
				$mess = 'Была только одна поптытка получить больше!';
			} else { // если тест пройден начисляем бонусы
				$student_points += $bonus;
				update_FROM_WHERE($dbh, STUDENTS, 'vk_id', $student_vk_id, 'test_points', $student_points);
				$mess = $easy_msg[rand(0, 3)];
				if ($bonus == 4) {
					$mess = $medium_msg[rand(0, 1)];
				} elseif ($bonus == 5) {
					$mess = $hard_msg[rand(0, 1)];
				}
				$string->$username->$quiz_title = true;
				$data = json_encode($string, JSON_UNESCAPED_UNICODE);
				file_put_contents($file, $data);
			}
			sendMessage($student_vk_id, $mess, NULL, keyboard($menuBtns));
			if ($bonus == 3 && !$test_complite) { // если тест EASY пройден в первый раз через 5мин отправляем картинки
				sleep(300);
				switch ($quiz_title) {
					case str_contains($quiz_title, 'ИТ Продукты 1.3'):
						$img_1 = BASE_DIR . '/img/scale/scale_1.JPG';
						$img_2 = BASE_DIR . '/img/resume/res_1.JPG';
						$photo_1 = uploadPhoto($user_id, $img_1);
						$photo_2 = uploadPhoto($user_id, $img_2);

						$mess = "Хочу напомнить важную информацию";
						sendMessage($student_vk_id, $mess, $photo_1, keyboard($menuBtns));
						sendMessage($student_vk_id, "", $photo_2, keyboard($menuBtns));
						break;
					case str_contains($quiz_title, 'ИТ Минимальные требования 2.3'):
						$img_1 = BASE_DIR . '/img/scale/scale_2.JPG';
						$img_2 = BASE_DIR . '/img/resume/res_2.JPG';
						$photo_1 = uploadPhoto($user_id, $img_1);
						$photo_2 = uploadPhoto($user_id, $img_2);

						$mess = 'Напоминаю, что важно помнить.';
						sendMessage($student_vk_id, $mess, $photo_1, keyboard($menuBtns));
						sendMessage($student_vk_id, "", $photo_2, keyboard($menuBtns));
						break;
					case str_contains($quiz_title, 'ИТ Программы 3.4'):
						$img_1 = BASE_DIR . '/img/scale/scale_4.JPG';
						$img_2 = BASE_DIR . '/img/resume/res_3.1.JPG';
						$img_3 = BASE_DIR . '/img/resume/res_3.2.JPG';
						$photo_1 = uploadPhoto($user_id, $img_1);
						$photo_2 = uploadPhoto($user_id, $img_2);
						$photo_3 = uploadPhoto($user_id, $img_3);

						$mess = 'Я оставлю это здесь, а ты запомни.';
						sendMessage($student_vk_id, $mess, $photo_1, keyboard($menuBtns));
						sendMessage($student_vk_id, "", $photo_2, keyboard($menuBtns));
						sendMessage($student_vk_id, "", $photo_3, keyboard($menuBtns));
						break;
					case str_contains($quiz_title, 'ИТ Программа МСО 4.6'):
						$img_1 = BASE_DIR . '/img/scale/scale_5.JPG';
						$img_2 = BASE_DIR . '/img/resume/res_4.1.JPG';
						$img_3 = BASE_DIR . '/img/resume/res_4.2.JPG';
						$photo_1 = uploadPhoto($user_id, $img_1);
						$photo_2 = uploadPhoto($user_id, $img_2);
						$photo_3 = uploadPhoto($user_id, $img_3);
						$mess = 'Псс... Запомни это, пожалуйста.';
						sendMessage($student_vk_id, $mess, $photo_1, keyboard($menuBtns));
						sendMessage($student_vk_id, "", $photo_2, keyboard($menuBtns));
						sendMessage($student_vk_id, "", $photo_3, keyboard($menuBtns));
						break;
					case str_contains($quiz_title, 'ИТ Дополнительные процедуры 5.5'):
						$img_1 = BASE_DIR . '/img/scale/scale_6.JPG';
						$img_2 = BASE_DIR . '/img/resume/res_5.1.JPG';
						$img_3 = BASE_DIR . '/img/resume/res_5.2.JPG';
						$img_4 = BASE_DIR . '/img/scale/scale_7.JPG';
						$photo_1 = uploadPhoto($user_id, $img_1);
						$photo_2 = uploadPhoto($user_id, $img_2);
						$photo_3 = uploadPhoto($user_id, $img_3);
						$photo_4 = uploadPhoto($user_id, $img_4);

						$mess = 'Псс... Запомни это, пожалуйста.';
						sendMessage($student_vk_id, $mess, $photo_1, keyboard($menuBtns));
						sendMessage($student_vk_id, "", $photo_2, keyboard($menuBtns));
						sendMessage($student_vk_id, "", $photo_3, keyboard($menuBtns));
						sendMessage($student_vk_id, "", $photo_4, keyboard($menuBtns));

						$mess = "Итак, ты закончил все модули.\nИспользуй знания с умом и успехов тебе в работе!";
						sendMessage($student_vk_id, $mess, NULL, keyboard($menuBtns));
						break;

					default:
						$msg = "Похоже название теста изменилось...\nвот что мне пришло - " . $quiz_title;
						sendMessage('112069665', $msg, NULL, keyboard($emptyKbd));
						break;
				}
			}
		}
	} else { // если студента нет в базе
		$msg = "Какой-то дебил изменил логин после прохождения теста - " . $quiz_title . " на " . $username . " либо его просто нет в базе";
		sendMessage('112069665', $msg, NULL, keyboard($emptyKbd));
	}
}

echo ('ok');
