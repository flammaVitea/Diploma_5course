<?php
// Перевірка, чи користувач увійшов у систему
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Перенаправлення на сторінку входу, якщо користувач не увійшов у систему
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

    // Вибірка профілю студента
    $stmt = $pdo->prepare("SELECT Students.*, Persons.lastname, Persons.firstname, users.email, Specialties.specialty_name, Students.semester, Students.yearOfEntry
                           FROM Students 
                           JOIN Persons ON Students.person_id = Persons.person_id
                           JOIN users ON Students.user_id = users.user_id
                           JOIN Specialties ON Students.specialty_id = Specialties.specialty_id
                           WHERE Students.user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $student_profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // Отримання даних про предмети, які вивчає студент, з бази даних
    $stmt = $pdo->prepare("SELECT Catalog.*, EducationalMaterials.educational_materials_name 
                        FROM Catalog 
                        JOIN EducationalMaterials ON Catalog.educational_materials_id = EducationalMaterials.educational_materials_id 
                        JOIN Students ON Catalog.specialty_id = Students.specialty_id
                        WHERE Students.user_id = :user_id
                        AND Catalog.semester <= :current_semester
                        AND Catalog.year <= :year_of_entry
                        ORDER BY Catalog.semester");
    $stmt->bindParam(':user_id', $user_id);
    $current_semester = ($student_profile['semester'] - 1) * 2; // Конвертуємо номер семестру у загальний номер
    $stmt->bindParam(':current_semester', $current_semester);
    $year_of_entry = $student_profile['yearOfEntry'];
    $stmt->bindParam(':year_of_entry', $year_of_entry);
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
    <title>Профіль користувача</title>
</head>
<body>
    <section class="container">
        <div class="profile-header">
            <img src="img/img.jpg" alt="Аватар користувача">
            <h2><?php echo $student_profile['firstname'] . ' ' . $student_profile['lastname']; ?></h2>
        </div>
        <div class="container2">
            <div class="profile-info">
                <h3>Інформація про користувача</h3>
                <p><strong>Електронна пошта: </strong> <?php echo $student_profile['email']; ?></p>
                <p><strong>Статус: </strong> Активний</p>
            </div>
        </div>

        <h3 class="enrolled">Записані курси</h3>
        <section class="student_grid">
        <?php 
            $current_course = 0;
            $current_semester = 0;
            foreach ($subjects as $subject) {
                $course_at_semester = intval(($subject['semester'] - 1) / 2) + 1;
                if ($current_course != $course_at_semester || $current_semester != $subject['semester']) {
                    if ($current_course != 0 || $current_semester != 0) {
                        echo '</div>'; // Закриваємо попередній контейнер перед відкриванням нового
                    }
                    $current_course = $course_at_semester;
                    $current_semester = $subject['semester'];
                    echo '<div class="course-container">';
                    echo '<h4>Курс ' . $current_course . ' Семестр ' . $current_semester . '</h4>';
                }
                // Посилання на сторінку предмету з ідентифікатором предмету у параметрах запиту
                echo '<li><a href="study_material_page.php?educational_material_id=' . $subject['educational_materials_id'] . '">' . $subject['educational_materials_name'] . '</a></li>';
            }
        ?>
        </section>
            <form action="logout.php" method="post" class="logout">
                <button type="submit" class="logout-button" >Вийти</button>
            </form>
        </section>
    </body>
</html>
