<?php
session_start();
$loggedIn = isset($_SESSION['userID']);


require_once('vendor/autoload.php');
require_once 'rabbitmq_connection.php';

$client = new \GuzzleHttp\Client();

$trending = fetchTrending();
function fetchTrending()
{
    $type = 'trending_movies';
    sendRequest($type, 'day', 'frontendForDMZ');
    return recieveDMZ();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BreadWinners</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
                <li><button onclick="location.href='Reccomend.php'">Reccomended Movies</button></li>
                <li><button onclick="location.href='MovieTrivia.php'">Movie Trivia</button></li>
                <li><button onclick="location.href='watchlistPage.php'">Watch Later</button></li>
                <li><button onclick="location.href='topTenPage.php'">Top Movies</button></li>
                <!-- If they are logged in then display a "Welcome [user]" text at the top where the buttons would usually be and a logout button --->
                <p class="nav-title">Welcome, <?php echo $_SESSION['name']; ?>!</p>
                <!-- Logout button that calls logout.php to delete the userID from session and redirects them to the login page --->
                <li><button onclick="location.href='logout.php'">Logout</button></li>
            <?php else: ?>
                <!-- If they aren't logged in then display the buttons for login or sign up on the navbar --->

                <li><button onclick="location.href='login.php'">Login</button></li>
                <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="welcome-message">
        <h1>Hello, <?php echo $_SESSION['name']; ?></h1>
        <p>Welcome to BreadWinners. Here are the top 10 trending movies:</p>
    </div>

    <div class="trending-movies">
        <?php foreach (array_slice($trending['results'], 0, 10) as $movie): ?>
            <div class="movie-item">
                <a href="moviePage.php?id=<?php echo $movie['id']; ?>">
                    <img src="https://image.tmdb.org/t/p/w200<?php echo $movie['poster_path']; ?>" alt="<?php echo $movie['title']; ?> Poster">
                    <p><?php echo $movie['title']; ?></p>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="filters">
        <input type="text" id="search-bar" placeholder="Search for movies..." oninput="filterMovies()">
        <select id="genre-filter" onchange="filterMovies()">
            <option value="">All Genres</option>
            <option value="28">Action</option>
            <option value="12">Adventure</option>
            <option value="16">Animation</option>
            <option value="35">Comedy</option>
            <option value="80">Crime</option>
            <option value="99">Documentary</option>
            <option value="18">Drama</option>
            <option value="10751">Family</option>
            <option value="14">Fantasy</option>
            <option value="36">History</option>
            <option value="27">Horror</option>
            <option value="10402">Music</option>
            <option value="9648">Mystery</option>
            <option value="10749">Romance</option>
            <option value="878">Science Fiction</option>
            <option value="10770">TV Movie</option>
            <option value="53">Thriller</option>
            <option value="10752">War</option>
            <option value="37">Western</option>
        </select>
    </div>

    <div class="favorites-selection">
        <h2>All Movies</h2>
        <div class="favorites-container" id="movies-container">
        </div>
    </div>

    <div class="pagination-controls">
        <button id="prev-page" onclick="changePage(-1)">Previous</button>
        <span id="current-page">1</span>
        <button id="next-page" onclick="changePage(1)">Next</button>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let allMovies = [];
        let searchQuery = '';

        document.addEventListener('DOMContentLoaded', () => {
            loadMovies(currentPage);
        });

        function loadMovies(page) {
            fetch(`loadMovies.php?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    totalPages = data.total_pages;
                    allMovies = data.results.filter(movie => movie.poster_path);
                    displayMovies(allMovies);
                    document.getElementById('current-page').textContent = currentPage;
                });
        }

        function displayMovies(movies) {
            const moviesContainer = document.getElementById('movies-container');
            moviesContainer.innerHTML = '';

            movies.forEach(movie => {
                if (movie.poster_path) {
                    const movieItem = document.createElement('div');
                    movieItem.classList.add('favorite-item');
                    movieItem.setAttribute('data-id', movie.id);
                    movieItem.setAttribute('data-title', movie.title.toLowerCase());
                    movieItem.setAttribute('data-genres', movie.genre_ids.join(','));
                    movieItem.onclick = () => addFavorite(movie.id, movie.title);

                    movieItem.innerHTML = `
                    <img src="https://image.tmdb.org/t/p/w200${movie.poster_path}" alt="${movie.title} Poster">
                    <p>${movie.title}</p>
                `;

                    moviesContainer.appendChild(movieItem);
                }
            });
        }

        function filterMovies() {
            searchQuery = document.getElementById('search-bar').value.toLowerCase();
            const genreFilter = document.getElementById('genre-filter').value;

            if (searchQuery) {
                searchMovies(searchQuery, genreFilter);
            } else {
                let filteredMovies = allMovies;

                if (genreFilter) {
                    filteredMovies = filteredMovies.filter(movie => movie.genre_ids.includes(parseInt(genreFilter)));
                }

                displayMovies(filteredMovies);
            }
        }

        function searchMovies(query, genreFilter) {
            fetch(`searchMovies.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    let filteredMovies = data.results;

                    if (genreFilter) {
                        filteredMovies = filteredMovies.filter(movie => movie.genre_ids.includes(parseInt(genreFilter)));
                    }

                    displayMovies(filteredMovies);
                });
        }

        function changePage(direction) {
            if (direction === -1 && currentPage > 1) {
                currentPage--;
            } else if (direction === 1 && currentPage < totalPages) {
                currentPage++;
            }
            loadMovies(currentPage);
        }
    </script>
</body>

</html>