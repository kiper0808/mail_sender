--liquibase formatted sql
--changeset kiper0808:KRM-001
CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       username VARCHAR(255),
                       email VARCHAR(255) UNIQUE,
                       validts INT,
                       confirmed TINYINT(1) DEFAULT 0,
                       checked TINYINT(1) DEFAULT 0,
                       valid TINYINT(1) DEFAULT 0
);

CREATE TABLE email_check_queue (
                                   id INT AUTO_INCREMENT PRIMARY KEY,
                                   user_id INT NOT NULL,
                                   email VARCHAR(255) NOT NULL,
                                   status ENUM('pending', 'processing', 'done', 'failed') DEFAULT 'pending',
                                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE email_send_queue (
                                  id INT AUTO_INCREMENT PRIMARY KEY,
                                  user_id INT NOT NULL,
                                  email VARCHAR(255) NOT NULL,
                                  username VARCHAR(255) NOT NULL,
                                  status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
                                  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--rollback drop table users;
--rollback drop table email_check_queue;
--rollback drop table email_send_queue;

--changeset kiper0808:KRM-002
INSERT INTO users (username, email, checked, valid, confirmed, validts) VALUES
                                                                            -- ✅ Валидные пользователи с подпиской, истекающей через 1 или 3 дня
                                                                            ('User1', 'user1@example.com', 1, 1, 1, UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 1 DAY))),
                                                                            ('User2', 'user2@example.com', 1, 1, 1, UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 3 DAY))),
                                                                            ('User3', 'user3@example.com', 1, 1, 1, UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 1 DAY))),
                                                                            ('User4', 'user4@example.com', 1, 1, 1, UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 3 DAY))),

                                                                            -- ❌ Невалидные email-адреса
                                                                            ('User5', 'user@.com', 0, 0, 0, 0),
                                                                            ('User6', 'user name@example.com', 0, 0, 0, 0),
                                                                            ('User7', 'user@@example.com', 0, 0, 0, 0),
                                                                            ('User8', 'user@exam_ple.com', 0, 0, 0, 0),
                                                                            ('User9', 'user@exa!mple.com', 0, 0, 0, 0),
                                                                            ('User10', 'user@examplecom', 0, 0, 0, 0),

                                                                            -- ❌ Пользователи без подписки (validts = 0)
                                                                            ('User11', 'user11@example.com', 1, 1, 1, 0),
                                                                            ('User12', 'user12@example.com', 1, 1, 1, 0),

                                                                            -- ❌ Пользователи с неподтвержденным email (confirmed = 0)
                                                                            ('User13', 'user13@example.com', 1, 1, 0, 0),
                                                                            ('User14', 'user14@example.com', 1, 1, 0, 0),

                                                                            -- ❌ Пользователи, у которых checked = 0 (не проверенные)
                                                                            ('User15', 'user15@example.com', 0, 0, 0, 0),
                                                                            ('User16', 'user16@example.com', 0, 0, 0, 0);

--rollback truncate users;

