document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('classesGrid');
    if (!grid) return; // Only exists on classes.php

    function loadClasses() {
        fetch('../api/class_sections.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.classes.length === 0) {
                    grid.innerHTML = '<div class="classes-empty">No classes assigned to you yet.</div>';
                    return;
                }
                grid.innerHTML = data.classes.map(renderCard).join('');
            })
            .catch(() => {
                grid.innerHTML = '<div class="classes-empty">Failed to load classes.</div>';
            });
    }

    function renderCard(cls) {
        const attendance = cls.attendance_pct !== null ? cls.attendance_pct + '%' : '—';
        const gradeAvg = cls.grade_average !== null ? cls.grade_average + '%' : '—';
        return `
            <div class="class-card">
                <div class="class-card-top">
                    <div class="class-card-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87"></path><path d="M16 3.13a4 4 0 010 7.75"></path></svg>
                    </div>
                    <div>
                        <div class="class-card-title">${cls.course_code} ${cls.year_level}-${cls.section_label}</div>
                        <div class="class-card-subject">${escapeHtml(cls.subject_name)}</div>
                    </div>
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

    loadClasses();
});