<?php
session_start();
$loggedIn = isset($_SESSION['userID']);


require_once 'vendor/autoload.php';
require_once 'rabbitmq_connection.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$client = new \GuzzleHttp\Client();

$triviaJson = file_get_contents('trivia.json');
$triviaData = json_decode($triviaJson, true);

function fetchMoviePoster($movieTitle) {
    $type = 'search_movie';
    sendRequest($type, $movieTitle, 'frontendForDMZ');
    return recieveDMZ();
}

function getTriviaQuestions($triviaData, $genre) {
    $selectedTrivia = [];
    if ($genre) {
        $genreTrivia = $triviaData['movieTrivia'][array_search($genre, array_column($triviaData['movieTrivia'], 'category'))];
        if ($genreTrivia) {
            $selectedTrivia = $genreTrivia['questions'];
        }
    } else {
        foreach ($triviaData['movieTrivia'] as $category) {
            $selectedTrivia = array_merge($selectedTrivia, $category['questions']);
        }
    }
    return array_slice($selectedTrivia, 0, 10);
}

$genre = isset($_POST['genre']) ? $_POST['genre'] : '';
$selectedTrivia = getTriviaQuestions($triviaData, $genre);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Trivia - BreadWinners</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
                <li><button onclick="location.href='Reccomend.php'">Reccomended Movies</button></li>
                <li><button onclick="location.href='MovieTrivia.php'">Movie Trivia</button></li>
                <li><button onclick="location.href='watchlistPage.php'">Watch Later</button></li>
                <li><button onclick="location.href='topTen.php'">Top Movies</button></li>
                <p class="nav-title">Welcome, <?php echo $_SESSION['name']; ?>!</p>
                <li><button onclick="location.href='logout.php'">Logout</button></li>
            <?php else: ?>
                <li><button onclick="location.href='login.php'">Login</button></li>
                <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="trivia-selection">
        <h2>Select Genre or Play with All Genres</h2>
        <form method="POST" action="MovieTrivia.php">
            <select name="genre" id="genre-select">
                <option value="">All Genres</option>
                <option value="Action">Action</option>
                <option value="Adventure">Adventure</option>
                <option value="Animation">Animation</option>
                <option value="Comedy">Comedy</option>
                <option value="Crime">Crime</option>
                <option value="Documentary">Documentary</option>
                <option value="Drama">Drama</option>
                <option value="Family">Family</option>
                <option value="Fantasy">Fantasy</option>
                <option value="History">History</option>
                <option value="Horror">Horror</option>
                <option value="Music">Music</option>
                <option value="Mystery">Mystery</option>
                <option value="Romance">Romance</option>
                <option value="Science Fiction">Science Fiction</option>
                <option value="TV Movie">TV Movie</option>
                <option value="Thriller">Thriller</option>
                <option value="War">War</option>
                <option value="Western">Western</option>
            </select>
            <button type="submit">Start Trivia</button>
        </form>
    </div>

    <div id="trivia-container" class="trivia-container">
        <?php foreach ($selectedTrivia as $question): ?>
            <?php
            $movieDetails = fetchMoviePoster($question['movie']);
            echo '<pre>';
            print_r($movieDetails['data']);
            echo '</pre>';
            $posterUrl = isset($movieDetails['poster_path']) ? 'https://image.tmdb.org/t/p/w200' . $movieDetails['poster_path'] : '';
            ?>
            <div class="trivia-question">
                <img src="<?php echo $posterUrl; ?>" alt="<?php echo $question['movie']; ?> Poster">
                <p><?php echo $question['question']; ?></p>
                <ul>
                    <?php foreach ($question['options'] as $option): ?>
                        <li onclick="selectAnswer(this, '<?php echo $question['correctAnswer']; ?>')"><?php echo $option; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        let selectedAnswers = [];
        let correctAnswers = 0;

        function selectAnswer(element, correctAnswer) {
            if (element.classList.contains('selected')) return;

            element.classList.add('selected');
            element.style.backgroundColor = '#007BFF';
            element.style.color = 'white';
            element.style.pointerEvents = 'none';

            const questionElement = element.closest('.trivia-question');
            const options = questionElement.querySelectorAll('li');
            options.forEach(option => option.style.pointerEvents = 'none');

            selectedAnswers.push(element.textContent.trim());
            if (element.textContent.trim() === correctAnswer) {
                correctAnswers++;
            }

            if (selectedAnswers.length === document.querySelectorAll('.trivia-question').length) {
                setTimeout(showScore, 500);
            }
        }

        function showScore() {
            const scorePopup = document.createElement('div');
            scorePopup.classList.add('score-popup');
            scorePopup.innerHTML = `
                <div class="score-content">
                    <h2>Your Score: ${correctAnswers} / ${selectedAnswers.length}</h2>
                    <button onclick="restartTrivia()">Try Again</button>
                </div>
            `;
            document.body.appendChild(scorePopup);
        }

        function restartTrivia() {
            document.querySelector('.score-popup').remove();
            document.getElementById('trivia-container').innerHTML = '';
            document.getElementById('genre-select').value = '';
        }
    </script>
</body>
</html>