<?php
$servername = "localhost";
$username = "root";
$password = "bogdan09";
$dbname = "library";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $lecture_topic = $_POST['lecture_topic'];
    $lecture_text = $_POST['lecture_text'];
    $youtube_link = $_POST['youtube_link'];
    $lecture_pdf = '';
    $educational_material_topics_id = $_POST['lecture_topic_id'];
    $educational_material_id = $_POST['educational_material_id'];

    // Отримуємо назву предмета з бази даних
    $stmt = $conn->prepare("SELECT educational_materials_name FROM EducationalMaterials WHERE educational_materials_id = :educational_materials_id");
    $stmt->bindParam(':educational_materials_id', $educational_material_id); // Змінили назву параметра на правильну
    $stmt->execute();
    $subject = $stmt->fetch(PDO::FETCH_ASSOC)['educational_materials_name'];

    // Перевірка наявності директорії uploads та її створення, якщо вона відсутня
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (isset($_FILES['lecture_pdf']) && $_FILES['lecture_pdf']['error'] == 0) {
        // Створення нового імені файлу
        $newPdfName = $subject . '_' . $lecture_topic . '_' . sprintf('%03d', rand(1, 9999)) . '.pdf';
        $lecture_pdf = $uploadDir . $newPdfName;
        move_uploaded_file($_FILES['lecture_pdf']['tmp_name'], $lecture_pdf);
    }

    $sql = "INSERT INTO Lectures (lecture_topic, lecture_text, youtube_link, lecture_pdf, educational_material_topics_id) VALUES (:lecture_topic, :lecture_text, :youtube_link, :lecture_pdf, :educational_material_topics_id)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':lecture_topic', $lecture_topic);
    $stmt->bindParam(':lecture_text', $lecture_text);
    $stmt->bindParam(':youtube_link', $youtube_link);
    $stmt->bindParam(':lecture_pdf', $lecture_pdf);
    $stmt->bindParam(':educational_material_topics_id', $educational_material_topics_id);

    if ($stmt->execute()) {
        header("Location: edit_subject.php?educational_material_id=$educational_material_id");
        exit();
    } else {
        echo "Сталася помилка при додаванні лекції.";
    }
} catch(PDOException $e) {
    echo "Помилка: " . $e->getMessage();
}

$conn = null;
?>
