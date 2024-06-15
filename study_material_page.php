<?php
// Підключення до бази даних
$host = 'localhost';
$dbname = 'library';
$username = 'root';
$password = 'bogdan09';

// Початок сесії
session_start();

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Отримання статусу користувача з бази даних
    $stmt_userstatus = $pdo->prepare("SELECT status FROM users WHERE user_id = :user_id");
    $stmt_userstatus->bindParam(':user_id', $_SESSION['user_id']);
    $stmt_userstatus->execute();
    $user = $stmt_userstatus->fetch(PDO::FETCH_ASSOC);

    // Обробка форми для перенаправлення на сторінку профілю
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($user['status'])) {
            if ($user['status'] === 'student') {
                header('Location: student_dashboard.php');
                exit();
            } elseif ($user['status'] === 'teacher') {
                header('Location: teacher_dashboard.php');
                exit();
            }
        } else {
            echo "Тип користувача не визначено.";
        }
    }

    // Отримання educational_material_id з URL
    $educational_material_id = isset($_GET['educational_material_id']) ? $_GET['educational_material_id'] : null;

    if ($educational_material_id) {
        // Занесення educational_material_id в сесію
        $_SESSION['educational_material_id'] = $educational_material_id;

        $stmt = $pdo->prepare("
        SELECT m.educational_materials_id 
        FROM 
            EducationalMaterials as m 
            join Teachers as t on m.author=t.teacher_id
        where t.user_id= :user_id;");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $edu_mat_teach = $stmt->fetchAll(PDO::FETCH_NUM);
        $isAuthor = false;
        $found = array_search($educational_material_id, array_column($edu_mat_teach, 0));
        if (is_numeric($found)) {
            $isAuthor = true; 
        }

        // Отримання інформації про предмет та його теми з бази даних
        $stmt = $pdo->prepare("
            SELECT e.*, p.* 
            FROM EducationalMaterials AS e 
            JOIN Teachers AS t ON e.author = t.teacher_id 
            JOIN Persons AS p ON t.person_id = p.person_id 
            WHERE e.educational_materials_id = :educational_material_id");
        $stmt->bindParam(':educational_material_id', $educational_material_id);
        $stmt->execute();
        $subject_info = $stmt->fetch(PDO::FETCH_ASSOC);

        // Отримання тем для цього предмета
        $stmt = $pdo->prepare("SELECT * FROM EducationalMaterialTopics WHERE educational_material_id = :educational_material_id");
        $stmt->bindParam(':educational_material_id', $educational_material_id);
        $stmt->execute();
        $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Отримання інформації про викладача, який є автором предмету
        $stmt = $pdo->prepare("
        SELECT * 
        FROM Teachers 
        JOIN users ON Teachers.user_id = users.user_id 
        WHERE users.user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $teacher_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Помилка при виборі даних з бази даних: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Material Page</title>
    <link rel="stylesheet" href="Style/style_stydy_material_page.css?=202404121600">
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($subject_info['educational_materials_name']); ?></h1>
        <p>
            <strong>Автор:</strong> 
            <?php echo htmlspecialchars($subject_info['lastname'] . ' ' . $subject_info['firstname'] . ' ' . $subject_info['middlename']); ?>
        </p>
        <h2>Теми:</h2>
        <ul>
            <?php foreach ($topics as $topic): ?>
                <li>
                    <?php echo '<a href="lessons.php?educational_material_topics_id=' . htmlspecialchars($topic['educational_material_topics_id']) . '">' . htmlspecialchars($topic['subject_name']) . '</a>'; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <form method="post" action="">
            <button type="submit">Повернення на сторінку профіля</button>
        </form>
        <?php 
        // Перевірка, чи поточний користувач є автором предмету
        if ($isAuthor) {
            echo '<a class="edit_subject" href="edit_subject.php?educational_material_id=' . htmlspecialchars($educational_material_id) . '">Редагувати предмет</a>';
        }
        ?>
    </div>
</body>
</html>
