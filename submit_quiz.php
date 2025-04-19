<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Kết nối cơ sở dữ liệu
$pdo = new PDO("mysql:host=localhost;dbname=webdev", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Nhận dữ liệu từ form
$answers = $_POST['answers'];

// Kiểm tra câu trả lời
$correctAnswers = 0;
$questionDetails = [];

foreach ($answers as $questionId => $userAnswer) {
    $query = "SELECT * FROM quiz_questions WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$questionId]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    $isCorrect = ($userAnswer === $question['correct_answer']);
    if ($isCorrect) {
        $correctAnswers++;
    }

    $questionDetails[] = [
        'id' => $questionId,
        'question' => $question['question'],
        'options' => [
            'a' => $question['option_a'],
            'b' => $question['option_b'],
            'c' => $question['option_c'],
            'd' => $question['option_d']
        ],
        'correct_answer' => $question['correct_answer'],
        'user_answer' => $userAnswer,
        'is_correct' => $isCorrect,
        'explanation' => $question['explanation'] ?? 'Không có giải thích'
    ];
}

// Trả về kết quả
echo json_encode([
    'success' => true,
    'correct' => $correctAnswers,
    'total' => count($answers),
    'passed' => ($correctAnswers === count($answers)),
    'questions' => $questionDetails
]);

exit;
