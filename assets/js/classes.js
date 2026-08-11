document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('classesGrid');
    if (!grid) return; // Only exists on classes.php

    const modal = document.getElementById('createClassModal');
    const openBtn = document.getElementById('openCreateClassModal');
    const closeBtn = document.getElementById('closeCreateClassModal');
    const form = document.getElementById('createClassForm');
    const toast = document.getElementById('toast');
    const colorRow = document.getElementById('colorSwatchRow');
    const colorInput = document.getElementById('classColorHex');

    function showToast(message) {
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2500);
    }

    function openModal() { modal.classList.add('open'); }
    function closeModal() { modal.classList.remove('open'); form.reset(); }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    colorRow.querySelectorAll('.color-swatch').forEach(function (btn) {
        btn.addEventListener('click', function () {
            colorRow.querySelectorAll('.color-swatch').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            colorInput.value = btn.dataset.color;
        });
    });
    colorRow.querySelector('.color-swatch').classList.add('active');

    function loadClasses() {
        fetch('../api/class_sections.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.classes.length === 0) {
                    grid.innerHTML = '<div class="classes-empty">No classes yet. Click "Add Class" to create one.</div>';
                    return;
                }
                grid.innerHTML = data.classes.map(renderCard).join('');

                grid.querySelectorAll('.class-delete-btn').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (!confirm('Delete this class section?')) return;
                        const fd = new FormData();
                        fd.append('id', btn.dataset.id);
                        fetch('../api/delete_class_section.php', { method: 'POST', body: fd })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success) {
                                    showToast('Class deleted.');
                                    loadClasses();
                                }
                            });
                    });
                });
            })
            .catch(() => {
                grid.innerHTML = '<div class="classes-empty">Failed to load classes.</div>';
            });
    }

    function renderCard(cls) {
        const attendance = cls.attendance_pct !== null ? cls.attendance_pct + '%' : '—';
        const gradeAvg = cls.grade_average !== null ? cls.grade_average + '%' : '—';
        return `
            <div class="class-card" style="border-left-color:${cls.color_hex}">
                <div class="class-card-top">
                    <div class="class-card-icon" style="background:${cls.color_hex}">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87"></path><path d="M16 3.13a4 4 0 010 7.75"></path></svg>
                    </div>
                    <div>
                        <div class="class-card-title">${cls.course_code} ${cls.year_level}-${cls.section_label}</div>
                        <div class="class-card-subject">${escapeHtml(cls.subject_name)}</div>
                    </div>
                    <button type="button" class="class-delete-btn" data-id="${cls.id}" title="Delete class">&times;</button>
                </div>
                <div class="class-card-stats">
                    <div class="class-stat"><span class="class-stat-label">Students</span><span class="class-stat-value">${cls.students_count}</span></div>
                    <div class="class-stat"><span class="class-stat-label">Attendance</span><span class="class-stat-value">${attendance}</span></div>
                    <div class="class-stat"><span class="class-stat-label">Assignments Pending</span><span class="class-stat-value">${cls.assignments_pending}</span></div>
                    <div class="class-stat"><span class="class-stat-label">Grades Average</span><span class="class-stat-value">${gradeAvg}</span></div>
                </div>
                <a href="class_details.php?id=${cls.id}" class="class-view-details-btn">View Class Details &rarr;</a>
            </div>
        `;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData();
        fd.append('subject_name', document.getElementById('classSubjectName').value.trim());
        fd.append('course_id', document.getElementById('classCourse').value);
        fd.append('year_level', document.getElementById('classYearLevel').value);
        fd.append('section_label', document.getElementById('classSectionLabel').value.trim());
        fd.append('semester_label', document.getElementById('classSemesterLabel').value.trim());
        fd.append('color_hex', colorInput.value);

        fetch('../api/create_class_section.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showToast('Class created!');
                    closeModal();
                    loadClasses();
                } else {
                    showToast(res.message || 'Failed to create class.');
                }
            });
    });

    loadClasses();
});