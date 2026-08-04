<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
            const input = document.getElementById(toggle.dataset.passwordToggle);
            const icon = toggle.querySelector('i');

            if (!input || !icon) {
                return;
            }

            toggle.addEventListener('click', function () {
                const shouldShow = input.type === 'password';
                input.type = shouldShow ? 'text' : 'password';
                icon.className = shouldShow ? 'bx bx-hide' : 'bx bx-show';
                toggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            });
        });
    });
</script>
