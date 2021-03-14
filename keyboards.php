<?php

$students = [
	'one_time' => false,
	'buttons' => [
		[
			button('Сгенерировать бонус-код', 'new_pass', 'primary'),
			button('Турнирная таблица', 'standings', 'primary')
		],
		[
			button('Отправить к другому тренеру', 'Send', 'secondary')
		],
		[
			button('Очистить список учеников', 'Delete', 'negative')
		]
	]
];

$welcomeBtns = [
	'one_time' => true,
	'buttons' => [
		[
			button('Да, готов!', 'ready', 'positive')
		]
	]
];

$afterReply = [
	'one_time' => true,
	'buttons' => [
		[
			button('Да, готов!', 'ready', 'positive')
		],
		[
			button('Есть еще вопрос', 'question', 'negative')
		]
	]
];

$menuBtns = [
	'one_time' => false,
	'buttons' => [
		[
			button('Турнирная таблица', 'standings', 'primary')
		],
		[
			button('Задать вопрос тренеру', 'question', 'negative')
		]
	]
];

$confirmBtns = [
	'one_time' => true,
	'buttons' => [
		[
			button('Да', ['payload' => 'Yes', 'delete' => 'Yes'], 'positive'),
			button('Нет', ['payload' => 'No', 'delete' => 'No'], 'negative')
		]
	]
];

$coaches = [
	'one_time' => true,
	'buttons' => [
		[
			button('Ревина Анна', ['payload' => 'coach', 'coach_id' => '1'], 'positive'),
			button('Родионова Кристина', ['payload' => 'coach', 'coach_id' => '2'], 'positive')
		],
		[
			button('Дашкина Фатима', ['payload' => 'coach', 'coach_id' => '3'], 'positive'),
			button('Фомина Ольга', ['payload' => 'coach', 'coach_id' => '4'], 'positive')
		],
		[
			button('Шакирова Юлия', ['payload' => 'coach', 'coach_id' => '5'], 'positive'),
			button('Ширясова Валерия', ['payload' => 'coach', 'coach_id' => '6'], 'positive')
		],
		[
			button('Юдина Наталья', ['payload' => 'coach', 'coach_id' => '7'], 'positive')
		]
	]
];

$emptyKbd = [
	'one_time' => true,
	'buttons' => []
];
