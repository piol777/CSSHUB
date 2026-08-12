document.addEventListener('DOMContentLoaded', function () {
    const directoryToggle = document.getElementById('directoryToggle');
    const directoryWidget = document.getElementById('directoryWidget');
    const directoryBackBtn = document.getElementById('directoryBackBtn');
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

    // Panel stays open by default on desktop; on mobile it only opens when tapped (fullscreen)
    if (window.innerWidth > 768) {
        directoryWidget.classList.add('open');
    }

    // Load all students immediately (default filter: all courses, all year levels)
    loadStudents();

    if (directoryToggle) {
        directoryToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            directoryWidget.classList.toggle('open');
        });
    }

    if (directoryBackBtn) {
        directoryBackBtn.addEventListener('click', function () {
            directoryWidget.classList.remove('open');
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
                            <button type="button" class="directory-warn-btn" title="Give warning">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 114 4L7.5 20.5 2 22l1.5-5.5z"></path></svg>
                            </button>
                        </div>
                        <div class="directory-item-name">${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}</div>
                    </div>
                `).join('');

                document.querySelectorAll('.directory-item').forEach(item => {
                    item.addEventListener('click', function () {
                        startChatWithStudent(this.dataset.userId, this.dataset.name, this.dataset.avatar);
                    });
                });

                document.querySelectorAll('.directory-warn-btn').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const item = btn.closest('.directory-item');
                        window.openWarningModal(item.dataset.userId, item.dataset.name);
                    });
                });
            });
    }

    // ===== Warning modal (isang instance lang) =====
    let warningModal = document.getElementById('warningModal');
    if (!warningModal) {
        warningModal = document.createElement('div');
        warningModal.id = 'warningModal';
        warningModal.className = 'modal-overlay';
        warningModal.innerHTML = `
            <div class="modal-box">
                <div class="modal-header">
                    <span id="warningModalName">Warning</span>
                    <button type="button" class="modal-close-btn" id="closeWarningModal">&times;</button>
                </div>
                <div class="warning-modal-body">
                    <div class="warning-count-label" id="warningCountLabel">Warnings: 0 / 3</div>
                    <div class="warning-history" id="warningHistory"></div>
                    <textarea id="warningReasonInput" class="warning-reason-input" rows="3" placeholder="Reason type here.."></textarea>
                    <button type="button" class="modal-submit-btn" id="submitWarningBtn">Warning</button>
                    <button type="button" class="warning-reset-btn" id="resetWarningBtn" style="display:none;">Reset Warnings</button>
                </div>
            </div>
        `;
        document.body.appendChild(warningModal);

        document.getElementById('closeWarningModal').addEventListener('click', function () {
            warningModal.classList.remove('open');
        });
        warningModal.addEventListener('click', function (e) {
            if (e.target === warningModal) warningModal.classList.remove('open');
        });
    }

    window.openWarningModal = function (studentId, name) {
        document.getElementById('warningModalName').textContent = name;
        document.getElementById('warningReasonInput').value = '';
        warningModal.dataset.studentId = studentId;
        loadWarningInfo(studentId);
        warningModal.classList.add('open');
    };

    function loadWarningInfo(studentId) {
        fetch('../api/student_warning_count.php?student_id=' + studentId)
            .then(res => res.json())
            .then(function (data) {
                if (!data.success) return;
                document.getElementById('warningCountLabel').textContent = 'Warnings: ' + data.warning_count + ' / 3';
                document.getElementById('warningHistory').innerHTML = data.warnings.map(w => `
                    <div class="warning-history-item">
                        <strong>${escapeHtml(w.first_name)} ${escapeHtml(w.last_name)}:</strong> ${escapeHtml(w.reason)}
                    </div>
                `).join('') || '<div class="warning-history-empty">Wala pang warning.</div>';
                document.getElementById('resetWarningBtn').style.display = data.warning_count > 0 ? 'block' : 'none';
            });
    }

    document.getElementById('submitWarningBtn').addEventListener('click', function () {
        const studentId = warningModal.dataset.studentId;
        const reason = document.getElementById('warningReasonInput').value.trim();
        if (!reason) { alert('Ilagay ang dahilan ng warning.'); return; }

        const fd = new FormData();
        fd.append('student_id', studentId);
        fd.append('reason', reason);

        fetch('../api/give_warning.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(function (res) {
                if (res.success) {
                    document.getElementById('warningReasonInput').value = '';
                    loadWarningInfo(studentId);
                } else {
                    alert(res.message || 'Failed to give warning.');
                }
            });
    });

    document.getElementById('resetWarningBtn').addEventListener('click', function () {
        const studentId = warningModal.dataset.studentId;
        if (!confirm('I-reset ang warnings ng student na ito?')) return;

        const fd = new FormData();
        fd.append('student_id', studentId);

        fetch('../api/reset_warnings.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(function (res) {
                if (res.success) loadWarningInfo(studentId);
            });
    });

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