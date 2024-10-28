<?php
// Start the session
session_start();
// Check if the user is logged in by checking if they have a session token stored in the session storage 
$loggedIn = isset($_SESSION['userID']);

require_once('/vendor/autoload.php');

// Get the movie ID from the URL
$movie_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($movie_id) {
    $client = new \GuzzleHttp\Client();

    $response = $client->request('GET', 'https://api.themoviedb.org/3/movie/' . $movie_id . '?language=en-US', [
        'headers' => [
          'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
          'accept' => 'application/json',
        ],
    ]);

    // Decode the JSON response
    $movie = json_decode($response->getBody(), true);

    // Fetch recommendations using movieID
    $recommendationResponse = $client->request('GET', 'https://api.themoviedb.org/3/movie/' . $movie_id . '/recommendations?language=en-US&page=1', [
        'headers' => [
          'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
          'accept' => 'application/json',
        ],
    ]);
    $recommendations = json_decode($recommendationResponse->getBody(), true)['results'];
    
    // Movie data
    $title = $movie['title'];
    $vote_average = round($movie['vote_average'] / 2, 1); 
    $overview = $movie['overview'];
    $poster = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
    $genres = implode(', ', array_column($movie['genres'], 'name'));
    $languages = implode(', ', array_column($movie['spoken_languages'], 'english_name'));
    $production_companies = implode(', ', array_column($movie['production_companies'], 'name'));
} else {
    echo '<p>No movie ID provided!</p>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - BreadWinners</title>
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

    <!-- Movie Content -->
    <div class="movie-page">
        <div class="movie-poster">
            <img src="<?php echo $poster; ?>" alt="<?php echo $title; ?> Poster">
        </div>
        <div class="movie-details">
            <h1><?php echo $title; ?> <span class="vote-average"> <?php echo $vote_average; ?> <i class="fa fa-star"></i> </span> </h1>
            <p><strong>Overview:</strong> <?php echo $overview; ?></p>
            <p><strong>Genres:</strong> <?php echo $genres; ?></p>
            <p><strong>Spoken Languages:</strong> <?php echo $languages; ?></p>
            <p><strong>Production Companies:</strong> <?php echo $production_companies; ?></p>
        </div>
    </div>

    <!-- Recommended Movies -->
    <div class="recommendations">
        <h2>Recommended Movies</h2>
        <div class="carousel-container">
            <button class="carousel-button prev" onclick="moveCarousel(-1)">&#10094;</button>
            <div class="recommendation-carousel">
                <?php foreach ($recommendations as $recMovie): ?>
                    <div class="carousel-item">
                        <a href="moviePage.php?id=<?php echo $recMovie['id']; ?>">
                            <img src="https://image.tmdb.org/t/p/w200<?php echo $recMovie['poster_path']; ?>" alt="<?php echo $recMovie['title']; ?> Poster">
                        </a>
                        <p><?php echo $recMovie['title']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-button next" onclick="moveCarousel(1)">&#10095;</button>
        </div>
    </div>
</body>
</html>
