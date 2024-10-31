<script>
    document.addEventListener('DOMContentLoaded', function() {
        const options = document.querySelectorAll('.trivia-question li');
        options.forEach(option => {
            option.addEventListener('click', function() {
                selectAnswer(this);
            });
        });
    });

    let selectedAnswers = [];
    let correctAnswers = 0;

    function selectAnswer(element) {
        const questionElement = element.closest('.trivia-question');
        const correctAnswer = questionElement.querySelector('li[data-correct-answer]').getAttribute('data-correct-answer');

        element.classList.add('selected');
        element.style.backgroundColor = '#007BFF';
        element.style.color = 'white';
        element.style.pointerEvents = 'none';

        const options = questionElement.querySelectorAll('li');
        options.forEach(option => option.style.pointerEvents = 'none');

        selectedAnswers.push(element.textContent.trim());
        if (element.textContent.trim() === correctAnswer) {
            correctAnswers++;
        }

        const totalQuestions = document.querySelectorAll('.trivia-question').length;
        if (selectedAnswers.length === totalQuestions) {
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