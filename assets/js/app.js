function showFieldError(field, message) {
    if (!field) {
        return;
    }

    field.classList.add('is-invalid-lite');

    let error = field.parentElement.querySelector('.form-error');
    if (!error) {
        error = document.createElement('span');
        error.className = 'form-error';
        field.parentElement.appendChild(error);
    }

    error.textContent = message;
}

function clearFieldError(field) {
    if (!field) {
        return;
    }

    field.classList.remove('is-invalid-lite');

    const error = field.parentElement.querySelector('.form-error');
    if (error) {
        error.remove();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const validatedForms = document.querySelectorAll('[data-validate]');

    validatedForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[data-required]');

            requiredFields.forEach(function (field) {
                clearFieldError(field);

                if (!field.value.trim()) {
                    showFieldError(field, field.dataset.message || 'Field ini wajib diisi.');
                    isValid = false;
                }
            });

            if (!isValid) {
                event.preventDefault();
            }
        });
    });

    const confirmButtons = document.querySelectorAll('[data-confirm]');

    confirmButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            const message = button.dataset.confirm || 'Lanjutkan aksi ini?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});
