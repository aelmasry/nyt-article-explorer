// Author: Ali Salem <admin@alisalem.me>

// Global variables
let currentPage = 0;
let totalPages = 0;
let currentSearchQuery = '';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user'));

// API base URL
const API_BASE_URL = '/api';

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    updateAuthUI();
    if (token) {
        loadFavorites();
    }
});

// Authentication functions
function showLoginModal() {
    const modal = new bootstrap.Modal(document.getElementById('loginModal'));
    modal.show();
}

function showRegisterModal() {
    const modal = new bootstrap.Modal(document.getElementById('registerModal'));
    modal.show();
}

async function login() {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    try {
        const response = await fetch(`${API_BASE_URL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
            token = data.token;
            user = data.user;
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));
            updateAuthUI();
            bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
            loadFavorites();
        } else {
            alert(data.error);
        }
    } catch (error) {
        alert('An error occurred during login');
    }
}

async function register() {
    const username = document.getElementById('registerUsername').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;

    try {
        const response = await fetch(`${API_BASE_URL}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, email, password })
        });

        const data = await response.json();

        if (response.ok) {
            token = data.token;
            user = data.user;
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));
            updateAuthUI();
            bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
            loadFavorites();
        } else {
            alert(data.error);
        }
    } catch (error) {
        alert('An error occurred during registration');
    }
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    token = null;
    user = null;
    updateAuthUI();
}

function updateAuthUI() {
    const authButtons = document.getElementById('authButtons');
    const userMenu = document.getElementById('userMenu');
    const username = document.getElementById('username');

    if (token && user) {
        authButtons.classList.add('d-none');
        userMenu.classList.remove('d-none');
        username.textContent = user.username;
    } else {
        authButtons.classList.remove('d-none');
        userMenu.classList.add('d-none');
    }
}

// Article search functions
async function searchArticles(page = 0) {
    const query = document.getElementById('searchInput').value;
    if (!query) return;

    currentSearchQuery = query;
    currentPage = page;

    try {
        const response = await fetch(`${API_BASE_URL}/articles/search?q=${encodeURIComponent(query)}&page=${page}`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        const data = await response.json();

        if (response.ok) {
            displayArticles(data.response.docs);
            updatePagination(data.response.meta.hits);
        } else {
            alert(data.error);
        }
    } catch (error) {
        alert('An error occurred while searching articles');
    }
}

function displayArticles(articles) {
    alert('huuu');
    const grid = document.getElementById('articlesGrid');
    grid.innerHTML = '';

    articles.forEach(article => {
        const card = document.createElement('div');
        card.className = 'col-md-4 mb-4';
        card.innerHTML = `
            <div class="card article-card h-100">
                ${article.multimedia?.[0] ? `
                    <img src="https://static01.nyt.com/${article.multimedia[0].url}" 
                         class="card-img-top" alt="${article.headline.main}">
                ` : ''}
                <div class="card-body">
                    <h5 class="card-title">${article.headline.main}</h5>
                    <p class="card-text">${article.snippet}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">${new Date(article.pub_date).toLocaleDateString()}</small>
                        <i class="fas fa-heart favorite-icon ${isFavorite(article._id) ? 'active' : ''}"
                           onclick="toggleFavorite('${article._id}', '${article.headline.main}', '${article.web_url}')"></i>
                    </div>
                    <a href="${article.web_url}" target="_blank" class="btn btn-primary mt-2">Read More</a>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

function updatePagination(totalHits) {
    totalPages = Math.ceil(totalHits / 10);
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';

    for (let i = 0; i < totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `
            <a class="page-link" href="#" onclick="searchArticles(${i})">${i + 1}</a>
        `;
        pagination.appendChild(li);
    }
}

// Favorites functions
async function loadFavorites() {
    if (!token) return;

    try {
        const response = await fetch(`${API_BASE_URL}/favorites`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        const favorites = await response.json();

        if (response.ok) {
            displayFavorites(favorites);
        } else {
            alert(favorites.error);
        }
    } catch (error) {
        alert('An error occurred while loading favorites');
    }
}

function displayFavorites(favorites) {
    const grid = document.getElementById('favoritesGrid');
    grid.innerHTML = '';

    favorites.forEach(favorite => {
        const card = document.createElement('div');
        card.className = 'col-md-6 mb-3';
        card.innerHTML = `
            <div class="card article-card h-100">
                <div class="card-body">
                    <h5 class="card-title">${favorite.title}</h5>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">${new Date(favorite.created_at).toLocaleDateString()}</small>
                        <i class="fas fa-heart favorite-icon active"
                           onclick="removeFavorite('${favorite.article_id}')"></i>
                    </div>
                    <a href="${favorite.url}" target="_blank" class="btn btn-primary mt-2">Read More</a>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

function showFavorites() {
    const modal = new bootstrap.Modal(document.getElementById('favoritesModal'));
    modal.show();
}

async function toggleFavorite(articleId, title, url) {
    if (!token) {
        alert('Please login to add favorites');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/favorites`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                article_id: articleId,
                title: title,
                url: url
            })
        });

        const data = await response.json();

        if (response.ok) {
            loadFavorites();
            searchArticles(currentPage);
        } else {
            alert(data.error);
        }
    } catch (error) {
        alert('An error occurred while updating favorites');
    }
}

async function removeFavorite(articleId) {
    if (!token) return;

    try {
        const response = await fetch(`${API_BASE_URL}/favorites?article_id=${articleId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `