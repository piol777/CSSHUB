document.addEventListener('DOMContentLoaded', function () {
    const directoryToggle = document.getElementById('directoryToggle');
    const directoryWidget = document.getElementById('directoryWidget');
    const filterToggle = document.getElementById('directoryFilterToggle');
    const filterForm = document.getElementById('directoryFilterForm');
    const courseSelect = document.getElementById('directoryCourse');
    const yearSelect = document.getElementById('directoryYear');
    const sectionInput = document.getElementById('directorySection');
    const applyBtn = document.getElementById('directoryApplyFilter');
    const list = document.getElementById('directoryList');
    if (!directoryWidget) return; // Only exists for professor pages

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function avatarStyle(path) {
        return path ? ` style="background-image:url('../${path}')"` : '';
    }

    // Load course list into filter dropdown
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

    // Panel is always visible by default now — no toggle button needed to open it
    directoryWidget.classList.add('open');

    // Load all students immediately (default filter: all courses, all year levels)
    loadStudents();

    if (directoryToggle) {
        directoryToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            directoryWidget.classList.toggle('open');
        });
    }

    filterToggle.addEventListener('click', function () {
        filterForm.classList.toggle('open');
    });

    applyBtn.addEventListener('click', function () {
        filterForm.classList.remove('open');
        loadStudents();
    });

    function loadStudents() {
        list.innerHTML = '<div class="directory-empty">Loading...</div>';

        const params = new URLSearchParams();
        if (courseSelect.value) params.append('course_id', courseSelect.value);
        if (yearSelect.value) params.append('year_level', yearSelect.value);
        if (sectionInput.value.trim()) params.append('section_label', sectionInput.value.trim());

        fetch('../api/students_by_filter.php?' + params.toString())
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                if (data.students.length === 0) {
                    list.innerHTML = '<div class="directory-empty">No students found.</div>';
                    return;
                }

                list.innerHTML = data.students.map(s => `
                    <div class="directory-item" data-user-id="${s.id}" data-name="${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}" data-avatar="${s.profile_picture ? escapeHtml(s.profile_picture) : ''}">
                        <div class="directory-avatar-wrap">
                            <div class="avatar-circle"${avatarStyle(s.profile_picture)}></div>
                            <div class="directory-status-dot ${s.online ? 'online' : ''}"></div>
                        </div>
                        <div class="directory-item-name">${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}</div>
                    </div>
                `).join('');

                document.querySelectorAll('.directory-item').forEach(item => {
                    item.addEventListener('click', function () {
                        startChatWithStudent(this.dataset.userId, this.dataset.name, this.dataset.avatar);
                    });
                });
            });
    }

    function startChatWithStudent(userId, name, avatar) {
        fetch('../api/start_conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'other_user_id=' + encodeURIComponent(userId)
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                msgWidget.classList.add('open');
                if (typeof window.openWidgetConversation === 'function') {
                    window.openWidgetConversation(data.conversation_id, name, avatar);
                }
            });
    }
});