CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    movie_id INT NOT NULL,
    user_id INT NOT NULL,
    username VARCHAR(255),
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);