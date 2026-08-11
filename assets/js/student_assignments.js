document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('studentAssignmentsList');
    if (!list) return; // Only exists on student/assignments.php

    const toast = document.getElementById('toast');

    function showToast(message) {
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2500);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function statusLabel(a) {
        if (a.status === 'graded') return `Graded &middot; ${a.grade}/${a.points}`;
        if (a.status === 'submitted') return 'Submitted &middot; Waiting for grade';
        return 'Not yet submitted';
    }

    function loadAssignments() {
        fetch('../api/student_assignments.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.assignments.length === 0) {
                    list.innerHTML = '<div class="classes-empty">No assignments posted yet.</div>';
                    return;
                }
                list.innerHTML = data.assignments.map(renderCard).join('');

                list.querySelectorAll('.assignment-submit-form').forEach(function (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        const fileInput = form.querySelector('input[type="file"]');
                        if (!fileInput.files.length) {
                            showToast('Please choose a file first.');
                            return;
                        }
                        const fd = new FormData();
                        fd.append('assignment_id', form.dataset.assignmentId);
                        fd.append('file', fileInput.files[0]);

                        fetch('../api/submit_assignment.php', { method: 'POST', body: fd })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success) {
                                    showToast('Assignment submitted!');
                                    loadAssignments();
                                } else {
                                    showToast(res.message || 'Failed to submit.');
                                }
                            });
                    });
                });
            });
    }

    function renderCard(a) {
        const dueLabel = a.due_date ? `Due ${a.due_date.replace('T', ' ')}` : 'No due date';
        const canSubmit = a.status !== 'graded';

        return `
            <div class="class-card" style="border-left-color:${a.color_hex}; margin-bottom:14px;">
                <div class="class-card-top">
                    <div>
                        <div class="class-card-title">${escapeHtml(a.subject_name)}</div>
                        <div class="class-card-subject">${escapeHtml(a.title)} &middot; ${a.points} pts &middot; ${dueLabel}</div>
                    </div>
                </div>
                ${a.description ? `<p style="color:var(--text-muted); font-size:12.5px; margin-bottom:10px;">${escapeHtml(a.description)}</p>` : ''}
                <div class="submission-status ${a.status}" style="display:inline-block; margin-bottom:10px;">${statusLabel(a)}</div>
                ${canSubmit ? `
                    <form class="assignment-submit-form" data-assignment-id="${a.id}" style="display:flex; gap:8px; align-items:center;">
                        <input type="file" required>
                        <button type="submit" class="modal-submit-btn" style="width:auto; padding:8px 14px;">${a.status === 'submitted' ? 'Resubmit' : 'Submit'}</button>
                    </form>
                ` : ''}
            </div>
        `;
    }

    loadAssignments();
});