<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Відправлені практичні роботи</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        .button {
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Відправлені практичні роботи</h1>
    <?php
    session_start();

    $host = 'localhost';
    $dbname = 'library';
    $username = 'root';
    $password = 'bogdan09';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $practical_id = isset($_GET['practical_id']) ? intval($_GET['practical_id']) : 0;

        $stmt_userstatus = $pdo->prepare("SELECT status, user_id FROM users WHERE user_id = :user_id");
        $stmt_userstatus->bindParam(':user_id', $_SESSION['user_id']);
        $stmt_userstatus->execute();
        $user = $stmt_userstatus->fetch(PDO::FETCH_ASSOC);

        if ($practical_id > 0) {
            $stmt = $pdo->prepare("
                SELECT s.*, p.practical_topic, pr.lastname, pr.firstname, pr.middlename
                FROM Submissions s
                JOIN Practicals p ON s.practical_id = p.practical_id
                JOIN Students st ON s.user_id = st.user_id
                JOIN Persons pr ON st.person_id = pr.person_id
                WHERE s.practical_id = :practical_id
                ORDER BY s.uploaded_at
            ");
            $stmt->bindParam(':practical_id', $practical_id, PDO::PARAM_INT);
            $stmt->execute();
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($submissions) {
                echo "<table>
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Тема практичного</th>
                                <th>Назва файлу</th>
                                <th>Час завантаження</th>
                                <th>Посилання</th>
                                <th>ПІБ студента</th>
                            </tr>
                        </thead>
                        <tbody>";
                $counter = 1;
                foreach ($submissions as $submission) {
                    echo "<tr>
                            <td>" . $counter++ . "</td>
                            <td>" . htmlspecialchars($submission['practical_topic']) . "</td>
                            <td>" . htmlspecialchars($submission['file_name']) . "</td>
                            <td>" . htmlspecialchars($submission['uploaded_at']) . "</td>
                            <td><a href='" . htmlspecialchars($submission['file_path']) . "' target='_blank'>Завантажити</a></td>
                            <td>" . htmlspecialchars($submission['lastname']) . " " . htmlspecialchars($submission['firstname']) . " " . htmlspecialchars($submission['middlename']) . "</td>
                          </tr>";
                }
                echo "  </tbody>
                    </table>";
                
            } else {
                echo "<p>Не знайдено жодних поданих робіт.</p>";
            }
        } else {
            echo "<p>Невірний ідентифікатор практичної роботи.</p>";
        }
        echo '<button class="button" onclick="window.history.back();">Повернутися на сторінку завдань</button>';
    } catch (PDOException $e) {
        echo "Помилка: " . $e->getMessage();
    }
    ?>
</div>
</body>
</html>
