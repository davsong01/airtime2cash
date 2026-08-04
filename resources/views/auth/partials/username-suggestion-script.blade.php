<script>
    document.addEventListener('DOMContentLoaded', function () {
        const firstName = document.getElementById('firstName');
        const lastName = document.getElementById('lastName');
        const username = document.getElementById('username');
        const suggestButton = document.getElementById('suggestUsername');

        if (!firstName || !lastName || !username || !suggestButton) {
            return;
        }

        let usernameWasEdited = username.value.trim() !== '';

        function clean(value) {
            return value.normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]/g, '');
        }

        function suggestUsername() {
            const base = (clean(firstName.value) + clean(lastName.value)).slice(0, 24) || 'user';
            username.value = base + Math.floor(100 + Math.random() * 9900);
            usernameWasEdited = false;
            username.dispatchEvent(new Event('change', { bubbles: true }));
        }

        username.addEventListener('input', function () {
            usernameWasEdited = true;
        });

        [firstName, lastName].forEach(function (input) {
            input.addEventListener('blur', function () {
                if (!usernameWasEdited && username.value.trim() === '' && firstName.value.trim() && lastName.value.trim()) {
                    suggestUsername();
                }
            });
        });

        suggestButton.addEventListener('click', function () {
            suggestUsername();
            username.focus();
        });
    });
</script>
