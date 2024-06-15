<?php
session_start();

// Функція підключення до бази даних за допомогою PDO
function connectToDB() {
    $db_host = "localhost";
    $db_user = "root";
    $db_password = "bogdan09";
    $db_name = "library";

    try {
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Функція перевірки логіну користувача
function loginUser($conn, $email, $password) {
    $sql = "SELECT user_id, email, password, status FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($password, $row['password'])) {
        return $row;
    }
    return false;
}

// Функція отримання даних користувача з бази даних за його ідентифікатором
function getUserData($conn, $user_id, $status) {
    $table = ($status == 'student') ? 'Students' : 'Teachers';
    $sql = "SELECT * FROM $table WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Обробник відправки форми логіну
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $conn = connectToDB();

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Перевірка, чи електронна адреса відноситься до вказаного домену
    $allowed_domain = 'vpu21.if.ua';
    $email_domain = substr(strrchr($email, "@"), 1);
    if ($email_domain !== $allowed_domain) {
        $error = "Доступ обмежено. Ви можете увійти тільки з електронною адресою з домену $allowed_domain";
    } else {
        $user = loginUser($conn, $email, $password);

        if($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $user_data = getUserData($conn, $user['user_id'], $user['status']);
            $_SESSION['user_data'] = $user_data;

            if ($user['status'] == 'student') {
                header("Location: student_dashboard.php"); // Перенаправлення на сторінку студента
            } elseif ($user['status'] == 'teacher') {
                header("Location: teacher_dashboard.php"); // Перенаправлення на сторінку викладача
            } elseif ($user['status'] == 'administrator') {
                header("Location: admin.php"); // Перенаправлення на сторінку адміністратора
            }
            exit;
        } else {
            $error = "Неправильна пошта або пароль";
        }
    }

    $conn = null; // Закриваємо з'єднання з базою даних
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Style/style.css?v=202403301907">
    <title>Login</title>
</head>
<body>
    <div class="container">   
        <header>
            <img src="img/img.jpg" alt="Logo">
            <h1 style="text-align: center; padding-right: 1rem;">Бібліотека</h1>
        </header>
        <section>
            <h2>Вхід</h2>
            <?php
            if(isset($error)) {
                echo '<p class="error">' . $error . '</p>';
            }
            ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div>
                    <label for="email">Пошта:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div>
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login">Увійти</button>
            </form>
        </section>
    </div>
    <footer>
    <p style = "text-align: center;">| Виконав: Павлюк Богдан | Теми: "Бібліотека навчальних матеріалів" |</p>
    </footer>
</html>
