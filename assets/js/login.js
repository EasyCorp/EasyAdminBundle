const loginForm = document.querySelector('form');
loginForm.addEventListener(
    'submit',
    () => {
        loginForm.querySelector('button[type="submit"]').setAttribute('disabled', 'disabled');
    },
    false
);
