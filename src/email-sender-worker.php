<?php
namespace App;
use React\EventLoop\Factory;
use React\MySQL\Factory as MySQLFactory;
use React\Promise\PromiseInterface;
use React\MySQL\QueryResult;

// Создаём цикл событий
$loop = Factory::create();

// Настройки базы данных
$mysql = new MySQLFactory($loop);
$connection = $mysql->createConnection('mysql://root:root@localhost/mail_sender'); // Настроить с правильными данными

// Заглушка асинхронной функции отправки email
function send_email(string $from, string $to, string $text): PromiseInterface {
    return new \React\Promise\Promise(function ($resolve) {
        $delay = rand(1, 10); // случайная задержка
        $timer = \React\EventLoop\Loop::get()->addTimer($delay, function () use ($resolve) {
            $resolve(true); // Завершаем промис, как если бы письмо было отправлено успешно
        });
    });
}

// Функция для обработки одной задачи
function process_task($connection, $taskId, $email, $username) {
    // Начинаем отправку email
    send_email("noreply@example.com", $email, "{$username}, your subscription is expiring soon")
        ->then(function () use ($connection, $taskId) {
            // После отправки, обновляем статус задачи в базе данных
            return $connection->query("UPDATE email_send_queue SET status = 'sent' WHERE id = ?", [$taskId]);
        })
        ->otherwise(function () use ($connection, $taskId) {
            // Если возникла ошибка, обновляем статус задачи как "failed"
            return $connection->query("UPDATE email_send_queue SET status = 'failed' WHERE id = ?", [$taskId]);
        })
        ->then(function (QueryResult $result) use ($taskId) {
            echo "Письмо отправлено для задачи ID {$taskId}\n";
        })
        ->otherwise(function (\Exception $e) {
            echo "Ошибка отправки для задачи: {$e->getMessage()}\n";
        });
}

// Функция для обработки очереди
function process_queue($connection) {
    $connection->query("SELECT id, email, username FROM email_send_queue WHERE status = 'pending' LIMIT 20 FOR UPDATE SKIP LOCKED")
        ->then(function (QueryResult $result) use ($connection) {
            // Проходим по всем выбранным задачам
            foreach ($result->resultRows as $row) {
                process_task($connection, $row['id'], $row['email'], $row['username']);
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
