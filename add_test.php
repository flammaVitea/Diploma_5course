<?php
// Підключення до бази даних
$servername = "localhost";
$username = "root";
$password = "bogdan09";
$dbname = "library";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $test_topic = $_POST['test_topic'];
        $submission_deadline = $_POST['submission_deadline'];
        $educational_material_topics_id = $_POST['test_topic_id']; 

        // Вставка тесту в базу даних
        $sql = "INSERT INTO Tests (test_topic, submission_deadline, educational_material_topics_id) VALUES (:test_topic, :submission_deadline, :educational_material_topics_id)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':test_topic' => $test_topic,
            ':submission_deadline' => $submission_deadline,
            ':educational_material_topics_id' => $educational_material_topics_id,
        ]);

        $test_id = $conn->lastInsertId();

        // Вставка питань у базу даних
        foreach ($_POST['questions'] as $questionIndex => $questionData) {
            $question = $questionData['question'];

            $sql = "INSERT INTO Questions (test_id, question_text) VALUES (:test_id, :question_text)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':test_id' => $test_id,
                ':question_text' => $question,
            ]);

            $question_id = $conn->lastInsertId();

            $correct_answer_id = null;

            // Вставка відповідей у базу даних
            foreach ($questionData['answers'] as $answerIndex => $answer) {
                $sql = "INSERT INTO Answers (question_id, answer_text) VALUES (:question_id, :answer_text)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':question_id' => $question_id,
                    ':answer_text' => $answer,
                ]);

                $answer_id = $conn->lastInsertId();

                if ($answerIndex == $questionData['correct_answer']) {
                    $correct_answer_id = $answer_id;
                }
            }

            // Вставка правильної відповіді у базу даних
            if ($correct_answer_id !== null) {
                $sql = "INSERT INTO CorrectAnswers (question_id, answer_id) VALUES (:question_id, :answer_id)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':question_id' => $question_id,
                    ':answer_id' => $correct_answer_id,
                ]);
            }
        }

        echo "Тест успішно доданий!";
    }
} catch (PDOException $e) {
    echo "Помилка: " . $e->getMessage();
}

$conn = null;
?>
