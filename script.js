// Obsługa gwiazdek oceny
document.addEventListener('DOMContentLoaded', function() {
    // Funkcja do dodawania/usuwania z ulubionych
    window.toggleFavorite = function(recipeId, element) {
        fetch('ajax-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=toggle_favorite&recipe_id=' + recipeId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.classList.toggle('active');
                // Jeśli jesteśmy na stronie ulubionych, odśwież po usunięciu
                if (window.location.pathname.includes('favorites.php') && !element.classList.contains('active')) {
                    setTimeout(() => window.location.reload(), 500);
                }
            } else if (data.error === 'not_logged_in') {
                window.location.href = 'login.php';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    };

    // Funkcja do usuwania przepisu (tylko admin)
    window.deleteRecipe = function(recipeId) {
        if (confirm('Czy na pewno chcesz usunąć ten przepis?')) {
            fetch('ajax-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete_recipe&recipe_id=' + recipeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animacja znikania karty
                    const card = document.querySelector(`.recipe-card[onclick*="${recipeId}"]`);
                    if (card) {
                        card.style.transition = 'all 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            card.remove();
                            // Jeśli nie ma już żadnych przepisów, pokaż komunikat
                            const grid = document.querySelector('.recipes-grid');
                            if (grid && grid.children.length === 0) {
                                grid.innerHTML = '<p class="no-results">Nie znaleziono przepisów.</p>';
                            }
                        }, 300);
                    }
                } else {
                    alert('Wystąpił błąd podczas usuwania przepisu.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas usuwania przepisu.');
            });
        }
    };

    // Ocenianie przepisu
    const ratingContainer = document.querySelector('.rating');
    if (ratingContainer) {
        const stars = ratingContainer.querySelectorAll('.star');
        const recipeId = ratingContainer.dataset.recipeId;
        
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                const rating = index + 1;
                
                // Sprawdzanie czy użytkownik jest zalogowany
                fetch('recipe.php?id=' + recipeId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=rate&rating=' + rating
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Aktualizacja wyświetlania gwiazdek
                        stars.forEach((s, i) => {
                            if (i < rating) {
                                s.classList.add('user-rated');
                            } else {
                                s.classList.remove('user-rated');
                            }
                            
                            if (i < Math.round(data.avgRating)) {
                                s.classList.add('filled');
                            } else {
                                s.classList.remove('filled');
                            }
                        });
                        
                        // Aktualizacja liczby ocen
                        document.querySelector('.rating-count .count').textContent = data.ratingCount;
                    }
                })
                .catch(error => {
                    // Przekierowanie do logowania jeśli niezalogowany
                    window.location.href = 'login.php';
                });
            });
            
            // Efekt hover
            star.addEventListener('mouseenter', function() {
                stars.forEach((s, i) => {
                    if (i <= index) {
                        s.style.color = 'var(--light-orange)';
                    } else {
                        s.style.color = '';
                    }
                });
            });
        });
        
        ratingContainer.addEventListener('mouseleave', function() {
            stars.forEach(s => s.style.color = '');
        });
    }
    
    // Obsługa przycisku ulubionych na stronie przepisu
    const favoriteBtn = document.querySelector('.favorite-btn');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            const recipeId = this.dataset.recipeId;
            
            fetch('recipe.php?id=' + recipeId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle_favorite'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('active');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // Animacja kart przepisów
    const recipeCards = document.querySelectorAll('.recipe-card');
    recipeCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
});

// Dodanie animacji CSS
const style = document.createElement('style');
style.textContent = `
    .fade-in {
        animation: fadeIn 0.5s ease-in forwards;
        opacity: 0;
    }
    
    @keyframes fadeIn {
        to {
            opacity: 1;
        }
    }
    
    .star {
        transition: color 0.2s ease;
        cursor: pointer;
    }
    
    .star.user-rated {
        color: var(--coral) !important;
    }
    
    .favorite-btn,
    .favorite-icon {
        transition: all 0.2s ease;
    }
    
    .favorite-btn:hover,
    .favorite-icon:hover {
        transform: scale(1.2);
    }
    
    .favorite-btn.active,
    .favorite-icon.active {
        animation: pulse 0.5s ease;
    }
    
    .delete-icon {
        transition: all 0.2s ease;
    }
    
    .delete-icon:hover {
        animation: shake 0.3s ease;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }
    
    @keyframes shake {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(5deg); }
        75% { transform: rotate(-5deg); }
    }
`;
document.head.appendChild(style);