document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('myUpcomingList');
    const toggle = document.getElementById('myUpcomingToggle');
    const panel = document.getElementById('myUpcomingPanel');
    if (!list) return; // Only exists on pages with the professor nav

    if (toggle && panel) {
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.toggle('open');
            if (panel.classList.contains('open')) loadMyUpcoming();
        });
        document.addEventListener('click', function (e) {
            if (panel.classList.contains('open') && !panel.contains(e.target) && e.target !== toggle) {
                panel.classList.remove('open');
            }
        });
    }

    const TYPE_ICONS = {
        class: '🏫', live: '🎥', exam: '📝', event: '📌'
    };

    function loadMyUpcoming() {
        fetch('../api/professor_upcoming_events.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.events.length === 0) {
                    list.innerHTML = '<div class="upcoming-empty">Wala ka pang na-post.</div>';
                    return;
                }
                list.innerHTML = data.events.map(e => `
                    <div class="upcoming-item" data-id="${e.id}">
                        <span class="upcoming-item-icon">${TYPE_ICONS[e.event_type] || '📌'}</span>
                        <div class="upcoming-item-info">
                            <div class="upcoming-item-title">${e.title}</div>
                            <div class="upcoming-item-date">${e.event_date}${e.event_time ? ' • ' + e.event_time : ''}</div>
                        </div>
                        <div class="upcoming-item-actions">
                            <button type="button" class="upcoming-edit-btn" title="Edit">✏️</button>
                            <button type="button" class="upcoming-delete-btn" title="Delete">🗑️</button>
                        </div>
                    </div>
                `).join('');

                list.querySelectorAll('.upcoming-edit-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const item = btn.closest('.upcoming-item');
                        const event = data.events.find(e => String(e.id) === item.dataset.id);
                        openEditModal(event);
                    });
                });

                list.querySelectorAll('.upcoming-delete-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('Burahin ang upcoming post na ito?')) return;
                        const id = btn.closest('.upcoming-item').dataset.id;
                        const fd = new FormData();
                        fd.append('id', id);
                        fetch('../api/delete_upcoming.php', { method: 'POST', body: fd })
                            .then(res => res.json())
                            .then(function (res) {
                                if (res.success) loadMyUpcoming();
                            });
                    });
                });
            });
    }

    function openEditModal(event) {
        document.getElementById('upcomingEditId').value = event.id;
        document.getElementById('upcomingTitle').value = event.title;
        document.getElementById('upcomingType').value = event.event_type;
        document.getElementById('upcomingDate').value = event.event_date;
        document.getElementById('upcomingTime').value = event.event_time || '';
        if (document.getElementById('upcomingCourse')) document.getElementById('upcomingCourse').value = event.target_course_id || '';
        if (document.getElementById('upcomingYearLevel')) document.getElementById('upcomingYearLevel').value = event.target_year_level || '';
        if (document.getElementById('upcomingSection')) document.getElementById('upcomingSection').value = event.target_section_label || '';

        if (window.openUpcomingEditModal) window.openUpcomingEditModal();
    }

    document.addEventListener('upcoming-posted', loadMyUpcoming);

    loadMyUpcoming();
});