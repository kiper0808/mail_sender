<?php
namespace App;

require __DIR__ . '/vendor/autoload.php';

$loop = Factory::create(); // Создаём цикл событий

// Настройки базы данных
$mysql = new MySQLFactory($loop);
$connection = $mysql->createConnection('mysql://root:root@localhost/mail_sender'); // Настроить с правильными данными

// Заглушка функции асинхронной проверки email с реальной задержкой
function check_email(string $email): PromiseInterface {
    // Симулируем асинхронную задержку в 1-60 секунд
    return \React\Promise\resolve()
        ->then(function () {
            // Задержка от 1 до 60 секунд
            return new \React\Promise\Promise(function ($resolve) {
                $delay = rand(1, 60);
                // Реальная асинхронная задержка
                $timer = \React\EventLoop\Loop::get()->addTimer($delay, function () use ($resolve) {
                    $resolve(true); // Завершаем промис, подразумевая что email валидный
                });
            });
        })
        ->then(function () use ($email) {
            // Простой паттерн для проверки формата email
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        });
}

// Функция для обработки одной задачи
function process_task($connection, $taskId, $userId, $email) {
    // Проверяем email с асинхронной задержкой
    check_email($email)
        ->then(function ($is_valid) use ($connection, $taskId, $userId) {
            // Обновляем пользователя в базе данных
            return $connection->query("UPDATE users SET checked = 1, valid = ? WHERE id = ?", [(int) $is_valid, $userId]);
        })
        ->then(function (QueryResult $result) use ($connection, $taskId) {
            // Обновляем статус задачи в очереди
            return $connection->query("UPDATE email_check_queue SET status = 'done' WHERE id = ?", [$taskId]);
        })
        ->then(function (QueryResult $result) use ($taskId) {
            echo "Email проверен для задачи ID {$taskId}\n";
        })
        ->otherwise(function (\Exception $e) {
            echo "Ошибка: {$e->getMessage()}\n";
        });
}

// Функция для обработки очереди
function process_queue($connection) {
    $connection->query("SELECT id, email, user_id FROM email_check_queue WHERE status = 'pending' LIMIT 10 FOR UPDATE SKIP LOCKED")
        ->then(function (QueryResult $result) use ($connection) {
            // Проходим по всем выбранным задачам
            foreach ($result->resultRows as $row) {
                process_task($connection, $row['id'], $row['user_id'], $row['email']);
            }

            // После завершения обработки, заново запускаем процесс
            process_queue($connection);
        })
        ->otherwise(function (\Exception $e) {
            echo "Ошибка при получении задач: {$e->getMessage()}\n";
        });
}

// Запуск обработки очереди
process_queue($connection);

// Слушаем цикл событий
$loop->run();
