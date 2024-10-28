function addToWatchlist(movieId) {
    fetch('addToWatchlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ movieId: movieId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Movie added to watchlist!');
            } else {
                alert('Failed to add movie to watchlist.');
            }
        })
        .catch(error => console.error('Error:', error));
}

function removeFromWatchlist(movieId) {
    fetch('watchlistPage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'remove',
            movie_id: movieId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // refresh to update watchlist
            } else {
                alert('Failed to remove movie from watchlist.');
            }
        })
        .catch(error => console.error('Error:', error));
}

