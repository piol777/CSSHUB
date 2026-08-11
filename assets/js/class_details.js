document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.classes-page-container[data-class-id]');
    if (!container) return; // Only exists on class_details.php

    const classId = container.dataset.classId;
    const dateInput = document.getElementById('attendanceDate');
    const list = document.getElementById('attendanceList');
    const saveBtn = document.getElementById('saveAttendanceBtn');
    const historyEl = document.getElementById('attendanceHistory');
    const toast = document.getElementById('toast');

    let currentSessionId = null;

    function showToast(message) {
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2500);
    }

    function loadSession(date) {
        list.innerHTML = '<div class="classes-empty">Loading...</div>';
        const fd = new FormData();
        fd.append('class_id', classId);
        fd.append('session_date', date);

        fetch('../api/start_attendance_session.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    list.innerHTML = '<div class="classes-empty">' + (data.message || 'Failed to load.') + '</div>';
                    return;
                }
                currentSessionId = data.session_id;

                if (data.students.length === 0) {
                    list.innerHTML = '<div class="classes-empty">No enrolled students found for this section.</div>';
                    saveBtn.style.display = 'none';
                    return;
                }

                list.innerHTML = data.students.map(renderRow).join('');
                saveBtn.style.display = 'inline-block';

                list.querySelectorAll('.attendance-status-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const row = btn.closest('.attendance-row');
                        row.querySelectorAll('.attendance-status-btn').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    });
                });
            });
    }

    function renderRow(student) {
        const statuses = ['present', 'late', 'absent', 'excused'];
        const labels = { present: 'Present', late: 'Late', absent: 'Absent', excused: 'Excused' };
        return `
            <div class="attendance-row" data-student-id="${student.student_id}">
                <span class="attendance-student-name">${student.name}</span>
                <div class="attendance-status-group">
                    ${statuses.map(s => `<button type="button" class="attendance-status-btn ${s === student.status ? 'active' : ''}" data-status="${s}">${labels[s]}</button>`).join('')}
                </div>
            </div>
        `;
    }

    saveBtn.addEventListener('click', function () {
        const records = [];
        list.querySelectorAll('.attendance-row').forEach(function (row) {
            const activeBtn = row.querySelector('.attendance-status-btn.active');
            records.push({
                student_id: row.dataset.studentId,
                status: activeBtn ? activeBtn.dataset.status : 'present',
            });
        });

        const fd = new FormData();
        fd.append('session_id', currentSessionId);
        fd.append('records', JSON.stringify(records));

        fetch('../api/save_attendance.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Attendance saved!');
                    loadHistory();
                } else {
                    showToast(data.message || 'Failed to save.');
                }
            });
    });

    function loadHistory() {
        fetch('../api/get_attendance_history.php?class_id=' + classId)
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.sessions.length === 0) {
                    historyEl.innerHTML = '<div class="classes-empty">No attendance taken yet.</div>';
                    return;
                }
                historyEl.innerHTML = data.sessions.map(s => `
                    <div class="attendance-history-item">
                        <span>${s.session_date}</span>
                        <span>${s.present_pct}% present</span>
                    </div>
                `).join('');
            });
    }

    dateInput.addEventListener('change', function () {
        loadSession(dateInput.value);
    });

    loadSession(dateInput.value);
    loadHistory();

    // ===== ASSIGNMENTS =====
    const assignmentsList = document.getElementById('assignmentsList');
    const createAssignmentModal = document.getElementById('createAssignmentModal');
    const openCreateAssignmentBtn = document.getElementById('openCreateAssignmentModal');
    const closeCreateAssignmentBtn = document.getElementById('closeCreateAssignmentModal');
    const createAssignmentForm = document.getElementById('createAssignmentForm');

    const viewSubmissionsModal = document.getElementById('viewSubmissionsModal');
    const closeViewSubmissionsBtn = document.getElementById('closeViewSubmissionsModal');
    const submissionsList = document.getElementById('submissionsList');
    const submissionsModalTitle = document.getElementById('submissionsModalTitle');

    function loadAssignments() {
        fetch('../api/list_assignments.php?class_id=' + classId)
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.assignments.length === 0) {
                    assignmentsList.innerHTML = '<div class="classes-empty">No assignments yet.</div>';
                    return;
                }
                assignmentsList.innerHTML = data.assignments.map(a => `
                    <div class="assignment-item" data-id="${a.id}" data-title="${a.title}">
                        <div>
                            <div class="assignment-item-title">${a.title}</div>
                            <div class="assignment-item-meta">${a.points} pts ${a.due_date ? '&middot; Due ' + a.due_date.replace('T', ' ') : ''}</div>
                        </div>
                        <div class="assignment-item-progress">${a.submitted_count}/${a.total_count} submitted</div>
                    </div>
                `).join('');

                assignmentsList.querySelectorAll('.assignment-item').forEach(function (item) {
                    item.addEventListener('click', function () {
                        openSubmissions(item.dataset.id, item.dataset.title);
                    });
                });
            });
    }

    if (openCreateAssignmentBtn) openCreateAssignmentBtn.addEventListener('click', () => createAssignmentModal.classList.add('open'));
    if (closeCreateAssignmentBtn) closeCreateAssignmentBtn.addEventListener('click', () => createAssignmentModal.classList.remove('open'));
    createAssignmentModal.addEventListener('click', function (e) {
        if (e.target === createAssignmentModal) createAssignmentModal.classList.remove('open');
    });

    createAssignmentForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData();
        fd.append('class_id', classId);
        fd.append('title', document.getElementById('assignmentTitle').value.trim());
        fd.append('description', document.getElementById('assignmentDescription').value.trim());
        fd.append('points', document.getElementById('assignmentPoints').value);
        fd.append('due_date', document.getElementById('assignmentDueDate').value);

        fetch('../api/create_assignment.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showToast('Assignment created!');
                    createAssignmentModal.classList.remove('open');
                    createAssignmentForm.reset();
                    loadAssignments();
                } else {
                    showToast(res.message || 'Failed to create assignment.');
                }
            });
    });

    function openSubmissions(assignmentId, title) {
        submissionsModalTitle.textContent = title;
        submissionsList.innerHTML = '<div class="classes-empty">Loading...</div>';
        viewSubmissionsModal.classList.add('open');

        fetch('../api/assignment_submissions.php?assignment_id=' + assignmentId)
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.submissions.length === 0) {
                    submissionsList.innerHTML = '<div class="classes-empty">No students found.</div>';
                    return;
                }
                submissionsList.innerHTML = data.submissions.map(renderSubmission).join('');

                submissionsList.querySelectorAll('.submission-grade-row button').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const row = btn.closest('.submission-row');
                        const gradeInput = row.querySelector('input');
                        const fd = new FormData();
                        fd.append('submission_id', row.dataset.submissionId);
                        fd.append('grade', gradeInput.value);
                        fd.append('feedback', '');

                        fetch('../api/grade_submission.php', { method: 'POST', body: fd })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success) {
                                    showToast('Grade saved!');
                                    openSubmissions(assignmentId, title);
                                    loadAssignments();
                                } else {
                                    showToast(res.message || 'Failed to save grade.');
                                }
                            });
                    });
                });
            });
    }

    function renderSubmission(s) {
        return `
            <div class="submission-row" data-submission-id="${s.submission_id}">
                <div class="submission-row-top">
                    <span class="submission-name">${s.first_name} ${s.last_name}</span>
                    <span class="submission-status ${s.status}">${s.status}</span>
                </div>
                <div class="submission-grade-row">
                    <input type="number" min="0" max="100" placeholder="Grade" value="${s.grade ?? ''}">
                    <button type="button">Save Grade</button>
                </div>
            </div>
        `;
    }

    if (closeViewSubmissionsBtn) closeViewSubmissionsBtn.addEventListener('click', () => viewSubmissionsModal.classList.remove('open'));
    viewSubmissionsModal.addEventListener('click', function (e) {
        if (e.target === viewSubmissionsModal) viewSubmissionsModal.classList.remove('open');
    });

    loadAssignments();
});