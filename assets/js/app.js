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

function initializeToasts() {
    const toasts = document.querySelectorAll('[data-toast]');

    toasts.forEach(function (toast) {
        const closeButton = toast.querySelector('[data-toast-close]');

        function closeToast() {
            toast.classList.add('is-hiding');

            window.setTimeout(function () {
                toast.remove();
            }, 220);
        }

        if (closeButton) {
            closeButton.addEventListener('click', closeToast);
        }

        window.setTimeout(closeToast, 3600);
    });
}

function clearFlashParams() {
    const url = new URL(window.location.href);
    const hasFlashParams = url.searchParams.has('message') || url.searchParams.has('error');

    if (!hasFlashParams) {
        return;
    }

    url.searchParams.delete('message');
    url.searchParams.delete('error');
    window.history.replaceState({}, '', url.pathname + url.search + url.hash);
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
    initializeToasts();
    clearFlashParams();
});
