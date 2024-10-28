<?php
session_start();
$loggedIn = isset($_SESSION['userID']);

require_once('vendor/autoload.php');

$client = new \GuzzleHttp\Client();

$triviaJson = file_get_contents('trivia.json');
$triviaData = json_decode($triviaJson, true);
?>

<!DOCTYPE html
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Trivia - BreadWinners</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="script.js" defer></script>
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
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
        <select id="genre-select">
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
        <button onclick="startTrivia()">Start Trivia</button>
    </div>

    <div id="trivia-container" class="trivia-container"></div>

    <script>
        const triviaData = <?php echo json_encode($triviaData); ?>;
        let selectedAnswers = [];
        let correctAnswers = 0;

        async function fetchMoviePoster(movieTitle) {
            const response = await fetch(`https://api.themoviedb.org/3/search/movie?query=${encodeURIComponent(movieTitle)}&include_adult=false&language=en-US&page=1`, {
                headers: {
                    'Authorization': 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
                    'accept': 'application/json',
                }
            });
            const data = await response.json();
            return data.results[0]?.poster_path ? `https://image.tmdb.org/t/p/w200${data.results[0].poster_path}` : '';
        }

        async function startTrivia() {
            const genre = document.getElementById('genre-select').value;
            const triviaContainer = document.getElementById('trivia-container');
            triviaContainer.innerHTML = '';
            selectedAnswers = [];
            correctAnswers = 0;

            let selectedTrivia = [];
            if (genre) {
                const genreTrivia = triviaData.movieTrivia.find(category => category.category === genre);
                if (genreTrivia) {
                    selectedTrivia = genreTrivia.questions;
                }
            } else {
                triviaData.movieTrivia.forEach(category => {
                    selectedTrivia = selectedTrivia.concat(category.questions);
                });
            }

            selectedTrivia = selectedTrivia.sort(() => 0.5 - Math.random()).slice(0, 10);

            for (const question of selectedTrivia) {
                const posterUrl = await fetchMoviePoster(question.movie);
                const questionElement = document.createElement('div');
                questionElement.classList.add('trivia-question');
                questionElement.innerHTML = `
                    <img src="${posterUrl}" alt="${question.movie} Poster">
                    <p>${question.question}</p>
                    <ul>
                        ${question.options.map(option => `<li onclick="selectAnswer(this, '${question.correctAnswer}')">${option}</li>`).join('')}
                    </ul>
                `;
                triviaContainer.appendChild(questionElement);
            }
        }

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