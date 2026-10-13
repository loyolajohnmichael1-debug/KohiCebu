(function () {
    const root = document.querySelector('[data-google-auth]');
    if (!root) return;
    const button = root.querySelector('[data-google-button]');
    const status = root.querySelector('[data-google-status]');
    let busy = false;

    function show(message, error) {
        if (!status) return;
        status.textContent = message;
        status.classList.toggle('is-error', !!error);
        status.hidden = !message;
    }

    async function handleCredential(response) {
        if (busy) return;
        const phoneInput = root.dataset.phoneInput ? document.getElementById(root.dataset.phoneInput) : null;
        const phone = phoneInput ? phoneInput.value.trim() : '';
        if (phoneInput && phone.length < 7) {
            show('Enter a valid phone number to create your account, then choose Google again.', true);
            phoneInput.focus();
            return;
        }
        busy = true;
        show('Signing you in securely…', false);
        try {
            const result = await fetch('google-auth.php', {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({id_token: response.credential, phone, csrf_token: root.dataset.csrf})
            });
            const data = await result.json();
            if (!result.ok || !data.success) {
                show(data.message || 'Google sign-in failed. Please try again.', true);
                if (data.needs_registration && status) {
                    const link = document.createElement('a');
                    link.href = 'register.php';
                    link.textContent = ' Create your customer account';
                    link.className = 'google-auth__link';
                    status.append(link);
                }
                busy = false;
                return;
            }
            window.location.assign(root.dataset.redirect || 'index.php');
        } catch (error) {
            show(error.message || 'Google sign-in failed. Please try again.', true);
            busy = false;
        }
    }

    function initialize() {
        if (!window.google || !google.accounts || !google.accounts.id) return setTimeout(initialize, 100);
        google.accounts.id.initialize({client_id: root.dataset.clientId, callback: handleCredential, auto_select: false});
        google.accounts.id.renderButton(button, {type: 'standard', theme: 'outline', size: 'large', shape: 'rectangular', text: 'continue_with', logo_alignment: 'left', width: Math.min(button.clientWidth || 360, 400)});
    }
    initialize();
}());
