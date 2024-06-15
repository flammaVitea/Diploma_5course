<?php
session_start();

// Завершення сесії
session_unset(); // Видалення всіх змінних сесії
session_destroy(); // Завершення сесії

// Перенаправлення на сторінку входу
header("Location: index.php");
exit;
?>
