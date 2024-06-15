<?php
session_start();

$host = 'localhost';
$dbname = 'library';
$username = 'root';
$password = 'bogdan09';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt_userstatus = $pdo->prepare("SELECT status, user_id FROM users WHERE user_id = :user_id");
    $stmt_userstatus->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt_userstatus->execute();
    $user = $stmt_userstatus->fetch(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
        $practical_id = intval($_POST['practical_id']);
        $file = $_FILES['file'];

        // Перевірка розширення файлу
        $allowed_extensions = ['pdf', 'zip'];
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (in_array($file_extension, $allowed_extensions)) {
            $upload_dir = 'uploads/';
            $file_name = time() . '_' . basename($file['name']);
            $upload_file = $upload_dir . $file_name;

            // Завантаження файлу
            if (move_uploaded_file($file['tmp_name'], $upload_file)) {
                // Внесення даних до бази
                $stmt = $pdo->prepare("INSERT INTO Submissions (practical_id, file_name, file_path, user_id, uploaded_at) VALUES (:practical_id, :file_name, :file_path, :user_id, NOW())");
                $stmt->bindParam(':practical_id', $practical_id, PDO::PARAM_INT);
                $stmt->bindParam(':file_name', $file_name);
                $stmt->bindParam(':file_path', $upload_file);
                $stmt->bindParam(':user_id', $user['user_id'], PDO::PARAM_INT);
                $stmt->execute();

                echo "Файл успішно завантажено.";
            } else {
                echo "Помилка завантаження файлу.";
            }
        } else {
            echo "Неприпустимий тип файлу. Дозволені тільки PDF і ZIP файли.";
        }
    } else {
        echo "Файл не завантажено.";
    }
} catch (PDOException $e) {
    echo "Помилка: " . $e->getMessage();
}
?>
