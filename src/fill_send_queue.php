<?php
namespace App;
use PDO;

/**
 * Скрипт заполнения очереди на отправку email-уведомлений
 * @var $pdo PDO
 */
require 'db.php';

echo "Заполняем очередь на отправку уведомлений.\n";

// Инициализация счетчиков
$added_count_1_day = 0;
$added_count_3_days = 0;

// Запрос для добавления пользователей, чья подписка истекает через 1 день
$query = "
INSERT INTO email_send_queue (user_id, email, username)
SELECT id, email, username
FROM users
WHERE confirmed = 1
  AND checked = 1
  AND valid = 1
  AND validts BETWEEN UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 1 DAY)) - 60
                  AND UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 1 DAY))
  AND NOT EXISTS (
    SELECT 1 
    FROM email_send_queue 
    WHERE user_id = users.id 
      AND validts BETWEEN UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 1 DAY)) - 60 
                      AND UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 1 DAY))
  );
";

// Выполнение запроса для подписки через 1 день
$added_count_1_day = $pdo->exec($query);

// Запрос для добавления пользователей, чья подписка истекает через 3 дня
$query3days = "
INSERT INTO email_send_queue (user_id, email, username)
SELECT id, email, username
FROM users
WHERE confirmed = 1
  AND checked = 1
  AND valid = 1
  AND validts BETWEEN UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 3 DAY)) - 60
                  AND UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 3 DAY))
  AND NOT EXISTS (
    SELECT 1 
    FROM email_send_queue 
    WHERE user_id = users.id 
      AND validts BETWEEN UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 3 DAY)) - 60 
                      AND UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 3 DAY))
  );
";

// Выполнение запроса для подписки через 3 дня
$added_count_3_days = $pdo->exec($query3days);

echo "Очередь на отправку уведомлений обновлена.\n";
echo "$added_count_1_day записей добавлено для подписки через 1 день.\n";
echo "$added_count_3_days записей добавлено для подписки через 3 дня.\n";
