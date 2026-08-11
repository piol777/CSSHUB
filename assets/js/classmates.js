document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('classmatesList');
    if (!list) return; // Only exists on student/classmates.php

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function avatarStyle(path) {
        return path ? ` style="background-image:url('../${path}')"` : '';
    }

    fetch('../api/classmates.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success || data.classmates.length === 0) {
                list.innerHTML = '<div class="directory-empty">No classmates found yet.</div>';
                return;
            }

            list.innerHTML = data.classmates.map(s => `
                <div class="directory-item">
                    <div class="directory-avatar-wrap">
                        <div class="avatar-circle"${avatarStyle(s.profile_picture)}></div>
                        <div class="directory-status-dot ${s.online ? 'online' : ''}"></div>
                    </div>
                    <div class="directory-item-name">${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}</div>
                </div>
            `).join('');
        })
        .catch(() => {
            list.innerHTML = '<div class="directory-empty">Failed to load classmates.</div>';
        });
});