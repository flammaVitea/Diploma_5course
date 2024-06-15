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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $year = $_POST['year'];
    $educational_materials_id = $_POST['educational_materials_id'];
    $teacher_id = $_POST['teacher_id'];
    $specialty_id = $_POST['specialty_id'];
    $subject_code = $_POST['subject_code'];
    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];

    $sql = "INSERT INTO Catalog (year, educational_materials_id, teacher_id, specialty_id, subject_code, semester, academic_year)
            VALUES (:year, :educational_materials_id, :teacher_id, :specialty_id, :subject_code, :semester, :academic_year)";

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([
        'year' => $year,
        'educational_materials_id' => $educational_materials_id,
        'teacher_id' => $teacher_id,
        'specialty_id' => $specialty_id,
        'subject_code' => $subject_code,
        'semester' => $semester,
        'academic_year' => $academic_year,
    ])) {
        echo "Catalog entry added successfully.";
    } else {
        echo "Error adding catalog entry.";
    }
}
?>
