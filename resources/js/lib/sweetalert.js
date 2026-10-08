import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const baseOptions = {
    reverseButtons: true,
    focusCancel: true,
    customClass: {
        confirmButton: 'rounded-xl px-5 py-2.5 text-sm font-bold shadow-none',
        cancelButton: 'rounded-xl px-5 py-2.5 text-sm font-bold shadow-none',
        popup: 'rounded-2xl',
    },
};

export async function confirmDelete(text, title = 'Are you sure?') {
    const result = await Swal.fire({
        ...baseOptions,
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
    });

    return result.isConfirmed;
}

export async function confirmAction({
    title,
    text,
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    icon = 'question',
    confirmColor = '#2563eb',
}) {
    const result = await Swal.fire({
        ...baseOptions,
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#64748b',
    });

    return result.isConfirmed;
}

export async function confirmSignOut() {
    const result = await Swal.fire({
        ...baseOptions,
        title: 'Sign out?',
        text: 'Are you sure you want to sign out of APO Guard Monitoring System?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sign Out',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
    });

    return result.isConfirmed;
}

export async function confirmDeleteAccount() {
    const result = await Swal.fire({
        ...baseOptions,
        title: 'Delete this account?',
        text: 'This permanently removes your login. Enter your password to confirm.',
        icon: 'warning',
        input: 'password',
        inputPlaceholder: 'Enter your password',
        inputAttributes: {
            autocapitalize: 'off',
            autocorrect: 'off',
        },
        showCancelButton: true,
        confirmButtonText: 'Confirm Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        preConfirm: (password) => {
            if (!password) {
                Swal.showValidationMessage('Password is required');
            }

            return password;
        },
    });

    return result.isConfirmed ? result.value : null;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function appendHiddenInput(form, name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
}

export function initBladeConfirmations() {
    document.querySelectorAll('form[data-swal-confirm]').forEach((form) => {
        if (form.dataset.swalBound === 'true') {
            return;
        }

        form.dataset.swalBound = 'true';
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const title = form.dataset.swalTitle ?? 'Are you sure?';
            const text = form.dataset.swalConfirm;
            const isDelete = form.dataset.swalType === 'delete';
            const confirmed = isDelete
                ? await confirmDelete(text, title)
                : await confirmAction({
                      title,
                      text,
                      confirmText: form.dataset.swalConfirmText ?? 'Confirm',
                      icon: form.dataset.swalIcon ?? 'question',
                      confirmColor: form.dataset.swalConfirmColor ?? '#2563eb',
                  });

            if (confirmed) {
                form.removeAttribute('data-swal-confirm');
                form.submit();
            }
        });
    });

    document.querySelectorAll('[data-swal-sign-out]').forEach((button) => {
        if (button.dataset.swalBound === 'true') {
            return;
        }

        button.dataset.swalBound = 'true';
        button.addEventListener('click', async (event) => {
            event.preventDefault();

            if (!(await confirmSignOut())) {
                return;
            }

            const form = document.getElementById('logout-form');
            if (form) {
                form.submit();
            }
        });
    });

    document.querySelectorAll('[data-swal-delete-account]').forEach((button) => {
        if (button.dataset.swalBound === 'true') {
            return;
        }

        button.dataset.swalBound = 'true';
        button.addEventListener('click', async (event) => {
            event.preventDefault();

            const password = await confirmDeleteAccount();
            if (!password) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = button.dataset.action;
            appendHiddenInput(form, '_token', csrfToken());
            appendHiddenInput(form, '_method', 'DELETE');
            appendHiddenInput(form, 'password', password);
            document.body.appendChild(form);
            form.submit();
        });
    });
}

export { Swal };
