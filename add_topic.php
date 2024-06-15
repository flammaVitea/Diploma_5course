<?php
// Підключення до бази даних
$servername = "localhost";
$username = "root";
$password = "bogdan09";
$dbname = "library";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Отримання даних з форми
        $topic_name = $_POST['topic_name'];
        $educational_material_id = $_POST['educational_material_id'];
        $number_of_lessons = 0; // Початкове значення для нової теми
        $number_of_lectures = 0; // Початкове значення для нової теми
        $number_of_practical = 0; // Початкове значення для нової теми
        $number_of_tests = 0; // Початкове значення для нової теми

        // SQL-запит для додавання нової теми
        $sql = "INSERT INTO EducationalMaterialTopics (subject_name, educational_material_id, number_of_lessons, number_of_lectures, number_of_practical, number_of_tests)
                VALUES (:subject_name, :educational_material_id, :number_of_lessons, :number_of_lectures, :number_of_practical, :number_of_tests)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':subject_name' => $topic_name,
            ':educational_material_id' => $educational_material_id,
            ':number_of_lessons' => $number_of_lessons,
            ':number_of_lectures' => $number_of_lectures,
            ':number_of_practical' => $number_of_practical,
            ':number_of_tests' => $number_of_tests
        ]);

        // Перенаправлення назад до сторінки редагування предмета після успішного додавання
        header("Location: edit_subject.php?educational_material_id=$educational_material_id");
        exit();
    }
} catch(PDOException $e) {
    echo "Помилка: " . $e->getMessage();
}

// Закриття з'єднання з базою даних
$conn = null;
?>
