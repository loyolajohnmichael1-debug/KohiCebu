document.addEventListener('DOMContentLoaded', () => {
    const endpoint = 'api/account.php';
    const form = document.querySelector('.account-form');

    if (form) {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            const message = document.getElementById('formMessage');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Saving…';
            message.textContent = '';
            message.classList.remove('is-error', 'is-success');

            try {
                const data = Object.fromEntries(new FormData(form).entries());
                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: form.dataset.action, ...data })
                });
                const result = await response.json();
                if (response.status === 401) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname.split('/').pop());
                    return;
                }
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to save your changes.');

                message.textContent = result.message || 'Saved successfully.';
                message.classList.add('is-success');
                form.reset();
                if (form.dataset.action === 'profile') {
                    window.setTimeout(() => { window.location.href = 'account.php'; }, 900);
                }
            } catch (error) {
                message.textContent = error.message || 'The account service is unavailable. Please try again.';
                message.classList.add('is-error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    }

    const activityState = document.getElementById('activityState');
    const activityList = document.getElementById('activityList');
    if (!activityState || !activityList) return;

    fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'activity' })
    })
        .then(async response => {
            const result = await response.json();
            if (response.status === 401) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent('login-activity.php');
                return null;
            }
            if (!response.ok || !result.success) throw new Error(result.message || 'Unable to load login activity.');
            return result;
        })
        .then(result => {
            if (!result) return;
            const entries = Array.isArray(result.activities) ? result.activities : [];
            activityList.replaceChildren();
            if (!entries.length) {
                activityState.textContent = 'No login activity has been recorded yet.';
                return;
            }

            entries.forEach(entry => {
                const item = document.createElement('li');
                const date = document.createElement('time');
                const rawDate = String(entry.login_date || '');
                const parsed = new Date(rawDate.replace(' ', 'T'));
                date.textContent = Number.isNaN(parsed.getTime())
                    ? rawDate
                    : new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(parsed);
                if (!Number.isNaN(parsed.getTime())) date.dateTime = parsed.toISOString();
                const label = document.createElement('span');
                label.textContent = 'Successful sign-in';
                item.append(date, label);
                activityList.append(item);
            });
            activityState.hidden = true;
            activityList.hidden = false;
        })
        .catch(error => {
            activityState.textContent = error.message || 'Unable to load login activity.';
            activityState.classList.add('is-error');
        });
});
