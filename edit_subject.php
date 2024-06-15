<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject</title>
    <link rel="stylesheet" href="Style/style_edit_subject.css?v=20240606">
</head>
<body>
    <div class="container">
    <?php
    // Підключення до бази даних
    $servername = "localhost";
    $username = "root";
    $password = "bogdan09";
    $dbname = "library";

    try {
        // Підключення до бази даних
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Отримання ID навчального матеріалу
        if (isset($_GET['educational_material_id'])) {
            $educational_material_id = $_GET['educational_material_id'];

            // SQL-запит для отримання інформації про предмет для редагування та теми
            $sql = "SELECT EM.educational_materials_name, ET.subject_name, ET.number_of_lessons, ET.number_of_lectures, ET.number_of_practical, ET.number_of_tests, ET.educational_material_topics_id
                    FROM EducationalMaterials EM
                    INNER JOIN EducationalMaterialTopics ET ON EM.educational_materials_id = ET.educational_material_id
                    WHERE ET.educational_material_id = :educational_material_id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':educational_material_id' => $educational_material_id]);

            // Отримання назви предмету
            $subject_name = "";
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $subject_name = $row['educational_materials_name'];
            }

            // Виведення назви предмету
            echo "<h1>" . htmlspecialchars($subject_name) . "</h1>";

            // Перевірка наявності тем
            if ($stmt->rowCount() > 0) {
                echo "<h2>Теми:</h2>";
                echo "<table border='1' style='text-align: center'>
                        <tr>
                            <th>№</th>
                            <th>Назва теми</th>
                            <th>Кількість уроків</th>
                            <th>Кількість лекцій</th>
                            <th>Кількість практичних</th>
                            <th>Кількість тестів</th>
                        </tr>";

                $topics = []; // Масив для збереження тем
                $counter = 1; // Лічильник номерів по порядку
                $stmt->execute([':educational_material_id' => $educational_material_id]); // Повторно виконуємо запит

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $topic_id = $row['educational_material_topics_id'];
                    $topics[$topic_id] = $row['subject_name']; // Зберігаємо теми у масив

                    // Отримання кількості лекцій для кожної теми
                    $lecture_count_sql = "SELECT COUNT(*) AS lecture_count FROM Lectures WHERE educational_material_topics_id = :topic_id";
                    $lecture_count_stmt = $conn->prepare($lecture_count_sql);
                    $lecture_count_stmt->execute([':topic_id' => $topic_id]);
                    $lecture_count = $lecture_count_stmt->fetch(PDO::FETCH_ASSOC)['lecture_count'];

                    // Отримання кількості практичних для кожної теми
                    $practical_count_sql = "SELECT COUNT(*) AS practical_count FROM Practicals WHERE educational_material_topics_id = :topic_id";
                    $practical_count_stmt = $conn->prepare($practical_count_sql);
                    $practical_count_stmt->execute([':topic_id' => $topic_id]);
                    $practical_count = $practical_count_stmt->fetch(PDO::FETCH_ASSOC)['practical_count'];

                    // Отримання кількості тестів для кожної теми
                    $test_count_sql = "SELECT COUNT(*) AS test_count FROM Tests WHERE educational_material_topics_id = :topic_id";
                    $test_count_stmt = $conn->prepare($test_count_sql);
                    $test_count_stmt->execute([':topic_id' => $topic_id]);
                    $test_count = $test_count_stmt->fetch(PDO::FETCH_ASSOC)['test_count'];
                    
                    $number_of_lessons = $lecture_count + $practical_count + $test_count;

                    echo "<tr>
                            <td>$counter</td>
                            <td>" . htmlspecialchars($row['subject_name']) . "</td>
                            <td>" . htmlspecialchars($number_of_lessons) . "</td>
                            <td>" . htmlspecialchars($lecture_count) . "</td>
                            <td>" . htmlspecialchars($practical_count) . "</td>
                            <td>" . htmlspecialchars($test_count) . "</td>
                        </tr>";
                    $counter++; // Інкрементуємо лічильник
                }

                echo "</table>";
            } else {
                echo "<p>Теми відсутні.</p>";
            }
        } else {
            echo "<p>ID навчального матеріалу не задано.</p>";
        }
    } catch (PDOException $e) {
        echo "Помилка: " . $e->getMessage();
    }
    ?>

        <!-- Форма для додавання нової теми -->
        <h2>Додати нову тему</h2>
        <form action="add_topic.php" method="post">
            <label for="topic_name">Назва теми:</label>
            <input type="text" id="topic_name" name="topic_name" required><br><br>
            <input type="hidden" name="educational_material_id" value="<?php echo htmlspecialchars($educational_material_id); ?>">
            <input type="submit" value="Додати тему">
        </form>

        <h2>Додати лекцію</h2>
        <form action="add_lecture.php" method="post" enctype="multipart/form-data">
            <label for="lecture_topic">Тема лекції:</label>
            <input type="text" id="lecture_topic" name="lecture_topic" required><br><br>
            <input type="hidden" name="educational_material_id" value="<?php echo htmlspecialchars($educational_material_id); ?>">
            
            <label for="lecture_text">Текст лекції:</label>
            <textarea id="lecture_text" name="lecture_text" required style="width: 100%;"></textarea><br><br>
            
            <label for="youtube_link">Посилання на YouTube:</label>
            <input type="url" name="youtube_link" required><br><br>
            
            <label for="lecture_pdf">PDF файл:</label>
            <input type="file" id="lecture_pdf" name="lecture_pdf" accept="application/pdf"><br><br>
            
            <label for="lecture_topic_id">Виберіть тему:</label>
            <select id="lecture_topic_id" name="lecture_topic_id" required>
                <?php
                if (isset($topics)) {
                    foreach ($topics as $topic_id => $subject_name) {
                        echo "<option value=\"$topic_id\">" . htmlspecialchars($subject_name) . "</option>";
                    }
                }
                ?>
            </select><br><br>
            
            <input type="submit" value="Додати лекцію">
        </form>

        <h2>Додати практичну</h2>
        <form action="add_practical.php" method="post" enctype="multipart/form-data">
            <label for="practical_topic">Тема практичної:</label>
            <input type="text" id="practical_topic" name="practical_topic" required><br><br>
            <input type="hidden" name="educational_material_id" value="<?php echo htmlspecialchars($educational_material_id); ?>">
            <label for="practical_text">Текст практичної:</label>
            <textarea id="practical_text" name="practical_text" required></textarea><br><br>
            
            <label for="youtube_link">Посилання на YouTube:</label>
            <input type="url" name="youtube_link" required><br><br>
            
            <label for="practical_pdf">PDF файл:</label>
            <input type="file" id="practical_pdf" name="practical_pdf" accept="application/pdf"><br><br>
            
            <label for="submission_deadline">Термін виконання:</label>
            <input type="date" id="submission_deadline" name="submission_deadline" required><br><br>

            <label for="practical_topic_id">Виберіть тему:</label>
            <select id="practical_topic_id" name="practical_topic_id" required>
                <?php
                if (isset($topics)) {
                    foreach ($topics as $topic_id => $subject_name) {
                        echo "<option value=\"$topic_id\">" . htmlspecialchars($subject_name) . "</option>";
                    }
                }
                ?>
            </select><br><br>
            
            <input type="submit" value="Додати практичну">
        </form>

        <h2>Додати тест</h2>
        <form id="testForm" action="add_test.php" method="post">
            <label for="test_topic">Тема тесту:</label>
            <input type="text" id="test_topic" name="test_topic" required><br><br>

            <label for="submission_deadline">Термін виконання:</label>
            <input type="date" id="submission_deadline" name="submission_deadline" required><br><br>
            
            <label for="num_questions">Кількість питань:</label>
            <input type="number" id="num_questions" name="num_questions" min="1" required><br><br>
            
            <div id="questionsContainer"></div>
            
            <label for="test_topic_id">Виберіть тему:</label>
            <select id="test_topic_id" name="test_topic_id" required>
                <?php
                if (isset($topics)) {
                    foreach ($topics as $topic_id => $subject_name) {
                        echo "<option value=\"$topic_id\">" . htmlspecialchars($subject_name) . "</option>";
                    }
                }
                ?>
            </select><br><br>
            
            <input type="hidden" name="educational_material_id" value="<?php echo htmlspecialchars($educational_material_id); ?>">
            <input type="submit" value="Додати тест">
        </form>

        <script>
        document.getElementById('num_questions').addEventListener('change', function() {
            const numQuestions = this.value;
            const questionsContainer = document.getElementById('questionsContainer');
            questionsContainer.innerHTML = '';

            for (let i = 0; i < numQuestions; i++) {
                const questionDiv = document.createElement('div');
                
                questionDiv.innerHTML = `
                    <h3>Питання ${i + 1}</h3>
                    <label for="question_${i}">Питання:</label>
                    <input type="text" id="question_${i}" name="questions[${i}][question]" required><br><br>
                    
                    <label for="num_answers_${i}">Кількість варіантів відповіді:</label>
                    <input type="number" id="num_answers_${i}" name="questions[${i}][num_answers]" min="1" required><br><br>
                    
                    <div id="answersContainer_${i}"></div>

                    <label for="correct_answer_${i}">Правильна відповідь:</label>
                    <select id="correct_answer_${i}" name="questions[${i}][correct_answer]" required>
                        <!-- Генеруємо варіанти відповідей -->
                    </select>
                `;

                questionsContainer.appendChild(questionDiv);

                document.getElementById(`num_answers_${i}`).addEventListener('change', function() {
                    const numAnswers = this.value;
                    const answersContainer = document.getElementById(`answersContainer_${i}`);
                    answersContainer.innerHTML = '';

                    for (let j = 0; j < numAnswers; j++) {
                        const answerDiv = document.createElement('div');
                        answerDiv.innerHTML = `
                            <label for="answer_${i}_${j}">Варіант ${j + 1}:</label>
                            <input type="text" id="answer_${i}_${j}" name="questions[${i}][answers][${j}]" required><br><br>
                        `;
                        answersContainer.appendChild(answerDiv);
                    }

                    generateOptions(i, numAnswers);
                });
            }
        });

        // Функція для генерації варіантів відповіді для вибору правильної відповіді
        function generateOptions(questionIndex, numAnswers) {
            const select = document.getElementById(`correct_answer_${questionIndex}`);
            select.innerHTML = '';

            for (let i = 0; i < numAnswers; i++) {
                const option = document.createElement('option');
                option.value = i;  // Значення для порівняння з індексом відповіді
                option.textContent = `Варіант ${i + 1}`;
                select.appendChild(option);
            }
        }
        </script>
    </div>
    <?php echo '<p class="back_stor_p"><a class="back_stor" href="study_material_page.php?educational_material_id=' . $educational_material_id .' ">Повернутися на сторінку з предметом</a></p>'?>
</body>
</html>
