document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('createUpcomingModal');
    if (!modal) return; // Only exists on professor pages

    const closeBtn = document.getElementById('closeCreateUpcomingModal');
    const form = document.getElementById('createUpcomingForm');
    const courseSelect = document.getElementById('upcomingCourse');
    const typeSelect = document.getElementById('upcomingType');
    const toast = document.getElementById('toast');
    const openTrigger = document.getElementById('openCreateUpcomingModal');
    const openTriggerAvatar = document.getElementById('openCreateUpcomingModalAvatar');
    const classBtn = document.getElementById('upcomingComposerClassBtn');
    const liveBtn = document.getElementById('upcomingComposerVideoBtn');
    const editIdInput = document.getElementById('upcomingEditId');
    const modalTitle = document.getElementById('upcomingModalTitle');

    fetch('../api/courses.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            data.courses.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name + ' (' + c.code + ')';
                courseSelect.appendChild(opt);
            });
        });

    // Bagong post lang (hindi galing sa Edit button) — laging i-reset ang edit state
    function openModal(presetType) {
        editIdInput.value = '';
        modalTitle.textContent = 'Post Upcoming';
        form.reset();
        if (presetType) typeSelect.value = presetType;
        modal.classList.add('open');
    }

    function closeModal() {
        modal.classList.remove('open');
    }

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    if (openTrigger) openTrigger.addEventListener('click', () => openModal());
    if (openTriggerAvatar) openTriggerAvatar.addEventListener('click', () => openModal());
    if (classBtn) classBtn.addEventListener('click', () => openModal('class'));
    if (liveBtn) liveBtn.addEventListener('click', () => openModal('live'));
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // Tinatawag ito ng my_upcoming.js kapag pinindot ang Edit — hindi na dapat i-reset dito
    window.openUpcomingEditModal = function () {
        modalTitle.textContent = 'Edit Upcoming';
        modal.classList.add('open');
    };

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        const isEdit = editIdInput.value !== '';
        const endpoint = isEdit ? '../api/update_upcoming.php' : '../api/create_upcoming.php';

        if (isEdit) formData.set('id', editIdInput.value);

        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Failed to save.');
                    return;
                }
                closeModal();
                form.reset();
                editIdInput.value = '';
                showToast(isEdit ? 'Upcoming event updated!' : 'Upcoming event posted!');
                document.dispatchEvent(new CustomEvent('upcoming-posted'));
            })
            .catch(() => {
                alert('Something went wrong. Please try again.');
            });
    });
});