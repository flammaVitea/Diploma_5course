<?php
session_start();

$host = 'localhost';
$dbname = 'library';
$username = 'root';
$password = 'bogdan09';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $educational_material_id = isset($_SESSION['educational_material_id']) ? $_SESSION['educational_material_id'] : null;
    $educational_material_topics_id = isset($_GET['educational_material_topics_id']) ? intval($_GET['educational_material_topics_id']) : null;

    $stmt = $pdo->prepare("SELECT educational_materials_name FROM EducationalMaterials WHERE educational_materials_id = :educational_material_id");
    $stmt->bindParam(':educational_material_id', $educational_material_id);
    $stmt->execute();
    $subject_info = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt_userstatus = $pdo->prepare("SELECT status FROM users WHERE user_id = :user_id");
    $stmt_userstatus->bindParam(':user_id', $_SESSION['user_id']);
    $stmt_userstatus->execute();
    $user = $stmt_userstatus->fetch(PDO::FETCH_ASSOC);

    if ($educational_material_topics_id > 0) {
        $stmt = $pdo->prepare("SELECT subject_name FROM EducationalMaterialTopics WHERE educational_material_topics_id = :id");
        $stmt->bindParam(':id', $educational_material_topics_id, PDO::PARAM_INT);
        $stmt->execute();
        $topic = $stmt->fetch(PDO::FETCH_ASSOC);
        $topicName = $topic ? $topic['subject_name'] : "Тема не знайдена";
    } else {
        $topicName = "Тема";
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
    <title><?php echo htmlspecialchars($topicName); ?></title>
    <link rel="stylesheet" href="Style/style_lessons.css?1">
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($topicName); ?></h1>
    </header>
    <main>
        <nav>
            <section class="lectures">
                <h2>Лекції</h2>
                <ul>
                    <?php
                    try {
                        if ($educational_material_topics_id > 0) {
                            $stmt = $pdo->prepare("SELECT lecture_id, lecture_topic FROM Lectures WHERE educational_material_topics_id = :id");
                            $stmt->bindParam(':id', $educational_material_topics_id, PDO::PARAM_INT);
                            $stmt->execute();
                            $rowCount = $stmt->rowCount();
                            if ($rowCount > 0) {
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<li onclick=\"showContent('lectures', {$row['lecture_id']}); return false;\">{$row['lecture_topic']}</li>";
                                }
                            } else {
                                echo "<p>Немає лекцій для цієї теми.</p>";
                            }
                        } else {
                            echo "<p>Немає лекцій для цієї теми.</p>";
                        }
                    } catch (PDOException $e) {
                        echo "Connection failed: " . $e->getMessage();
                    }
                    ?>
                </ul>
            </section>
            <section class="practicals">
                <h2>Практичні завдання</h2>
                <ol>
                    <?php
                    try {
                        if ($educational_material_topics_id > 0) {
                            $stmt = $pdo->prepare("SELECT practical_id, practical_topic, submission_deadline FROM Practicals WHERE educational_material_topics_id = :id");
                            $stmt->bindParam(':id', $educational_material_topics_id, PDO::PARAM_INT);
                            $stmt->execute();

                            $rowCount = $stmt->rowCount();
                            if ($rowCount > 0) {
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $submission_deadline = date("d.m.Y", strtotime($row['submission_deadline']));
                                    $currentDate = date("Y-m-d H:i:s");
                                    $disabled = ($currentDate > $row['submission_deadline']) ? 'disabled' : '';
                                    echo "<li onclick=\"showContent('practicals', {$row['practical_id']}); return false;\">{$row['practical_topic']}</li>";
                                    echo "<p>Здати до $submission_deadline</p>";
                                }
                            } else {
                                echo "<p>Немає практичних завдань для цієї теми.</p>";
                            }
                        } else {
                            echo "<p>Немає практичних завдань для цієї теми.</p>";
                        }
                    } catch (PDOException $e) {
                        echo "Connection failed: " . $e->getMessage();
                    }
                    ?>
                </ol>
            </section>
            <section class="tests">
                <h2>Тести</h2>
                <ol>
                    <?php
                    try {
                        if (isset($_GET['id'])) {
                            $_SESSION['test_id'] = $_GET['id']; // зберігання id тесту у сесії
                        }
                        if ($educational_material_topics_id > 0) {
                            $stmt = $pdo->prepare("SELECT id, test_topic, submission_deadline FROM Tests WHERE educational_material_topics_id = :id");
                            $stmt->bindValue(':id', $educational_material_topics_id, PDO::PARAM_INT);
                            $stmt->execute();

                            $rowCount = $stmt->rowCount();
                            if ($rowCount > 0) {
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $submission_deadline_test = date("d.m.Y", strtotime($row['submission_deadline']));
                                    echo "<li onclick=\"showTest({$row['id']}); return false;\">{$row['test_topic']}</li>";
                                    echo "<p>Здати до $submission_deadline_test</p>";
                                }
                            } else {
                                echo "<p>Немає тестів для цієї теми.</p>";
                            }
                        } else {
                            echo "<p>Немає тестів для цієї теми.</p>";
                        }
                    } catch (PDOException $e) {
                        echo "Помилка: " . $e->getMessage();
                    }
                    ?>
                </ol>
            </section>
            <?php echo '<p class="back_stor"><a style="text-decoration: none; color:#000;" href="study_material_page.php?educational_material_id=' . $educational_material_id .' ">Повернутися на сторінку з предметом</a></p>'?>
        </nav>
        <aside id="contentDisplay" class="contentDisplay"></aside>
    </main>

    <script>
        function showContent(type, id) {
            const xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const contentDisplay = document.getElementById('contentDisplay');
                    contentDisplay.innerHTML = this.responseText;
                    contentDisplay.style.display = 'block';
                }
            };

            if (type && id) {
                xhttp.open("GET", `get_content.php?type=${type}&id=${id}`, true);
                xhttp.send();
            } else {
                console.error("Не вдалося отримати тип та ідентифікатор контенту.");
            }
        }

        function showTest(testId) {
            const xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const contentDisplay = document.getElementById('contentDisplay');
                    contentDisplay.innerHTML = this.responseText;
                    contentDisplay.style.display = 'block';
                } else if (this.readyState == 4) {
                    console.error("Помилка завантаження тесту.");
                }
            };

            xhttp.open("GET", `get_test_content.php?test_id=${testId}`, true);
            xhttp.send();
        }

        document.addEventListener('click', function(event) {
            const contentDisplay = document.getElementById('contentDisplay');
            if (!contentDisplay.contains(event.target) && event.target.tagName !== 'LI') {
                contentDisplay.style.display = 'none';
            }
        });
    </script>
</body>
</html>