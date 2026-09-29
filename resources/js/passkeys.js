import { Passkeys } from '@laravel/passkeys';

const showError = (root, message) => {
    const errorEl = root.querySelector('[data-passkey-error]');

    if (!(errorEl instanceof HTMLElement)) {
        return;
    }

    if (!message) {
        errorEl.hidden = true;
        errorEl.textContent = '';

        return;
    }

    errorEl.hidden = false;
    errorEl.textContent = message;
};

const setBusy = (button, busy) => {
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    button.disabled = busy;
};

const initPasskeyLogin = () => {
    const root = document.querySelector('[data-passkeys-login]');

    if (!(root instanceof HTMLElement) || !Passkeys.isSupported()) {
        return;
    }

    const button = root.querySelector('[data-passkey-verify]');
    const remember = root.querySelector('[data-passkey-remember]');

    button?.addEventListener('click', async () => {
        showError(root, '');
        setBusy(button, true);

        try {
            const response = await Passkeys.verify({
                remember: () => remember instanceof HTMLInputElement && remember.checked,
            });

            if (response?.redirect) {
                window.location.href = response.redirect;
            }
        } catch (error) {
            showError(root, error instanceof Error ? error.message : String(error));
        } finally {
            setBusy(button, false);
        }
    });

    Passkeys.autofill({
        remember: () => remember instanceof HTMLInputElement && remember.checked,
    }).then((response) => {
        if (response?.redirect) {
            window.location.href = response.redirect;
        }
    }).catch(() => {
        // Autofill is optional; explicit button remains available.
    });
};

const initPasskeyConfirm = () => {
    const root = document.querySelector('[data-passkeys-confirm]');

    if (!(root instanceof HTMLElement) || !Passkeys.isSupported()) {
        return;
    }

    const button = root.querySelector('[data-passkey-confirm]');

    button?.addEventListener('click', async () => {
        showError(root, '');
        setBusy(button, true);

        try {
            const response = await Passkeys.verify({
                routes: {
                    options: '/passkeys/confirm/options',
                    submit: '/passkeys/confirm',
                },
            });

            if (response?.redirect) {
                window.location.href = response.redirect;
            } else {
                window.location.href = '/profile#passkeys';
            }
        } catch (error) {
            showError(root, error instanceof Error ? error.message : String(error));
        } finally {
            setBusy(button, false);
        }
    });
};

const initPasskeyManage = () => {
    const root = document.querySelector('[data-passkeys-manage]');

    if (!(root instanceof HTMLElement) || !Passkeys.isSupported()) {
        return;
    }

    const button = root.querySelector('[data-passkey-register]');
    const nameInput = root.querySelector('[data-passkey-name]');

    button?.addEventListener('click', async () => {
        const name = nameInput instanceof HTMLInputElement
            ? nameInput.value.trim()
            : '';

        if (name === '') {
            showError(root, 'Enter a name for this passkey.');

            return;
        }

        showError(root, '');
        setBusy(button, true);

        try {
            await Passkeys.register({ name });
            window.location.reload();
        } catch (error) {
            showError(root, error instanceof Error ? error.message : String(error));
        } finally {
            setBusy(button, false);
        }
    });
};

export const initPasskeys = () => {
    initPasskeyLogin();
    initPasskeyConfirm();
    initPasskeyManage();
};
