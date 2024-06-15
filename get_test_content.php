<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест</title>
    <style>
        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 800px;
            width: 100%;
            margin: 20px;
            user-select: none;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
            user-select: none;
        }

        p {
            color: #666;
            line-height: 1.6;
            user-select: none;
        }

        form {
            margin-top: 20px;
        }

        input[type="radio"] {
            margin-right: 10px;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .no-results, .error {
            color: #d9534f;
            font-weight: bold;
            margin-top: 20px;
        }

        .check-button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .check-button:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        // Заборона натискання клавіші F12
        document.addEventListener('keydown', function(event) {
            if (event.key === 'F12' || (event.ctrlKey && event.shiftKey && event.key === 'I')) {
                event.preventDefault();
            }
        });

        // Заборона виділення тексту за допомогою JavaScript
        document.addEventListener('selectstart', function(event) {
            event.preventDefault();
        });

        // Заборона використання правої кнопки миші
        document.addEventListener('contextmenu', function(event) {
            event.preventDefault();
        });
    </script>
</head>
<body>
    <div class="container">
        <?php
        session_start();

        // Встановлення з'єднання з базою даних
        $servername = "localhost";
        $username = "root";
        $password = "bogdan09";
        $dbname = "library";

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Перевірка та встановлення значення test_id у сесії
            if (isset($_GET['test_id'])) {
                $_SESSION['test_id'] = $_GET['test_id'];
            }

            // Отримання значення test_id з сесії
            if (isset($_SESSION['test_id'])) {
                $test_id = $_SESSION['test_id'];

                // Отримання інформації про користувача з бази даних
                $stmt_user = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
                $stmt_user->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt_user->execute();
                $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

                // Отримання тестів з бази даних
                $sql_tests = "SELECT * FROM Tests WHERE id = :test_id";
                $stmt_tests = $conn->prepare($sql_tests);
                $stmt_tests->bindParam(':test_id', $test_id, PDO::PARAM_INT);
                $stmt_tests->execute();

                if ($stmt_tests->rowCount() > 0) {
                    // Відображення тестів та форми для завершення тесту
                    while ($row_test = $stmt_tests->fetch(PDO::FETCH_ASSOC)) {
                        echo "<h2>Тема тесту: " . $row_test["test_topic"] . "</h2>";
                        echo "<p><strong>Дедлайн:</strong> " . $row_test["submission_deadline"] . "</p>";

                        // Вибірка питань, пов'язаних з поточним тестом
                        $sql_questions = "SELECT * FROM Questions WHERE test_id = :test_id";
                        $stmt_questions = $conn->prepare($sql_questions);
                        $stmt_questions->bindParam(':test_id', $test_id, PDO::PARAM_INT);
                        $stmt_questions->execute();

                        if ($stmt_questions->rowCount() > 0) {
                            echo "<form action='test_result.php' method='post'>";
                            // Відображення питань та варіантів відповідей
                            while ($row_question = $stmt_questions->fetch(PDO::FETCH_ASSOC)) {
                                echo "<p>" . $row_question["question_text"] . "</p>";

                                $question_id = $row_question["id"];
                                $sql_answers = "SELECT * FROM Answers WHERE question_id = :question_id";
                                $stmt_answers = $conn->prepare($sql_answers);
                                $stmt_answers->bindParam(':question_id', $question_id, PDO::PARAM_INT);
                                $stmt_answers->execute();

                                if ($stmt_answers->rowCount() > 0) {
                                    while ($row_answers = $stmt_answers->fetch(PDO::FETCH_ASSOC)) {
                                        // Відображення варіантів відповідей
                                        echo "<input type='radio' name='question_" . $question_id . "' value='" . $row_answers["id"] . "'>" . $row_answers["answer_text"] . "<br>";
                                    }
                                } else {
                                    echo "<p>Немає варіантів відповідей для питання ID: " . $question_id . "</p>";
                                }
                            }
                            if ($user['status'] == 'student') {
                            echo "<input type='submit' value='Завершити тест'>";
                            }
                            echo "</form>";
                        } else {
                            echo "<p class='no-results'>Немає питань для тесту ID: " . $test_id . "</p>";
                        }
                    }

                    // Відображення кнопки для перевірки виконання тесту, якщо користувач - викладач
                    if ($user['status'] == 'teacher') {
                        echo "<button class='check-button' onclick=\"window.location.href='check_test.php?test_id=" . $test_id . "'\">Перевірити виконання тесту</button>";
                    }
                } else {
                    echo "<p class='no-results'>0 результатів для обраного тесту</p>";
                }
            } else {
                echo "<p class='error'>Не вдалося отримати значення test_id з сесії</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Помилка підключення до бази даних: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
</body>
</html>
