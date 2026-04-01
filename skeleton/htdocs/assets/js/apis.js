/**
 * apis.js — Framework AJAX Helpers
 * ==================================
 * Provides the ApiClient object that wraps all fetch/jQuery AJAX calls
 * following the /api/{namespace}/{method} routing pattern.
 *
 * Usage:
 *   ApiClient.post('auth', 'login', { user: 'john', password: '...' })
 *     .then(data => toast.success('Welcome!', data.message))
 *     .catch(err => toast.error('Login Failed', err.message));
 *
 *   ApiClient.get('posts', 'count').then(data => console.log(data));
 */

const ApiClient = (() => {

    const BASE = '/api/';

    /**
     * Core fetch wrapper.
     * @param {'GET'|'POST'} method
     * @param {string} namespace  API namespace folder (e.g. 'auth', 'posts')
     * @param {string} action     Action name (e.g. 'login', 'create')
     * @param {Object} [payload]  POST body data
     * @returns {Promise<Object>} Parsed JSON response
     */
    async function _request(method, namespace, action, payload = {}) {
        const url = `${BASE}${encodeURIComponent(namespace)}/${encodeURIComponent(action)}`;

        const options = {
            method,
            credentials: 'same-origin',
        };

        if (method === 'POST') {
            const formData = new FormData();
            Object.entries(payload).forEach(([k, v]) => formData.append(k, v));
            options.body = formData;
        }

        const response = await fetch(url, options);
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const err = new Error(data.message || data.error || `HTTP ${response.status}`);
            err.status = response.status;
            err.data   = data;
            throw err;
        }

        return data;
    }

    return {
        /** GET /api/{namespace}/{action} */
        get:    (ns, action)          => _request('GET',  ns, action),
        /** POST /api/{namespace}/{action} with FormData payload */
        post:   (ns, action, payload) => _request('POST', ns, action, payload),
        /** DELETE /api/{namespace}/{action} */
        delete: (ns, action)          => _request('DELETE', ns, action),
    };
})();

/* ─── Auth Helpers ──────────────────────────────────────── */

/**
 * Submits login form. Redirects to /admin on success.
 * @param {string} user
 * @param {string} password
 */
async function apiLogin(user, password) {
    try {
        const data = await ApiClient.post('auth', 'login', { user, password });
        toast.success('Welcome back!', data.message || 'Logged in successfully.');
        setTimeout(() => { window.location.href = '/admin'; }, 800);
    } catch (err) {
        toast.error('Login Failed', err.message || 'Invalid credentials.');
    }
}

/**
 * Submits signup form. Redirects to /login on success.
 * @param {Object} fields - { username, password, email_address, phone }
 */
async function apiSignup(fields) {
    try {
        const data = await ApiClient.post('auth', 'signup', fields);
        toast.success('Account Created!', data.message || 'Please log in.');
        setTimeout(() => { window.location.href = '/login'; }, 1200);
    } catch (err) {
        toast.error('Registration Failed', err.message || 'Try a different username.');
    }
}

/* ─── DOM-Ready Init ─────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {

    // Login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const user     = document.getElementById('login-user').value.trim();
            const password = document.getElementById('login-password').value;
            apiLogin(user, password);
        });
    }

    // Signup form
    const signupForm = document.getElementById('signup-form');
    if (signupForm) {
        signupForm.addEventListener('submit', (e) => {
            e.preventDefault();
            apiSignup({
                username:      document.getElementById('signup-username').value.trim(),
                password:      document.getElementById('signup-password').value,
                email_address: document.getElementById('signup-email').value.trim(),
                phone:         document.getElementById('signup-phone').value.trim(),
            });
        });
    }

    // Sidebar toggle (mobile)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
});
