const loginForm = document.querySelector('form');
loginForm.addEventListener('submit', function () {
    loginForm.querySelector('button[type="submit"]').setAttribute('disabled', 'disabled');
}, false);
