<?php

try {
	$dbh = new PDO('mysql:dbname=h911249946_test; host=h911249946.mysql;charset=utf8', 'h911249946_hard', 'qaZ134679'); // подключаемся
	$dbh->exec('SET CHARACTER SET utf8');
} catch (PDOException $e) {
    echo 'Подключение не удалось: ' . $e->getMessage();
}
?>