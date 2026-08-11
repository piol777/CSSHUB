document.addEventListener('DOMContentLoaded', function () {
    const courseSelect = document.getElementById('course_id');
    const sectionPrefix = document.getElementById('sectionPrefix');
    const sectionInput = document.getElementById('section_label');

    courseSelect.addEventListener('change', function () {
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        const courseCode = selectedOption.getAttribute('data-code');

        if (courseCode) {
            sectionPrefix.textContent = courseCode + '/';
            sectionInput.disabled = false;
            sectionInput.focus();
        } else {
            sectionPrefix.textContent = '--/';
            sectionInput.disabled = true;
            sectionInput.value = '';
        }
    });

    // Only allow digits and a single dash, e.g. "1-1"
    sectionInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9-]/g, '');
    });

    // Password match check before submit
    const form = document.getElementById('registerForm');
    form.addEventListener('submit', function (e) {
        const pass = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        if (pass !== confirm) {
            e.preventDefault();
            alert('Password and Confirm Password do not match.');
        }
    });
});