<?php
session_start();

// Налаштування з'єднання з базою даних
$host = 'localhost';
$db = 'library';
$user = 'root';
$pass = 'bogdan09';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Обробка форми додавання предмету
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['subject_name'], $_POST['author_id'])) {
        $subjectName = $_POST['subject_name'];
        $authorId = $_POST['author_id'];
        
        // Підготовлений запит
        $sql = "INSERT INTO EducationalMaterials (educational_materials_name, author) VALUES (:subject_name, :author_id)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute(['subject_name' => $subjectName, 'author_id' => $authorId])) {
            echo "Educational material added successfully.";
        } else {
            echo "Error adding educational material.";
        }
    }
}
?>
