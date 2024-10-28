<?php
session_start();
$loggedIn = isset($_SESSION['userID']);

require_once 'vendor/autoload.php';  

$client = new \GuzzleHttp\Client();

$response = $client->request('GET', 'https://api.themoviedb.org/3/discover/movie?include_adult=false&include_video=false&language=en-US&sort_by=popularity.desc', [
    'headers' => [
        'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
        'accept' => 'application/json',
    ],
]);

$movies = json_decode($response->getBody(), true)['results']; 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Recommendations - BreadWinners</title>
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
        <h2>Select Your 3 Favorite Movies</h2>
        <div class="favorites-container" id="movies-container">
        </div>
    </div>

    <div class="pagination-controls">
        <button id="prev-page" onclick="changePage(-1)">Previous</button>
        <span id="current-page">1</span>
        <button id="next-page" onclick="changePage(1)">Next</button>
    </div>

    <div class="user-favorites">
        <h2>Your Favorite Movies</h2>
        <ul id="favorite-movies-list"></ul>
    </div>

    <div class="recommendations">
        <h2>Here is What We Recommend</h2>
        <div id="recommendation-results"></div>
    </div>

    <script>
        let favoriteMovies = [];
        let currentPage = 1;
        let totalPages = 1;
        let allMovies = [];
        let searchQuery = '';

        document.addEventListener('DOMContentLoaded', () => {
            loadMovies(currentPage);
        });

        function loadMovies(page) {
            fetch(`https://api.themoviedb.org/3/discover/movie?include_adult=false&include_video=false&language=en-US&sort_by=popularity.desc&page=${page}`, {
                headers: {
                    'Authorization': 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
                    'accept': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    totalPages = data.total_pages;
                    allMovies = data.results;
                    displayMovies(allMovies);
                    document.getElementById('current-page').textContent = currentPage;
                });
        }

        function displayMovies(movies) {
            const moviesContainer = document.getElementById('movies-container');
            moviesContainer.innerHTML = '';

            movies.forEach(movie => {
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
            });
        }

        function addFavorite(movieId, movieTitle) {
            if (favoriteMovies.length < 3 && !favoriteMovies.includes(movieId)) {
                favoriteMovies.push(movieId);
                const listItem = document.createElement('li');
                listItem.textContent = movieTitle;
                document.getElementById('favorite-movies-list').appendChild(listItem);

                const movieItem = document.querySelector(`.favorite-item[data-id="${movieId}"]`);
                if (movieItem) {
                    movieItem.classList.add('selected');
                }

                if (favoriteMovies.length === 3) {
                    fetchRecommendations();
                }
            }
        }

        function fetchRecommendations() {
            const recommendationResults = document.getElementById('recommendation-results');
            recommendationResults.innerHTML = '';

            favoriteMovies.forEach(movieId => {
                fetch(`https://api.themoviedb.org/3/movie/${movieId}/recommendations?language=en-US&page=1`, {
                    headers: {
                        'Authorization': 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
                        'accept': 'application/json',
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        const firstRecommendation = data.results[0];
                        const recommendationItem = document.createElement('div');
                        recommendationItem.classList.add('recommendation-item');
                        recommendationItem.innerHTML = `
                        <a href="moviePage.php?id=${firstRecommendation.id}">
                            <img src="https://image.tmdb.org/t/p/w200${firstRecommendation.poster_path}" alt="${firstRecommendation.title} Poster">
                        </a>
                        <p>${firstRecommendation.title}</p>
                    `;
                        recommendationResults.appendChild(recommendationItem);
                    });
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
            fetch(`https://api.themoviedb.org/3/search/movie?query=${query}&include_adult=false&language=en-US&page=1`, {
                headers: {
                    'Authorization': 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
                    'accept': 'application/json',
                }
            })
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