<?php

session_start();

$host = 'localhost';
$dbname = 'library';
$username = 'root';
$password = 'bogdan09';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $type = isset($_GET['type']) ? $_GET['type'] : null;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $stmt_userstatus = $pdo->prepare("SELECT status, user_id FROM users WHERE user_id = :user_id");
    $stmt_userstatus->bindParam(':user_id', $_SESSION['user_id']);
    $stmt_userstatus->execute();
    $user = $stmt_userstatus->fetch(PDO::FETCH_ASSOC);

    if ($type && $id > 0) {
        if ($type === 'lectures') {
            $stmt = $pdo->prepare("SELECT * FROM Lectures WHERE lecture_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $content = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($content) {
                echo "<h1>" . htmlspecialchars($content['lecture_topic']) . "</h1>";
                echo "<p>" . htmlspecialchars($content['lecture_text']) . "</p>";
                if (!empty($content['lecture_pdf'])) {
                    echo "<p><a href=\"" . htmlspecialchars($content['lecture_pdf']) . "\" target=\"_blank\">Відкрити PDF</a></p>";
                }
                if (!empty($content['youtube_link'])) {
                    echo "<p><a href=\"" . htmlspecialchars($content['youtube_link']) . "\" target=\"_blank\">Переглянути відео</a></p>";
                }                
            } else {
                echo "<p>Контент не знайдено.</p>";
            }
        } elseif ($type === 'practicals') {
            $stmt = $pdo->prepare("SELECT * FROM Practicals WHERE practical_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $content = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($content) {
                echo "<h1>" . htmlspecialchars($content['practical_topic']) . "</h1>";
                echo "<p>" . htmlspecialchars($content['practical_text']) . "</p>";
                if (!empty($content['practical_pdf'])) {
                    echo "<p><a href=\"" . htmlspecialchars($content['practical_pdf']) . "\" target=\"_blank\">Відкрити PDF</a></p>";
                }
                if (!empty($content['youtube_link'])) {
                    echo "<p><a href=\"" . htmlspecialchars($content['youtube_link']) . "\" target=\"_blank\">Переглянути відео</a></p>";
                }
                // Перевірка ролі користувача для відображення відповідних кнопок
                if ($user['status'] === 'student') {
                    echo '
                        <form action="upload.php" method="post" enctype="multipart/form-data">
                            <p><input type="hidden" name="practical_id" value="' . htmlspecialchars($_GET["id"]) . '"></p>
                            <p><input type="file" name="file" accept=".pdf,.zip" required></p>
                            <p style="text-align: center;"><input style="width: 300px; height: 50px; border-radius: 5px; font-size: 20px;" type="submit" value="Здати"></p>
                        </form>';
                } elseif ($user['status'] === 'teacher') {
                    echo '
                        <form action="view_submissions.php" method="get">
                            <input type="hidden" name="practical_id" value="' . htmlspecialchars($_GET["id"]) . '">
                            <p style="text-align: center;"><input style=" padding: 10px; border-radius: 10px " type="submit" value="Переглянути виконані роботи"></p>
                        </form>';
                }
            } else {
                echo "<p>Контент не знайдено.</p>";
            }
        }
    } else {
        echo "<p>Невірний ідентифікатор контенту.</p>";
    }
} catch (PDOException $e) {
    echo "Помилка: " . $e->getMessage();
}
?>