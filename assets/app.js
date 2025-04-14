import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

document.addEventListener('DOMContentLoaded', function () {
    // Gestion des likes
    document.querySelectorAll('.like-button').forEach(button => {
        button.addEventListener('click', async function () {
            const photoId = this.dataset.photoId;

            try {
                const response = await fetch(`/like/${photoId}`, {
                    method: 'POST', // Correction : utilisation de POST au lieu de GET
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok) {
                    const data = await response.json();
                    const icon = this.querySelector('.fa-heart');
                    const count = this.querySelector('.like-count');

                    if (data.message === 'Like ajouté') {
                        this.classList.remove('text-gray-600', 'dark:text-gray-400');
                        this.classList.add('text-pink-600', 'dark:text-pink-500');
                        icon.classList.add('like-animation');
                    } else if (data.message === 'Like retiré') {
                        this.classList.remove('text-pink-600', 'dark:text-pink-500');
                        this.classList.add('text-gray-600', 'dark:text-gray-400');
                    }

                    count.textContent = data.likesCount;
                    setTimeout(() => icon.classList.remove('like-animation'), 500);
                } else {
                    console.error('Erreur lors de la gestion du like:', response.statusText);
                }
            } catch (error) {
                console.error('Erreur lors de la gestion du like:', error);
            }
        });
    });
});

