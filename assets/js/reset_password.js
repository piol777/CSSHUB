document.addEventListener('DOMContentLoaded', function () {
    const boxes = document.querySelectorAll('.digit-box');
    const combined = document.getElementById('codeCombined');
    const form = document.getElementById('resetPasswordForm');

    boxes[0].focus();

    boxes.forEach((box, index) => {
        box.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && index < boxes.length - 1) {
                boxes[index + 1].focus();
            }
        });

        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                boxes[index - 1].focus();
            }
        });

        box.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
            for (let i = 0; i < boxes.length; i++) {
                boxes[i].value = pasted[i] || '';
            }
            boxes[Math.min(pasted.length, boxes.length - 1)].focus();
        });
    });

    form.addEventListener('submit', function (e) {
        const code = Array.from(boxes).map(b => b.value).join('');

        if (code.length !== 4) {
            e.preventDefault();
            alert('Please enter the full 4-digit code.');
            return;
        }

        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters.');
            return;
        }

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match.');
            return;
        }

        combined.value = code;
    });
});
