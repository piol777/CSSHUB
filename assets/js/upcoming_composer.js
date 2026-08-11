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

    function openModal(presetType) {
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

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('../api/create_upcoming.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Failed to post.');
                    return;
                }
                closeModal();
                form.reset();
                showToast('Upcoming event posted!');
            })
            .catch(() => {
                alert('Something went wrong. Please try again.');
            });
    });
});