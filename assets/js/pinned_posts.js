document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('pinnedToggle');
    const panel = document.getElementById('pinnedPanel');
    const list = document.getElementById('pinnedList');
    if (!toggle || !panel) return;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function loadPinned() {
        list.innerHTML = '<div class="notif-empty">Loading...</div>';
        fetch('../api/pinned_posts.php')
            .then(res => res.json())
            .then(function (data) {
                if (!data.success || data.posts.length === 0) {
                    list.innerHTML = '<div class="notif-empty">No pinned posts yet.</div>';
                    return;
                }
                list.innerHTML = data.posts.map(p => `
                    <div class="notif-item" data-id="${p.id}">
                        <div class="notif-text">
                            <strong>📌 ${escapeHtml(p.title)}</strong><br>
                            ${escapeHtml((p.content || '').slice(0, 60))}${(p.content || '').length > 60 ? '...' : ''}
                        </div>
                    </div>
                `).join('');

                list.querySelectorAll('.notif-item').forEach(function (item) {
                    item.addEventListener('click', function () {
                        window.location.href = 'dashboard.php?highlight=' + item.dataset.id + '&type=pin';
                    });
                });
            });
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) loadPinned();
    });

    document.addEventListener('click', function (e) {
        if (panel.classList.contains('open') && !panel.contains(e.target) && e.target !== toggle) {
            panel.classList.remove('open');
        }
    });
});