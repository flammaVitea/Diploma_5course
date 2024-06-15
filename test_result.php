<!DOCTYPE html>
<html>
<head>
    <title>Результати тесту</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-family: Arial, sans-serif;
            margin: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h2 {
            color: #333;
            margin-top: 15%;
        }
        p {
            font-size: 18px;
            color: #555;
        }
        .result-message {
            font-size: 20px;
            font-weight: bold;
            color: #4CAF50;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            margin: 10px 5px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            outline: none;
            color: #fff;
            background-color: #4CAF50;
            border: none;
            border-radius: 15px;
            box-shadow: 0 9px #999;
            width: 25%;
        }
        .button:hover {
            background-color: #45a049;
        }
        .button:active {
            background-color: #45a049;
            box-shadow: 0 5px #666;
            transform: translateY(4px);
        }
        .error-message {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Результати тесту</h2>

    <?php
    // Початок сесії
    session_start();

    $host = 'localhost';
    $dbname = 'library';
    $username = 'root';
    $password = 'bogdan09';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Отримання значення test_id з сесії попередньої сторінки
        $test_id = isset($_SESSION['test_id']) ? intval($_SESSION['test_id']) : 0;
        $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0; // Припустимо, що user_id зберігається в сесії

        if ($test_id > 0 && $user_id > 0) {
            // Запит до бази даних для отримання коректних відповідей для обраного тесту
            $stmt = $pdo->prepare("SELECT Questions.id AS question_id, CorrectAnswers.answer_id AS correct_answer_id
                                    FROM Questions
                                    JOIN CorrectAnswers ON Questions.id = CorrectAnswers.question_id
                                    WHERE Questions.test_id = :test_id");
            $stmt->bindParam(':test_id', $test_id, PDO::PARAM_INT);
            $stmt->execute();
            $correct_answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Лічильник правильних відповідей
            $correct_count = 0;

            // Початок транзакції
            $pdo->beginTransaction();

            // Збереження результатів тесту
            $stmt_result = $pdo->prepare("INSERT INTO TestResults (user_id, test_id, completed_at, score) VALUES (:user_id, :test_id, NOW(), :score)");
            $stmt_result->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_result->bindParam(':test_id', $test_id, PDO::PARAM_INT);
            $stmt_result->bindParam(':score', $correct_count, PDO::PARAM_INT);
            $stmt_result->execute();
            $result_id = $pdo->lastInsertId();

            // Збереження відповідей студента
            $stmt_answer = $pdo->prepare("INSERT INTO ResultAnswers (result_id, question_id, answer_id, is_correct) VALUES (:result_id, :question_id, :answer_id, :is_correct)");

            foreach ($correct_answers as $correct_answer) {
                $question_id = $correct_answer['question_id'];
                $correct_answer_id = $correct_answer['correct_answer_id'];

                // Отримання обраної користувачем відповіді з форми (якщо вона є)
                $selected_answer_key = 'question_' . $question_id;
                if (isset($_POST[$selected_answer_key])) {
                    $selected_answer_id = $_POST[$selected_answer_key];

                    // Порівняння обраної відповіді з коректною
                    $is_correct = ($selected_answer_id == $correct_answer_id) ? 1 : 0;
                    if ($is_correct) {
                        $correct_count++; // Збільшення лічильника правильних відповідей
                    }

                    // Збереження відповіді
                    $stmt_answer->bindParam(':result_id', $result_id, PDO::PARAM_INT);
                    $stmt_answer->bindParam(':question_id', $question_id, PDO::PARAM_INT);
                    $stmt_answer->bindParam(':answer_id', $selected_answer_id, PDO::PARAM_INT);
                    $stmt_answer->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);
                    $stmt_answer->execute();
                }
            }

            // Оновлення результату з правильним рахунком
            $stmt_update_score = $pdo->prepare("UPDATE TestResults SET score = :score WHERE id = :result_id");
            $stmt_update_score->bindParam(':score', $correct_count, PDO::PARAM_INT);
            $stmt_update_score->bindParam(':result_id', $result_id, PDO::PARAM_INT);
            $stmt_update_score->execute();

            // Завершення транзакції
            $pdo->commit();

            // Виведення кількості правильних відповідей
            echo "<p class='result-message'>Кількість правильних відповідей: $correct_count</p>";
            echo '<button class="button" onclick="window.history.back();">Повернутися на сторінку завдань</button>';
        } else {
            echo "<p class='error-message'>Не вдалося отримати ідентифікатор тесту або користувача з сесії.</p>";
        }
    } catch (PDOException $e) {
        // Відкат транзакції у разі помилки
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "<p class='error-message'>Помилка: " . $e->getMessage() . "</p>";
    }
    ?>

</body>
</html>
