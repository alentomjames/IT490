// Validate Signup Form
function validateSignupForm() {
    const userId = document.forms["signupForm"]["username"].value;
    const password = document.forms["signupForm"]["password"].value;
    const confirmPassword = document.forms["signupForm"]["passwordConfirm"].value;

    if (userId === "" || password === "" || confirmPassword === "") {
        alert("All fields must be filled out.");
        return false;
    }

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    if (password.length < 6) {
        alert("Password must be at least 6 characters long.");
        return false;
    }

    return true;
}

// Validate Login Form
function validateLoginForm() {
    const userId = document.forms["loginForm"]["username"].value;
    const password = document.forms["loginForm"]["password"].value;

    if (userId === "" || password === "") {
        alert("All fields must be filled out.");
        return false;
    }

    return true;
}

// Carousel For Recommended Movies 
let carouselIndex = 0;

function moveCarousel(direction) {
    const carousel = document.querySelector('.recommendation-carousel');
    const items = document.querySelectorAll('.carousel-item');
    const itemWidth = items[0].offsetWidth + 20;  // Item width + margin
    const carouselWidth = itemWidth * items.length;
    const containerWidth = document.querySelector('.carousel-container').offsetWidth;

    carouselIndex += direction;

    // Wrapping logic to loop the carousel
    if (carouselIndex < 0) {
        carouselIndex = items.length - Math.floor(containerWidth / itemWidth);
    } else if (carouselIndex > items.length - Math.floor(containerWidth / itemWidth)) {
        carouselIndex = 0;
    }

    // Move the carousel
    carousel.style.transform = `translateX(-${carouselIndex * itemWidth}px)`;
}

// display user watchlist
document.addEventListener('DOMContentLoaded', () => {
    loadWatchlist();
});


// load watchlist and display
function loadWatchlist() {
    fetch('fetchWatchlist.php', {
        method: 'GET',
    })
        .then(response => response.json())
        .then(data => {
            const watchlistContainer = document.querySelector('.watchlist-container');
            watchlistContainer.innerHTML = '';
            console.log(data['watchlist']);
            if (data['type'] === 'success' && data['watchlist'].length > 0) {
                data['watchlist'].forEach(movieId => {
                    const item = document.createElement('div');
                    item.className = 'watchlist-item';
                    item.dataset.movieId = movieId;

                    // Fetch movie details from TMDB API
                    fetch(`getMovieDetails.php?movieId=${movieId}`)
                        .then(response => response.json())
                        .then(movie => {
                            item.innerHTML = `
                                <img src="https://image.tmdb.org/t/p/w200${movie.poster_path}" alt="${movie.title}">
                                <p>${movie.title}</p>
                                <button onclick="removeFromWatchlist(${movieId})" class="remove-button">Remove</button>
                            `;
                            watchlistContainer.appendChild(item);
                        })
                        .catch(error => console.error('Error fetching movie details:', error));
                });
            } else {
                watchlistContainer.innerHTML = '<p>Your watchlist is empty!</p>';
            }
        })
        .catch(error => console.error('Error fetching watchlist:', error));
}

// Function to add a movie to the watchlist
function addToWatchlist(movieId) {
    fetch('addToWatchlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ movie_id: movieId })
    })
        .then(response => response.json())
        .then(data => {
            if (data['type'] === 'success') {
                alert('Movie added to watchlist!');
                loadWatchlist(); // Refresh watchlist to include the new movie
            } else {
                alert('Failed to add movie to watchlist.');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Function to remove a movie from the watchlist
function removeFromWatchlist(movieId) {
    fetch('removeFromWatchlist.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ movie_id: movieId })
    })
        .then(response => response.json())
        .then(data => {
            if (data['type'] === 'success') {
                alert('Movie removed from watchlist!');
                document.querySelector(`.watchlist-item[data-movie-id="${movieId}"]`).remove();
            } else {
                alert('Failed to remove movie from watchlist.');
            }
        })
        .catch(error => console.error('Error:', error));
}


// Code for Reccomended Movies Page 

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

function changePage(direction) {
    if (direction === -1 && currentPage > 1) {
        currentPage--;
    } else if (direction === 1 && currentPage < totalPages) {
        currentPage++;
    }
    loadMovies(currentPage);
}

function setMovieRating(movieId, userId, rating) {
    if (rating < 1 || rating > 5) {
        alert("Please provide a rating between 1 and 5.");
        return;
    }

    fetch('setRating.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ movie_id: movieId, user_id: userId, rating: rating })
    })
        .then(response => response.json())
        .then(data => {
            if (data['type'] === 'success') {
                alert('Rating submitted successfully!');
            } else {
                alert(`Failed to submit rating: ${data['message']}`);
            }
        })
        .catch(error => {
            console.error('Error submitting rating:', error);
            return { error: 'Failed to submit rating' };  // Returning an error object
        });
}

// Add this script to your script.js or inline within topTenPage.php
document.addEventListener('DOMContentLoaded', loadTopTenMovies);

function loadTopTenMovies() {
    fetch('fetchTopTen.php', {
        method: 'GET',
    })
        .then(response => response.json())
        .then(data => {
            const topMoviesContainer = document.getElementById('top-movies-container');
            topMoviesContainer.innerHTML = '';

            if (data['type'] === 'success' && data['top_movies'].length > 0) {
                data['top_movies'].forEach(movie => {
                    const item = document.createElement('div');
                    item.className = 'movie-item';

                    fetch(`https://api.themoviedb.org/3/movie/${movie.movie_id}?api_key=38b40730e9d751a8d47f6e30b11ef937`)
                        .then(response => response.json())
                        .then(movieDetails => {
                            item.innerHTML = `
                                <a href="moviePage.php?id=${movie.movie_id}">
                                    <img src="https://image.tmdb.org/t/p/w200${movieDetails.poster_path}" alt="${movieDetails.title}">
                                    <p>${movieDetails.title}</p>
                                    <p class="vote-average">${Math.round(movieDetails.vote_average / 2 * 10) / 10} <i class="fa fa-star"></i></p>
                                </a>
                            `;
                            topMoviesContainer.appendChild(item);
                        })
                        .catch(error => console.error('Error fetching movie details:', error));
                });
            } else {
                topMoviesContainer.innerHTML = '<p>No top-rated movies found!</p>';
            }
        })
        .catch(error => console.error('Error fetching top movies:', error));
}

// Load recommendations and display
function loadRecommendations() {
    fetch('getRecommendations.php', {
        method: 'GET',
    })
        .then(response => response.json())
        .then(data => {
            const recommendationsContainer = document.getElementById('recommendations-container');
            recommendationsContainer.innerHTML = '';

            if (data['type'] === 'success' && data.recommendations['liked'].length > 0) {
                data.recommendations['liked'].forEach(movie => {
                    fetch(`fetchRecommendations.php?movieId=${movie}`)
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
                    recommendationsContainer.appendChild(item);
                });
            });
            } else {
                recommendationsContainer.innerHTML = '<p>No recommendations found based on your liked movies.</p>';
            }
        })
        .catch(error => console.error('Error fetching recommendations:', error));
}

document.addEventListener('DOMContentLoaded', loadRecommendations);

function loadSimilarMovies(movieId) {
    const apiKey = '38b40730e9d751a8d47f6e30b11ef937';
    fetch(`https://api.themoviedb.org/3/movie/${movieId}/similar?api_key=${apiKey}&language=en-US&page=1`, {
        method: 'GET',
    })
        .then(response => response.json())
        .then(data => {
            const similarMoviesContainer = document.getElementById('similar-movies-container');
            similarMoviesContainer.innerHTML = '';

            if (data.results && data.results.length > 0) {
                data.results.forEach(movie => {
                    const item = document.createElement('div');
                    item.className = 'movie-item';

                    fetch(`https://api.themoviedb.org/3/movie/${movie.id}?api_key=${apiKey}`)
                        .then(response => response.json())
                        .then(movieDetails => {
                            item.innerHTML = `
                                <a href="moviePage.php?id=${movie.id}">
                                    <img src="https://image.tmdb.org/t/p/w200${movieDetails.poster_path}" alt="${movieDetails.title}">
                                    <p>${movieDetails.title}</p>
                                    <p class="vote-average">${Math.round(movieDetails.vote_average / 2 * 10) / 10} <i class="fa fa-star"></i></p>
                                </a>
                            `;
                            similarMoviesContainer.appendChild(item);
                        })
                        .catch(error => console.error('Error fetching movie details:', error));
                });
            } else {
                similarMoviesContainer.innerHTML = '<p>No similar movies found!</p>';
            }
        })
        .catch(error => console.error('Error fetching similar movies:', error));
}


