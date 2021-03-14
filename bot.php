		<?php
		require_once 'connect/db.php';
		require_once 'config.php';
		require_once 'functions.php';
		require_once 'keyboards.php';
		require_once 'emodji.php';

		if (!isset($_REQUEST)) {
			return;
		}

		$data = json_decode(file_get_contents('php://input'));


		switch ($data->type) {
			case 'confirmation': // подтверждение ключа
				echo CONFIRMATION_TOKEN;
				break;

			case 'message_new': // новое сообщение
				$group_id = $data->group_id;
				$user_id = $data->object->message->from_id;
				$user_msg_id = $data->object->message->id;
				$user_pay = $data->object->message->payload;
				$user_pay = json_decode($user_pay);
				$user_msg = trim($data->object->message->text);
				$user_msg = mb_strtolower($user_msg);
				$user_info = getUsers($user_id);
				$user = array_pop($user_info);
				$user_name = $user['first_name'];
				$user_lastname = $user['last_name'];

				$stmt = select_FROM_WHERE($dbh, 'coaches', 'vk_id', $user_id);
				$coach = $stmt->fetch();
				if ($coach) { // ветка тренера
					if (!$user_pay) {
						switch ($user_msg) {
							case 'меню':
								$msg = 'Выбирай';
								$kbd = keyboard($students);
								break;
							default:
								$msg = "Я знаю тебя... ты " . $coach['name'] . "!!!\nНапиши - 'меню' и все будет ОК!";
								$kbd = keyboard($emptyKbd);
								break;
						}
					}
					switch ($user_pay->payload) {
						case 'Yes':
							$msg = 'Список очищен!';
							delete_FROM($dbh, STUDENTS, 'coach', $coach['id']);
							$stmt = select_FROM_WHERE($dbh, 'bonus_points', 'coach_id', $coach['id']);
							$bonus_points = $stmt->fetch();
							if ($bonus_points) {
								delete_FROM($dbh, 'bonus_points', 'coach_id', $coach['id']);
								$msg .= "\nБонусные коды очищены";
							}

							$file = BASE_DIR . '/tests/' . $coach['name'] . '.json';
							if (file_exists($file)) {
								unlink($file);
							}
							break;
						case 'No':
							$msg = 'Выберите действие';
							break;
						case 'coach':
							$stmt = select_FROM_WHERE2($dbh, STUDENTS, 'ready', true, 'coach', $coach['id']);
							while ($user = $stmt->fetch()) {
								update_FROM_WHERE($dbh, STUDENTS, 'ren_login', $user['ren_login'], 'ready', 0);
								update_FROM_WHERE($dbh, STUDENTS, 'ren_login', $user['ren_login'], 'coach', $user_pay->coach_id);
								$ren_login = $user['ren_login'];
							}
							$query = select_FROM_WHERE($dbh, 'coaches', 'id', $user_pay->coach_id);
							$new_coach = $query->fetch();
							change_coach($coach['name'], $new_coach['name'], $ren_login);
							$msg = "Готово";
							break;
					}
					$kbd = keyboard($students);
					switch ($user_pay) {
						case 'new_pass':
							$msg = gen_password();
							$kbd = keyboard($students);
							$sql = 'INSERT INTO bonus_points(code, coach_id) values(?,?)';
							$dbh->prepare($sql)->execute([$msg, $coach['id']]);
							break;
						case 'Delete':
							$msg = 'Вы уверены?';
							$kbd = keyboard($confirmBtns);
							break;
						case 'Send':
							$stmt = select_FROM_WHERE_with_ORDER($dbh, STUDENTS, 'coach', $coach['id'], 'ren_login');
							$top_line = "-------------------------------\n";
							$bottom_line = "\n-------------------------------\n";
							$users = [];
							$i = 0;
							while ($user = $stmt->fetch()) {
								$names .= $user['full_name'] . ' - ' . $user['ren_login'] . $bottom_line;
								$btn_text = preg_replace('/[^\d]/', '', $user['ren_login']);
								array_push($users, button($btn_text, 'student', 'primary'));
								$i++;
							}
							$msg = "КОГО НЕОБХОДИМО ПЕРЕМЕСТИТЬ?\n";
							$msg .= $top_line . $names;


							$col = 2;
							if ($i % 4 == 0) {
								$col = 4;
							} elseif ($i % 3 == 0 && $i > 8) {
								$col = 3;
							} elseif ($i > 10) {
								$col = 3;
							}
							$padavans = new_keyboard($users, $col);
							$kbd = keyboard($padavans);
							if (!$names) {
								$msg = "Перемещать некого";
								$kbd = keyboard($students);
							}
							break;
						case 'student':
							$msg = 'какому тренеру отправить?';
							update_FROM_WHERE($dbh, STUDENTS, 'ren_login', "train_CCuser" . $user_msg, 'ready', true);
							$kbd = keyboard($coaches);
							break;
						case 'standings':
							$stmt = select_FROM_WHERE_with_ORDER($dbh, STUDENTS, 'coach', $coach['id'], 'test_points', 'DESC');
							$top_line = "-------------------------------\n";
							$bottom_line = "\n-------------------------------\n";
							$i = 1;
							while ($user = $stmt->fetch()) {
								$points = $user['test_points'];
								$names .= $i . " - " . $user['full_name'] . ' - ' . $points . endings($points, [' балл', ' балла', ' баллов']) . $bottom_line;
								$i++;
							}
							$msg = $top_line . $names;
							if (!$names) {
								$msg = "В таблице пусто";
							}
							$kbd = keyboard($students);
							break;
					}
					sendMessage($user_id, $msg, NULL, $kbd);
				} else { // ветка студента
					$stmt = select_FROM_WHERE($dbh, STUDENTS, 'vk_id', $user_id);
					$student = $stmt->fetch();
					$coach_id = $student['coach'];
					if ($student) { // если студент уже в базе
						if ($user_pay) { // нажали кнопку
							switch ($user_pay->payload) {
								case 'coach':
									update_FROM_WHERE($dbh, STUDENTS, 'vk_id', $user_id, 'coach', $user_pay->coach_id);
									$query = select_FROM_WHERE($dbh, 'coaches', 'id', $user_pay->coach_id);
									$coach = $query->fetch();
									$msg = 'Теперь ' . $coach['name'] . ' ваш командир';
									sendMessage($user_id, $msg, NULL, keyboard($emptyKbd));
									$msg = 'Вы уверены в своем выборе?';
									$kbd = keyboard($confirmBtns);
									break;
								case 'Yes':
									//уведомление для тренера
									$query = select_FROM_WHERE($dbh, 'coaches', 'id', $coach_id);
									$coach = $query->fetch();
									$fwd = [
										'peer_id' 		=> $user_id,
										'message_ids'	=> [$user_msg_id]
									];
									$msg = "У тебя в команде пополнение\nСсылка на диалог: https://vk.com/gim" . $group_id . "?sel=" . $user_id;
									messageReply($coach['vk_id'], $msg, $fwd, keyboard($emptyKbd));

									$msg = "Отлично!\n\nВ процессе тебе предстоит пролететь на ракете " . _three . " планеты:\n\n" . _one . " планета - ПРОДУКТЫ. Здесь ты узнаешь те продукты, которые продаются в нашем космическом пространстве и их основные параметры.\n" . _two . " планета - КОММУНИКАТИВ. На этой горячей планете тебя ждут основы общения с клиентом и то, что нужно использовать для наиболее эффективной продажи.\n" . _three . " планета - ПРОГРАММЫ. В гостях у этой планеты ты узнаешь на какую кнопку нужно нажать, чтобы узнать решение банка и записать клиента в офис.\n\nКак твой настрой?\nГотов к незабываемым приключениям?";
									$kbd = keyboard($welcomeBtns);
									break;
								case 'No':
									$msg = 'Ты можешь иземенить свое решение...';
									$kbd = keyboard($coaches);
									break;
							}
							switch ($user_pay) {
								case 'ready':
									$msg = "Сейчас сгенерирую для тебя учётную запись и добавлю в свою команду";
									sendMessage($user_id, $msg, NULL, keyboard($emptyKbd));
									$msg = "==========================\r\nЛОГИН: " . $student['ren_login'] . "\r\nПАРОЛЬ: " . $student['ren_pass'] .  "\r\n==========================\n\n\nhttp://webtutor.rencredit.ru";
									$kbd = keyboard($menuBtns);
									sendMessage($user_id, $msg, NULL, $kbd);
									$msg = "Удачного пути " . _rocket;
									sendMessage($user_id, $msg, NULL, $kbd);
									$msg = "Если что, я всегда рядом и жду твоей команды " . _robo;
									break;
								case 'question':
									update_FROM_WHERE($dbh, 'padavans', 'vk_id', $user_id, 'question', 1);
									$msg = 'Напиши свой вопрос и тренер в скором времени ответит';
									$kbd = keyboard($menuBtns);
									break;
								case 'standings':
									$stmt = select_FROM_WHERE_with_ORDER($dbh, STUDENTS, 'coach', $coach_id, 'test_points', 'DESC');
									$msg = "----------------------------------\n";
									$i = 1;
									while ($user = $stmt->fetch()) {
										$points = $user['test_points'];
										$msg .= $i . " - " . $user['full_name'] . ' - ' . $points . endings($points, [' балл', ' балла', ' баллов']) . "\n----------------------------------\n";
										$i++;
									}
									$kbd = keyboard($menuBtns);
									break;
							}
							sendMessage($user_id, $msg, NULL, $kbd);
						} else { // пришло сообщение
							switch ($user_msg) {
								case 'меню':
									$msg = 'Ты снова можешь выбрать свою судьбу';
									$kbd = keyboard($menuBtns);
									break;
								case substr($user_msg, -4, 4) == '_ren' && substr($user_msg, 0, 3) == 'cc_':
									$stmt = select_FROM_WHERE($dbh, 'bonus_points', 'code', $user_msg);
									$bonus = $stmt->fetch();
									$kbd = keyboard($menuBtns);
									if ($bonus) {
										$points = $student['test_points'];
										$points += 2;
										update_FROM_WHERE($dbh, STUDENTS, 'vk_id', $user_id, 'test_points', $points);
										delete_FROM($dbh, 'bonus_points', 'code', $user_msg);
										$msg = "Поздравляю! " . _cracker . "\nТебе начислили бонусных баллов";
									} else {
										$msg = _confusedFaceWithHand . " Кто-то уже воспользовался этим кодом\nлибо он просто недействителен";
									}
									break;
								default:
									if ($student['question']) { // если был задан вопрос
										$kbd = keyboard($emptyKbd);
										$query = select_FROM_WHERE($dbh, 'coaches', 'id', $student['coach']);
										$coach = $query->fetch();
										$fwd = [
											'peer_id' 		=> $user_id,
											'message_ids'	=> [$user_msg_id]
										];
										$msg = "Вопрос!\nСсылка: https://vk.com/gim" . $group_id . "?sel=" . $user_id;
										messageReply($coach['vk_id'], $msg, $fwd, $kbd);
										update_FROM_WHERE($dbh, STUDENTS, 'vk_id', $user_id, 'question', 0);
										$msg = 'Данный вопрос требует уточнения, скоро вернусь с ответом';
									} else { //ответ на неизвестные команды ученика
										$msg = $user_name . ", прости, но я не понимаю что я должен сделать " . _sad . "\nВозвращаю тебя в главное меню";
										$kbd = keyboard($menuBtns);
									}
									break;
							}
							sendMessage($user_id, $msg, NULL, $kbd);
						}
					} else { // если в базе студента нет проверяем welcome-code
						$stmt = select_FROM_WHERE($dbh, 'reg_data', 'welcome_code', $user_msg);
						$reg_data = $stmt->fetch();
						$ren_login = $reg_data['ren_login'];
						$ren_pass =  $reg_data['ren_pass'];
						$wel_code = $reg_data['welcome_code'];
						if ($reg_data) { // если код верный добавляем студента в базу
							$query = select_FROM_WHERE($dbh, STUDENTS, 'welcome_code', $user_msg);
							$code = $query->fetch();
							if (!$code) {
								$sql = 'INSERT INTO padavans(vk_id, full_name, ren_login, ren_pass, welcome_code) values(?, ?, ?, ?, ?)';
								$query = $dbh->prepare($sql);
								$query->execute([$user_id, $user_lastname . ' ' . $user_name, $ren_login, $ren_pass, $wel_code]);
								$msg = "Дорогой друг, мы рады приветствовать тебя на дистанционно-очном Базовом обучении по Продажам." . _flyMoney . "\n\nМеня зовут Робби" . _robo . ". Я буду тебя сопровождать на протяжении всего космического путешествия по обучению.\n\nУточни, пожалуйста, кто твой командир шатла?" . _rocket;
								$kbd = keyboard($coaches);
							} else {
								$msg = "Тревога!!!" . _brickSign . " Этот код уже есть в базе!\nСообщи о проблеме в общий чат";
								$kbd = keyboard($emptyKbd);
							}
							sendMessage($user_id, $msg, NULL, $kbd);
						} else { // если код не верный отправляем сообщение
							$msg = "Упсс..." . _ooops . " Твой WELCOME-КОД не прошёл проверку. Проверь его, пожалуйста, и введи заново";
							sendMessage($user_id, $msg, NULL, keyboard($emptyKbd));
						}
					}
				}
				echo ('ok');
				break;
			case 'message_reply': //сообщение от тренера после заданного вопроса
				$admin = $data->object->admin_author_id;
				$user_id = $data->object->peer_id;
				if ($admin) {
					$stmt = select_FROM_WHERE($dbh, STUDENTS, 'vk_id', $user_id);
					$student = $stmt->fetch();
					$msg = "Если что, я всегда рядом и жду твоей команды " . _robo;
					sendMessage($user_id, $msg, NULL, keyboard($menuBtns));
				}
				echo ('ok');
				break;
		}
