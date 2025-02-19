<?php
namespace App;
use PDO;

/**
 * Скрипт добавления email-адресов в очередь на проверку
 * @var $pdo PDO
 */
require 'db.php';

// Получаем список пользователей, чьи email не были проверены
$stmt = $pdo->query("SELECT id, username, email, validts, confirmed, checked, valid FROM users WHERE valid = 0 AND checked = 0");

$added_count = 0; // Счётчик добавленных записей

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $email = $row['email'];
    $user_id = $row['id'];

    // Проверяем, существует ли уже запись в очереди для данного пользователя и email
    $checkStmt = $pdo->prepare("SELECT 1 FROM email_check_queue WHERE user_id = ? AND email = ?");
    $checkStmt->execute([$user_id, $email]);

    // Если запись существует, пропускаем
    if ($checkStmt->fetch()) {
        continue; // Переходим к следующему пользователю
    }

    // Вставляем email в очередь на проверку, если записи нет
    $pdo->prepare("INSERT INTO email_check_queue (user_id, email, status) 
                   VALUES (?, ?, 'pending')")
        ->execute([$user_id, $email]);

    $added_count++; // Увеличиваем счётчик
}

echo "$added_count email добавлено в очередь на проверку.\n";
