<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Відправлені тести</title>
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
    <h1>Відправлені тести</h1>
    <?php
    session_start();

    $host = 'localhost';
    $dbname = 'library';
    $username = 'root';
    $password = 'bogdan09';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;

        $stmt_userstatus = $pdo->prepare("SELECT status, user_id FROM users WHERE user_id = :user_id");
        $stmt_userstatus->bindParam(':user_id', $_SESSION['user_id']);
        $stmt_userstatus->execute();
        $user = $stmt_userstatus->fetch(PDO::FETCH_ASSOC);

        if ($test_id > 0) {
            $stmt = $pdo->prepare("
                SELECT tr.*, t.test_topic, pr.lastname, pr.firstname, pr.middlename
                FROM TestResults tr
                JOIN Tests t ON tr.test_id = t.id
                JOIN Students st ON tr.user_id = st.user_id
                JOIN Persons pr ON st.person_id = pr.person_id
                WHERE t.id = :test_id
            ");
            $stmt->bindParam(':test_id', $test_id, PDO::PARAM_INT);
            $stmt->execute();
            $test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($test_results) {
                echo "<table>
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Тема тесту</th>
                                <th>Результат</th>
                                <th>ПІБ студента</th>
                            </tr>
                        </thead>
                        <tbody>";
                $counter = 1;
                foreach ($test_results as $result) {
                    echo "<tr>
                            <td>" . $counter++ . "</td>
                            <td>" . htmlspecialchars($result['test_topic']) . "</td>
                            <td>" . $result['score'] . "</td>
                            <td>" . htmlspecialchars($result['lastname']) . " " . htmlspecialchars($result['firstname']) . " " . htmlspecialchars($result['middlename']) . "</td>
                          </tr>";
                }
                echo "  </tbody>
                    </table>";
            } else {
                echo "<p>Не знайдено жодних результатів для цього тесту.</p>";
            }
        } else {
            echo "<p>Невірний ідентифікатор тесту.</p>";
        }
        echo '<button class="button" onclick="window.history.back();">Повернутися на сторінку тестів</button>';
    } catch (PDOException $e) {
        echo "Помилка: " . $e->getMessage();
    }
    ?>
</div>
</body>
</html>
