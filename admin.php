<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Перенаправлення на сторінку входу, якщо користувач не увійшов у систему
    exit;
}
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
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Обробка форми для реєстрації персони
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];

    $stmt = $pdo->prepare('INSERT INTO Persons (lastname, firstname, middlename, gender, birthdate) VALUES (:lastname, :firstname, :middlename, :gender, :birthdate)');
    $stmt->execute([
        'lastname' => $lastname,
        'firstname' => $firstname,
        'middlename' => $middlename,
        'gender' => $gender,
        'birthdate' => $birthdate,
    ]);

    $person_id = $pdo->lastInsertId();

    if ($status == 'student') {
        $stmt = $pdo->prepare('INSERT INTO Students (person_id) VALUES (:person_id)');
        $stmt->execute(['person_id' => $person_id]);
    } else if ($status == 'teacher') {
        $stmt = $pdo->prepare('INSERT INTO Teachers (person_id) VALUES (:person_id)');
        $stmt->execute(['person_id' => $person_id]);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Обробка форми для реєстрації користувача (студента чи викладача)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO users (email, password) VALUES (:email, :password)');
    $stmt->execute(['email' => $email, 'password' => $password]);

    $user_id = $pdo->lastInsertId();

    if (isset($_POST['student'])) {
        $student_id = $_POST['student'];
        $stmt = $pdo->prepare('UPDATE Students SET user_id = :user_id WHERE student_id = :student_id');
        $stmt->execute(['user_id' => $user_id, 'student_id' => $student_id]);
    } else if (isset($_POST['teacher'])) {
        $teacher_id = $_POST['teacher'];
        $stmt = $pdo->prepare('UPDATE Teachers SET user_id = :user_id WHERE teacher_id = :teacher_id');
        $stmt->execute(['user_id' => $user_id, 'teacher_id' => $teacher_id]);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

    // Обробка форми для додавання студента
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
        $specialty = $_POST['specialty_id'];
        $semester = $_POST['semester'];
        $yearOfEntry = $_POST['yearOfEntry'];
        $person_id = 0; // Заміна на реальний person_id
        $user_id = 0;   // Заміна на реальний user_id

        $stmt = $pdo->prepare('INSERT INTO Students (specialty_id, semester, yearOfEntry, person_id, user_id) VALUES (:specialty, :semester, :yearOfEntry, :person_id, :user_id)');
        $stmt->execute([
            'specialty' => $specialty,
            'semester' => $semester,
            'yearOfEntry' => $yearOfEntry,
            'person_id' => $person_id,
            'user_id' => $user_id,
        ]);

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Обробка форми редагування студента
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
        $student_id = $_POST['student_id'];
        $specialty = $_POST['specialty_id'];
        $semester = $_POST['edit_semester'];
        $yearOfEntry = $_POST['edit_yearOfEntry'];

        $stmt = $pdo->prepare('UPDATE Students SET specialty_id = :specialty, semester = :semester, yearOfEntry = :yearOfEntry WHERE student_id = :student_id');
        $stmt->execute([
            'specialty' => $specialty,
            'semester' => $semester,
            'yearOfEntry' => $yearOfEntry,
            'student_id' => $student_id,
        ]);
    }


// Отримання критеріїв фільтрації з GET-запитів
$studentFilter = isset($_GET['studentFilter']) ? $_GET['studentFilter'] : '';
$teacherFilter = isset($_GET['teacherFilter']) ? $_GET['teacherFilter'] : '';
    // Отримання спеціальностей
    $sql = "SELECT specialty_id, specialty_name FROM Specialties";
    $statement = $pdo->prepare($sql);
    $statement->execute();
    $specialties = $statement->fetchAll();

    // Отримання студентів
    $stmt_aS = $pdo->prepare('
        SELECT 
            s.student_id AS "ID студента",
            sp.specialty_name AS "Назва спеціальності",
            CONCAT(p.lastname, " ", p.firstname, " ", p.middlename) AS "ПІБ",
            s.semester AS "Семестр",
            u.email AS "Email",
            s.yearOfEntry AS "Рік вступу"
        FROM Students s
        LEFT JOIN Specialties sp ON s.specialty_id = sp.specialty_id
        LEFT JOIN Persons p ON s.person_id = p.person_id
        LEFT JOIN users u ON s.user_id = u.user_id
        WHERE CONCAT(p.lastname, " ", p.firstname, " ", p.middlename) LIKE :studentFilter
    ');

    $studentFilter = isset($_GET['studentFilter']) ? $_GET['studentFilter'] : '';
    $stmt_aS->execute(['studentFilter' => "%$studentFilter%"]);
    $students = $stmt_aS->fetchAll();

// Запит на отримання даних з таблиці Teachers, Persons та Users з фільтрацією
$stmt_aT = $pdo->prepare('
    SELECT 
        t.teacher_id AS "ID викладача",
        CONCAT(p.lastname, " ", p.firstname, " ", p.middlename) AS "ПІБ",
        u.email AS "Email"
    FROM Teachers t
    LEFT JOIN Persons p ON t.person_id = p.person_id
    LEFT JOIN users u ON t.user_id = u.user_id
    WHERE CONCAT(p.lastname, " ", p.firstname, " ", p.middlename) LIKE :teacherFilter
');

$stmt_aT->execute(['teacherFilter' => "%$teacherFilter%"]);
$teachers = $stmt_aT->fetchAll();

   // Кількість акаунтів
   $stmt_total_accounts = $pdo->query("SELECT COUNT(*) AS total_accounts FROM users");
   $total_accounts = $stmt_total_accounts->fetch(PDO::FETCH_ASSOC)['total_accounts'];

   // Кількість користувачів-вчителів
   $stmt_total_teachers = $pdo->query("SELECT COUNT(*) AS total_teachers FROM Teachers");
   $total_teachers = $stmt_total_teachers->fetch(PDO::FETCH_ASSOC)['total_teachers'];

   // Кількість користувачів-студентів
   $stmt_total_students = $pdo->query("SELECT COUNT(*) AS total_students FROM Students");
   $total_students = $stmt_total_students->fetch(PDO::FETCH_ASSOC)['total_students'];

   // Кількість викладачів без акаунтів
   $stmt_teachers_without_accounts = $pdo->query("SELECT COUNT(*) AS teachers_without_accounts FROM Teachers WHERE user_id IS NULL");
   $teachers_without_accounts = $stmt_teachers_without_accounts->fetch(PDO::FETCH_ASSOC)['teachers_without_accounts'];

   // Кількість студентів без акаунтів
   $stmt_students_without_accounts = $pdo->query("SELECT COUNT(*) AS students_without_accounts FROM Students WHERE user_id IS NULL");
   $students_without_accounts = $stmt_students_without_accounts->fetch(PDO::FETCH_ASSOC)['students_without_accounts'];
   
   // Кількість не розподілених студентів по спеціальностях
   $stmt_students_without_specialty = $pdo->query("SELECT COUNT(*) AS students_without_specialty FROM Students WHERE specialty_id IS NULL");
   $students_without_specialty = $stmt_students_without_specialty->fetch(PDO::FETCH_ASSOC)['students_without_specialty'];

   // Кількість не розподілених студентів по року вступу
   $stmt_students_without_yearOfEntry = $pdo->query("SELECT COUNT(*) AS students_without_yearOfEntry FROM Students WHERE yearOfEntry IS NULL");
   $students_without_yearOfEntry = $stmt_students_without_yearOfEntry->fetch(PDO::FETCH_ASSOC)['students_without_yearOfEntry'];

   // Кількість не розподілених студентів по семестру
   $stmt_students_without_semester = $pdo->query("SELECT COUNT(*) AS students_without_semester FROM Students WHERE semester IS NULL");
   $students_without_semester = $stmt_students_without_semester->fetch(PDO::FETCH_ASSOC)['students_without_semester'];

    // Кількість предметів
    $stmt_subjects_count = $pdo->query("SELECT COUNT(*) AS subjects_count FROM EducationalMaterials");
    $subjects_count = $stmt_subjects_count->fetch(PDO::FETCH_ASSOC)['subjects_count'];

    // Кількість предметів у каталозі
    $stmt_catalog_subjects_count = $pdo->query("SELECT COUNT(*) AS catalog_subjects_count FROM Catalog");
    $catalog_subjects_count = $stmt_catalog_subjects_count->fetch(PDO::FETCH_ASSOC)['catalog_subjects_count'];

    $sql = "
    SELECT 
        c.id_catalog, c.year, em.educational_materials_name, t.teacher_id, c.specialty_id, s.specialty_name, c.subject_code, c.semester, c.academic_year, p.lastname, p.firstname, p.middlename
    FROM 
        Catalog c
    JOIN 
        EducationalMaterials em ON c.educational_materials_id = em.educational_materials_id
    JOIN 
        Teachers t ON c.teacher_id = t.teacher_id
    JOIN 
        Persons p ON t.person_id = p.person_id
    JOIN 
        Specialties s ON c.specialty_id = s.specialty_id";

    $stmt = $pdo->query($sql);
    $catalogs = $stmt->fetchAll();

// Fetching data from the EducationalMaterials table with author details
    $sql = "
        SELECT 
            em.educational_materials_id, em.educational_materials_name, p.lastname, p.firstname, p.middlename FROM EducationalMaterials em
        JOIN 
            Teachers t ON em.author = t.teacher_id
        JOIN 
            Persons p ON t.person_id = p.person_id";
    $stmt = $pdo->query($sql);
    $materials = $stmt->fetchAll();
    
    
    $sql = "SELECT specialty_id, specialty_name FROM Specialties";
    $statement = $pdo->prepare($sql);
    $statement->execute();
    $specialties = $statement->fetchAll(PDO::FETCH_ASSOC);
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Адмін-панель</title>
    <link rel="stylesheet" href="Style/style_admin.css?7">
</head>
<body>
    <div class="burger" id="burger">
        &#9776;
    </div>
    <div class="sidebar" id="sidebar">
        <a class="sidebar_punct" href="#dashboard" ><h2>Адмін-панель</h2></a>
        <a class="sidebar_punct" href="#register_person">Реєстрація особи</a>
        <a class="sidebar_punct" href="#audit_student">Студенти</a>
        <a class="sidebar_punct" href="#audit_teacher">Викладачі</a>
        <a class="sidebar_punct" href="#register_user">Реєстрація користувача</a>

        <hr style="width: 100%;">

        <a class="sidebar_punct" href="#educational_materials">Навчальні матеріали</a>
        <a class="sidebar_punct" href="#catalog">Каталог</a>
        <a class="sidebar_punct" href="#registration_material">Оформлення нового навчального матеріалу</a>
        <a class="sidebar_punct" href="#entering_information">Внесення інформації в каталог</a>

        <hr style="width: 100%;">

        <form action="index.php" method="post" style="margin-top: 20px;">
            <input type="submit" name="logout" value="Вийти" class="sidebar_punct" style="margin-left: 0; border: none; background: none; cursor: pointer; color: inherit; text-align: left; padding: 10px;">
        </form>

    </div>
    <div class="content" id="content">
    <h2 class='h2_element' id='dashboard'>Інформація</h2>    
        <div id="dashboard-container" class="card">
            <div class="dashboard_element account">
                <h3>Кількість акаунтів</h3>
                <p><?php echo htmlspecialchars($total_accounts); ?></p>
            </div>
            <div class="dashboard_element account_teachers">
                <h3>Кількість користувачів-вчителів</h3>
                <p><?php echo htmlspecialchars($total_teachers); ?></p>
            </div>
            <div class="dashboard_element account">
                <h3>Кількість користувачів-студентів</h3>
                <p><?php echo htmlspecialchars($total_students); ?></p>
            </div>
            <div class="dashboard_element account_teachers">
                <h3>Викладачі без акаунтів</h3>
                <p><?php echo htmlspecialchars($teachers_without_accounts); ?></p>
            </div>
            <div class="dashboard_element account_students">
                <h3>Студенти без акаунтів</h3>
                <p><?php echo htmlspecialchars($students_without_accounts); ?></p>
            </div>
            <div class="dashboard_element account_students">
                <h3>Студенти без спеціальності</h3>
                <p><?php echo htmlspecialchars($students_without_specialty); ?></p>
            </div>
            <div class="dashboard_element account_students">
                <h3>Студенти без року вступу</h3>
                <p><?php echo htmlspecialchars($students_without_yearOfEntry); ?></p>
            </div>
            <div class="dashboard_element account_students">
                <h3>Студенти без семестру</h3>
                <p><?php echo htmlspecialchars($students_without_semester); ?></p>
            </div>
            <div class="dashboard_element account_subjects">
                <h3>Кількість предметів</h3>
                <p><?php echo htmlspecialchars($subjects_count); ?></p>
            </div>
            <div class="dashboard_element catalog_subjects">
                <h3>Кількість предметів у каталозі</h3>
                <p><?php echo htmlspecialchars($catalog_subjects_count); ?></p>
            </div>
        </div>
        <h2 class='h2_element'>Реєстрація особи</h2>
        <div id="register_person" class="card">
            <form action="admin.php" method="post">
                <label for="status">Status:</label><br>
                <select id="status" name="status" required>
                    <option value="student">Студент</option>
                    <option value="teacher">Викладач</option>
                </select><br><br>
                <label for="lastname">Прізвище:</label><br>
                <input type="text" id="lastname" name="lastname" required><br>
                <label for="firstname">Ім'я:</label><br>
                <input type="text" id="firstname" name="firstname" required><br>
                <label for="middlename">по Батькові:</label><br>
                <input type="text" id="middlename" name="middlename" required><br>
                <label for="gender">Стать:</label><br>
                <div class="male gender">
                    <input type="radio" id="male" name="gender" value="1" required>
                    <label for="male" class="gender_label">Чоловік</label><br>
                </div>
                <div class="female gender">
                    <input type="radio" id="female" name="gender" value="0">
                    <label for="female" class="gender_label">Жінка</label>
                </div><br>
                <label for="birthdate">Дата народження:</label><br>
                <input type="date" id="birthdate" name="birthdate" required><br><br>
                <input type="submit" value="Зареєструвати">
            </form>
        </div>
        <h2 class='h2_element'>Студент</h2>
        <div id="audit_student" class="card">
        <form method="get" action="">
            <label for="studentFilter">Фільтрація по іменам:</label>
            <input type="text" id="studentFilter" name="studentFilter" value="<?= htmlspecialchars($studentFilter) ?>">
            <input type="submit" value="Фільтрувати">
        </form>
        <form method="post" action="">
            <h3>Редагування даних студента</h3>
            <label for="student_id">Виберіть студента:</label>
            <select id="student_id" name="student_id" required>
                <option value="">Виберіть студента</option>
                <?php foreach ($students as $student) : ?>
                    <option value="<?= htmlspecialchars($student['ID студента']) ?>"><?= htmlspecialchars($student['ПІБ']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="edit_student">    
                <label for="specialty_id">Спеціальність:</label>
                <select id="specialty_id" name="specialty_id" required>
                    <option value="">Виберіть спеціальність</option>
                    <?php foreach ($specialties as $specialty) : ?>
                        <option value="<?= htmlspecialchars($specialty['specialty_id']) ?>"><?= htmlspecialchars($specialty['specialty_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="edit_semester">Семестр:</label>
                <input type="number" id="edit_semester" name="edit_semester" class="edit_student_input" required>
                <label for="edit_yearOfEntry">Рік вступу:</label>
                <input type="number" id="edit_yearOfEntry" name="edit_yearOfEntry" class="edit_student_input" required>
            </div>    
            <input type="submit" name="edit_student" value="Редагувати">
        </form>
        <table>
            <tr>
                <th class="th_id">ID студента</th>
                <th class="">Назва спеціальності</th>
                <th class="">ПІБ</th>
                <th class="th_semester">Семестр</th>
                <th class="">Пошта</th>
                <th class="th_year">Рік вступу</th>
            </tr>
            <?php foreach ($students as $student) : ?>
                <tr>
                    <td class=""><?= htmlspecialchars($student['ID студента']) ?></td>
                    <td class=""><?= htmlspecialchars($student['Назва спеціальності']) ?></td>
                    <td class=""><?= htmlspecialchars($student['ПІБ']) ?></td>
                    <td class=""><?= htmlspecialchars($student['Семестр']) ?></td>
                    <td class=""><?= htmlspecialchars($student['Email']) ?></td>
                    <td class=""><?= htmlspecialchars($student['Рік вступу']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
        <h2 class='h2_element'>Викладач</h2>
        <div id="audit_teacher" class="card">
            <form method="get" action="">
                <label for="teacherFilter">Фільтрація по іменам:</label>
                <input type="text" id="teacherFilter" name="teacherFilter" value="<?= htmlspecialchars($teacherFilter) ?>">
                <input type="submit" value="Фільтрувати">
            </form>
            <table>
                <tr>
                    <th class="th_id">ID викладача</th>
                    <th class="">ПІБ</th>
                    <th class="">Пошта</th>
                </tr>
                <?php foreach ($teachers as $teacher) : ?>
                    <tr>
                        <td class=""><?= htmlspecialchars($teacher['ID викладача']) ?></td>
                        <td class=""><?= htmlspecialchars($teacher['ПІБ']) ?></td>
                        <td class=""><?= htmlspecialchars($teacher['Email']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <h2 class='h2_element'>Реєстрація користувача</h2>
        <div id="register_user" class="card">
            <div class="registration-section">
                <h1>Реєстрація студента</h1>
                <form action="admin.php" method="post">
                    <label for="student">Оберіть студента:</label><br>
                    <select name="student" id="student" multiple size="10">
                        <?php
                        $sql_students = "SELECT Students.student_id, Persons.firstname, Persons.lastname FROM Students JOIN Persons ON Students.person_id = Persons.person_id WHERE Students.user_id IS NULL";
                        $stmt_students = $pdo->query($sql_students);
                        while ($row = $stmt_students->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . $row['student_id'] . '">' . $row['firstname'] . ' ' . $row['lastname'] . '</option>';
                        }
                        ?>
                    </select><br>
                    <label for="email">Пошта:</label><br>
                    <input type="email" id="email" name="email" required><br>
                    <label for="password">Пароль:</label><br>
                    <input type="password" id="password" name="password" required><br><br>
                    <input type="submit" value="Зареєструвати">
                </form>
            </div>
            <div class="registration-section">
                <h1>Реєстрація викладача</h1>
                <form action="admin.php" method="post">
                    <label for="teacher">Виберіль викладача:</label><br>
                    <select name="teacher" id="teacher" multiple size="10">
                        <?php
                        $sql_teachers = "SELECT Teachers.teacher_id, Persons.firstname, Persons.lastname FROM Teachers JOIN Persons ON Teachers.person_id = Persons.person_id WHERE Teachers.user_id IS NULL";
                        $stmt_teachers = $pdo->query($sql_teachers);
                        while ($row = $stmt_teachers->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . $row['teacher_id'] . '">' . $row['firstname'] . ' ' . $row['lastname'] . '</option>';
                        }
                        ?>
                    </select><br>
                    <label for="email">Пошта:</label><br>
                    <input type="email" id="email" name="email" required><br>
                    <label for="password">Пароль:</label><br>
                    <input type="password" id="password" name="password" required><br><br>
                    <input type="submit" value="Зареєструвати">
                </form>
            </div>
        </div>
        <h2 class='h2_element'>Навчальні матеріали</h2>
        <div id="educational_materials" class="card">
        <table>
        <thead>
            <tr>
                <th>№</th>
                <th>Навчальний матерал</th>
                <th>Автор</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($materials as $material) {
                $author_full_name = "{$material['lastname']} {$material['firstname']} {$material['middlename']}";
                echo "<tr>
                        <td>{$material['educational_materials_id']}</td>
                        <td>{$material['educational_materials_name']}</td>
                        <td>{$author_full_name}</td>
                    </tr>";
            }
            ?>
        </tbody>
    </table>
        </div>
        <h2 class='h2_element'>Каталог</h2>
        <div id="catalog" class="card">
        <table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>Рік втупу студентів</th>
                    <th>Назва предмету</th>
                    <th>Викладач</th>
                    <th>Спеціальність</th>
                    <th>Код групи</th>
                    <th>Семестр</th>
                    <th>Рік викладання</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($catalogs as $catalog): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($catalog['id_catalog']); ?></td>   
                        <td><?php echo htmlspecialchars($catalog['year']); ?></td>
                        <td><?php echo htmlspecialchars($catalog['educational_materials_name']); ?></td>
                        <td><?php echo htmlspecialchars($catalog['lastname'] . ' ' . $catalog['firstname'] . ' ' . $catalog['middlename']); ?></td>
                        <td><?php echo htmlspecialchars($catalog['specialty_name']); ?></td>
                        <td><?php echo htmlspecialchars($catalog['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($catalog['semester']); ?></td>
                        <td><?php echo htmlspecialchars($catalog['academic_year']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div id="content">
        <h2 class='h2_element'>Реєстрація нового навчального матеріалу</h2>
        <div id="registration_material" class="card">
            <form id="add_subject_form">
                <label for="subject_name">Навчальний матеріал:</label>
                <input type="text" id="subject_name" name="subject_name" required><br><br>
                <label for="author_id">Автор:</label>
                <input type="number" id="author_id" name="author_id" required><br><br>
                <input type="submit" value="Додати навчальний матеріал">
            </form>
            <div id="subject_message" class="message"></div>
        </div>
        <h2 class='h2_element'>Реєстрація інформації в каталозі</h2>
        <div id="entering_information" class="card">
            <form id="add_catalog_form">
                <label for="year">Рік ступу студентів:</label>
                <input type="number" id="year" name="year" required><br><br>
                <label for="educational_materials_id">Назва предмету:</label>
                <input type="number" id="educational_materials_id" name="educational_materials_id" required><br><br>
                <label for="teacher_id">Викладач:</label>
                <input type="number" id="teacher_id" name="teacher_id" required><br><br>
                <label for="specialty_id">Спеціальність:</label>
                <select id="specialty_id" name="specialty_id" required>
                    <option value="">Вибір спеціальності</option>
                    <?php foreach ($specialties as $specialty) : ?>
                        <option value="<?= htmlspecialchars($specialty['specialty_id']) ?>"><?= htmlspecialchars($specialty['specialty_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="subject_code">Код групи:</label>
                <input type="text" id="subject_code" name="subject_code" required><br><br>
                <label for="semester">Семестр:</label>
                <input type="number" id="semester" name="semester" required><br><br>
                <label for="academic_year">Рік викладання:</label>
                <input type="number" id="academic_year" name="academic_year" required><br><br>
                <input type="submit" value="Додати в каталог">
            </form>
            <div id="catalog_message" class="message"></div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#add_subject_form').on('submit', function (event) {
                event.preventDefault();
                $.ajax({
                    url: 'add_subject.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        $('#subject_message').text(response);
                    },
                    error: function () {
                        $('#subject_message').text('Error adding educational material.');
                    }
                });
            });

            $('#add_catalog_form').on('submit', function (event) {
                event.preventDefault();
                $.ajax({
                    url: 'add_catalog.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        $('#catalog_message').text(response);
                    },
                    error: function () {
                        $('#catalog_message').text('Error adding catalog entry.');
                    }
                });
            });
        });
    </script>
</body>
</html>
