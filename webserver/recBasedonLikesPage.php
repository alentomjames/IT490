<?php
session_start();
$loggedIn = isset($_SESSION['userID']);
$userName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

// if (!$loggedIn) {
//     header('Location: login.php');
//     exit();
// }
require_once 'rabbitmq_connection.php';
require_once 'vendor/autoload.php';
function getMovieDetails($movieId) {


    $type = 'movie_details';
    sendRequest($type, $movieId, 'frontendForDMZ');

    $movie = recieveDMZ();

    if ($movie) {
        return $movie;
    } else {
        return null;
    }
}

function getRecommendations($movieId) {
    $type = 'recommendations';
    sendRequest($type, $movieId, 'frontendForDMZ');

    $recommendationsData = recieveDMZ();

    if ($recommendationsData) {
        return isset($recommendationsData['results'][0]) ? $recommendationsData['results'][0] : null;
    } else {
        return [];
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommendations</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
                <li>
                    <button onclick="location.href='Reccomend.php'" class="smoothie-button">
                        <img src="smoothie.png" alt="Movie Smoothie" class="smoothie-icon">
                    </button>
                </li>
                <li><button onclick="location.href='recBasedonLikesPage.php'">Recommended Movies</button></li>
                <li><button onclick="location.href='MovieTrivia.php'">Movie Trivia</button></li>
                <li><button onclick="location.href='watchlistPage.php'">Watch Later</button></li>
                <li><button onclick="location.href='topTen.php'">Top Movies</button></li>
                <p class="nav-title">Welcome, <?php echo htmlspecialchars($userName); ?>!</p>
                <li><button onclick="location.href='logout.php'">Logout</button></li>
            <?php else: ?>
                <li><button onclick="location.href='login.php'">Login</button></li>
                <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </nav>

    <h1>These are your liked movies:</h1>
    <div class="liked-container" id="liked-movies-container">
        <p>Loading your recommended movies...</p>
    </div>
    <h1>Based on your likes, you might enjoy:</h1>
    <div class="recommendations-container" id="recommendations-container">
        <p>Loading recommendations...</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetchLikedMovies();
        });

        function fetchLikedMovies() {
            fetch('fetchLikedMovies.php')
                .then(response => response.json())
                .then(data => {
                    if (data.type === 'success') {
                        displayLikedMovies(data.liked);
                    } else {
                        document.getElementById('liked-movies-container').innerHTML = '<p>' + data.message + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching liked movies:', error);
                    document.getElementById('liked-movies-container').innerHTML = '<p>Error loading liked movies.</p>';
                });
        }


        function displayLikedMovies(movies) {
            const container = document.getElementById('liked-movies-container');
            container.innerHTML = '';

            if (movies.length === 0) {
                container.innerHTML = '<p>Your liked movies list is empty!</p>';
                return;
            }

            movies.forEach(movie => {
                const movieItem = document.createElement('div');
                movieItem.classList.add('movie-item');
                movieItem.innerHTML = `<p>Movie ID: ${movie}</p>`;
                getMovieDetails(movie).then(movieDetails => {
                    if (movieDetails) {
                        const movieTitle = movieDetails.title;
                        const moviePoster = movieDetails.poster_path;
                        movieItem.innerHTML = `<a href="moviePage.php?id=${movie}"><img src="https://image.tmdb.org/t/p/w200${moviePoster}" alt="${movieTitle} Poster"><p>${movieTitle}</p></a>`;
                    } else {
                        movieItem.innerHTML = `<p>Movie ID: ${movie}</p>`;
                    }
                });
                container.appendChild(movieItem);
            });
        }
        function fetchRecommendations() {
            fetch('fetchRecommendations.php')
                .then(response => response.json())
                .then(data => {
                    if (data.type === 'success') {
                        displayRecommendations(data.recommendations);
                    } else {
                        document.getElementById('recommendations-container').innerHTML = '<p>' + data.message + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching recommendations:', error);
                    document.getElementById('recommendations-container').innerHTML = '<p>Error loading recommendations.</p>';
                });
        }

        function displayRecommendations(recommendations) {
            const container = document.getElementById('recommendations-container');
            container.innerHTML = '';

            if (recommendations.length === 0) {
                container.innerHTML = '<p>No recommendations available.</p>';
                return;
            }

            recommendations.forEach(movie => {
                const movieItem = document.createElement('div');
                movieItem.classList.add('movie-item');
                movieItem.innerHTML = `<p>Movie ID: ${movie.id}</p>`;
                getMovieDetails(movie.id).then(movieDetails => {
                    if (movieDetails) {
                        const movieTitle = movieDetails.title;
                        const moviePoster = movieDetails.poster_path;
                        movieItem.innerHTML = `<a href="moviePage.php?id=${movie.id}"><img src="https://image.tmdb.org/t/p/w200${moviePoster}" alt="${movieTitle} Poster"><p>${movieTitle}</p></a>`;
                    } else {
                        movieItem.innerHTML = `<p>Movie ID: ${movie.id}</p>`;
                    }
                });
                container.appendChild(movieItem);
            });
        }
    </script>
</body>
</html>