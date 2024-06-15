<?php
$servername = "localhost";
$username = "root";
$password = "bogdan09";
$dbname = "library";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $practical_topic = $_POST['practical_topic'];
    $practical_text = $_POST['practical_text'];
    $submission_deadline = $_POST['submission_deadline'];
    $youtube_link = $_POST['youtube_link'];
    $practical_pdf = '';
    $educational_material_topics_id = $_POST['practical_topic_id'];
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

    if (isset($_FILES['practical_pdf']) && $_FILES['practical_pdf']['error'] == 0) {
        // Створення нового імені файлу
        $newPdfName = $subject . '_' . $practical_topic . '_' . sprintf('%03d', rand(1, 9999)) . '.pdf';
        $practical_pdf = $uploadDir . $newPdfName;
        move_uploaded_file($_FILES['practical_pdf']['tmp_name'], $practical_pdf);
    }

    $sql = "INSERT INTO Practicals (practical_topic, practical_text, youtube_link, practical_pdf, submission_deadline, educational_material_topics_id) VALUES (:practical_topic, :practical_text, :youtube_link, :practical_pdf, :submission_deadline, :educational_material_topics_id)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':practical_topic', $practical_topic);
    $stmt->bindParam(':practical_text', $practical_text);
    $stmt->bindParam(':youtube_link', $youtube_link);
    $stmt->bindParam(':practical_pdf', $practical_pdf);
    $stmt->bindParam(':submission_deadline', $submission_deadline);
    $stmt->bindParam(':educational_material_topics_id', $educational_material_topics_id);

    if ($stmt->execute()) {
        header("Location: edit_subject.php?educational_material_id=$educational_material_id");
        exit();
    } else {
        echo "Сталася помилка при додаванні практичної.";
    }
} catch(PDOException $e) {
    echo "Помилка: " . $e->getMessage();
}

$conn = null;
?>
