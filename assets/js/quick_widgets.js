document.addEventListener('DOMContentLoaded', function () {
    const assignmentsToggle = document.getElementById('assignmentsWidgetToggle');
    const assignmentsWidget = document.getElementById('assignmentsWidget');
    const assignmentsList = document.getElementById('assignmentsWidgetList');

    const classmatesToggle = document.getElementById('classmatesWidgetToggle');
    const classmatesWidget = document.getElementById('classmatesWidget');
    const classmatesCloseBtn = document.getElementById('closeClassmatesWidget');
    const classmatesList = document.getElementById('classmatesWidgetList');

    if (!assignmentsWidget && !classmatesWidget) return; // Only exists on student Home dashboard

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function avatarStyle(path) {
        return path ? ` style="background-image:url('../${path}')"` : '';
    }

    // ===== My Assignments flyout =====
    let assignmentsLoaded = false;

    function loadAssignmentsPreview() {
        assignmentsList.innerHTML = '<div class="classes-empty">Loading...</div>';
        fetch('../api/student_assignments.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.assignments.length === 0) {
                    assignmentsList.innerHTML = '<div class="classes-empty">No assignments yet.</div>';
                    return;
                }
                assignmentsList.innerHTML = data.assignments.slice(0, 6).map(a => {
                    const statusLabel = a.status === 'graded'
                        ? `Graded &middot; ${a.grade}/${a.points}`
                        : a.status === 'submitted' ? 'Submitted' : 'Not submitted';
                    return `
                        <div class="assignment-widget-item">
                            <div class="assignment-widget-item-title">${escapeHtml(a.title)}</div>
                            <div class="assignment-widget-item-meta">${escapeHtml(a.subject_name)} &middot; ${statusLabel}</div>
                        </div>
                    `;
                }).join('');
                assignmentsLoaded = true;
            });
    }

    if (assignmentsToggle) {
        assignmentsToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            assignmentsWidget.classList.toggle('open');
            if (assignmentsWidget.classList.contains('open') && !assignmentsLoaded) {
                loadAssignmentsPreview();
            }
        });
    }

    // ===== Classmates panel =====
    let classmatesLoaded = false;

    function loadClassmatesPreview() {
        classmatesList.innerHTML = '<div class="directory-empty">Loading...</div>';
        fetch('../api/classmates.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.classmates.length === 0) {
                    classmatesList.innerHTML = '<div class="directory-empty">No classmates found yet.</div>';
                    return;
                }
                classmatesList.innerHTML = data.classmates.map(s => `
                    <div class="directory-item">
                        <div class="directory-avatar-wrap">
                            <div class="avatar-circle"${avatarStyle(s.profile_picture)}></div>
                            <div class="directory-status-dot ${s.online ? 'online' : ''}"></div>
                        </div>
                        <div class="directory-item-name">${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}</div>
                    </div>
                `).join('');
                classmatesLoaded = true;
            });
    }

    if (classmatesToggle) {
        classmatesToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            classmatesWidget.classList.toggle('open');
            if (classmatesWidget.classList.contains('open') && !classmatesLoaded) {
                loadClassmatesPreview();
            }
        });
    }

    if (classmatesCloseBtn) {
        classmatesCloseBtn.addEventListener('click', function () {
            classmatesWidget.classList.remove('open');
        });
    }

    // Click outside closes both widgets
    document.addEventListener('click', function (e) {
        if (assignmentsWidget && assignmentsWidget.classList.contains('open') &&
            !assignmentsWidget.contains(e.target) && e.target !== assignmentsToggle) {
            assignmentsWidget.classList.remove('open');
        }
        if (classmatesWidget && classmatesWidget.classList.contains('open') &&
            !classmatesWidget.contains(e.target) && e.target !== classmatesToggle) {
            classmatesWidget.classList.remove('open');
        }
    });
});