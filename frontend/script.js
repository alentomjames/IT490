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
                    fetch(`https://api.themoviedb.org/3/movie/${movieId}?api_key=38b40730e9d751a8d47f6e30b11ef937`)
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
            if (data.success) {
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
                alert(`Failed to submit rating: ${data['reason']}`);
            }
        })
        .catch(error => console.error('Error submitting rating:', error));
}

// event listener for rating buttons
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.rate-button').forEach(button => {
        button.addEventListener('click', event => {
            const movieId = event.target.dataset.movieId;
            const userId = event.target.dataset.userId;
            const rating = parseInt(event.target.dataset.rating);

            setMovieRating(movieId, userId, rating);
        });
    });
});

