<?php
// Підключення до бази даних
$dsn = 'mysql:host=your_host;dbname=your_dbname;charset=utf8';
$username = 'root';
$password = 'bogdan09';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);

    // Перевірка, чи було відправлено форму
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Отримання даних з форми
        $specialty_id = isset($_POST['specialty_id']) ? $_POST['specialty_id'] : null;

        if ($specialty_id) {
            // Приклад вставки даних у таблицю Students
            $sql = "INSERT INTO Students (specialty_id) VALUES (:specialty_id)";
            $statement = $pdo->prepare($sql);
            $statement->bindParam(':specialty_id', $specialty_id, PDO::PARAM_INT);

            if ($statement->execute()) {
                echo 'Data successfully inserted!';
            } else {
                echo 'Error inserting data.';
            }
        } else {
            echo 'Please select a specialty.';
        }
    }
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>
