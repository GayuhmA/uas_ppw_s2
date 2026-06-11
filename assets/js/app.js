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

function initializeReservationFilters() {
    const filterGroup = document.querySelector('[data-reservation-filters]');

    if (!filterGroup) {
        return;
    }

    const buttons = filterGroup.querySelectorAll('[data-status-filter]');
    const rows = document.querySelectorAll('[data-reservation-row]');
    const emptyState = document.querySelector('[data-filter-empty]');

    if (!buttons.length || !rows.length) {
        return;
    }

    function applyFilter(statusList) {
        const allowedStatuses = statusList
            .split(',')
            .map(function (status) {
                return status.trim();
            })
            .filter(Boolean);
        let visibleRows = 0;

        rows.forEach(function (row) {
            const shouldShow = allowedStatuses.length === 0 || allowedStatuses.includes(row.dataset.status);
            row.hidden = !shouldShow;

            if (shouldShow) {
                visibleRows += 1;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleRows > 0;
        }
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            buttons.forEach(function (item) {
                item.classList.remove('active');
                item.setAttribute('aria-pressed', 'false');
            });

            button.classList.add('active');
            button.setAttribute('aria-pressed', 'true');
            applyFilter(button.dataset.statusFilter || '');
        });
    });
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

    initializeReservationFilters();
});
