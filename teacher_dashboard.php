<?php
session_start();

// Перевірка, чи користувач увійшов у систему
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Перенаправлення на сторінку входу, якщо користувач не увійшов у систему
    exit;
}

// Підключення до бази даних
$host = 'localhost';
$dbname = 'library';
$username = 'root';
$password = 'bogdan09';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['status'];

    // Вибірка профілю викладача
    $stmt = $pdo->prepare("SELECT Teachers.*, Persons.lastname, Persons.firstname, users.email
                           FROM Teachers 
                           JOIN Persons ON Teachers.person_id = Persons.person_id
                           JOIN users ON Teachers.user_id = users.user_id
                           WHERE Teachers.user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $teacher_profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // Перевірка, чи знайдено профіль викладача
    if ($teacher_profile === false) {
        $teacher_profile = null;
    }

    // Отримання даних про предмети, які викладає викладач, з бази даних, відсортованих за роком та семестром
    $stmt = $pdo->prepare("SELECT Catalog.*, EducationalMaterials.educational_materials_name 
                           FROM Catalog 
                           JOIN EducationalMaterials ON Catalog.educational_materials_id = EducationalMaterials.educational_materials_id 
                           JOIN Teachers ON Catalog.teacher_id = Teachers.teacher_id
                           WHERE Teachers.user_id = :user_id
                           AND (Catalog.academic_year < YEAR(CURDATE()) OR (Catalog.academic_year = YEAR(CURDATE()) AND Catalog.semester <= :current_semester))
                           ORDER BY Catalog.academic_year, Catalog.semester");
    $stmt->bindParam(':user_id', $user_id);
    $current_semester = 10; // Припустимо, що викладач може викладати всі семестри
    $stmt->bindParam(':current_semester', $current_semester);
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Помилка при виборі даних з бази даних: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="Style/style_user_profile.css?v=202403251017">
    <title>Профіль викладача</title>
    <style>
        /* Додаткові стилі для відображення предметів */
        .course-container {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .course-header {
            background-color: #007bff;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <section class="container">
        <div class="profile-header">
            <img src="img/img.jpg" alt="User Avatar">
            <?php if ($teacher_profile): ?>
                <h2><?php echo htmlspecialchars($teacher_profile['firstname']) . ' ' . htmlspecialchars($teacher_profile['lastname']); ?></h2>
            <?php else: ?>
                <h2>Профіль не знайдено</h2>
            <?php endif; ?>
        </div>
        <div class="container2">
            <div class="profile-info">
                <h3>Інформація користувача</h3>
                <?php if ($teacher_profile): ?>
                    <p><strong>Пошта: </strong> <?php echo htmlspecialchars( $teacher_profile['email']); ?></p>
                <?php else: ?>
                    <p>Інформацію профілю не вдалося отримати.</p>
                <?php endif; ?>
                <p><strong>Статус: </strong> Активний</p>
            </div>
        </div>
        <h3 class="enrolled">Предмети</h3>
        <section class="teacher_grid">
        <?php 
            $current_academic_year = null;
            foreach ($subjects as $subject) {
                if ($subject['academic_year'] !== $current_academic_year) {
                    $current_academic_year = $subject['academic_year'];
                    echo '<div class="course-header">Рік ' . htmlspecialchars($subject['academic_year']) . '</div>';
                }
                echo '<div class="course-container">';
                echo '<p>Рік ' . htmlspecialchars($subject['academic_year']) . ', Семестр ' . htmlspecialchars($subject['semester']) . '</p>';
                echo '<li><a href="study_material_page.php?educational_material_id=' . htmlspecialchars($subject['educational_materials_id']) . '">' . htmlspecialchars($subject['educational_materials_name']) . '</a></li>';
                echo '</div>';
            }
        ?>
        </section>
        <form action="logout.php" method="post" class="logout">
            <button type="submit" class="logout-button">Вийти</button>
        </form>
    </section>
</body>
</html>
