document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('classOverviewToggle');
    const panel = document.getElementById('classOverviewPanel');
    const list = document.getElementById('classOverviewList');
    if (!toggle || !panel) return;

    function loadClassOverview() {
        list.innerHTML = '<div class="upcoming-empty">Loading...</div>';
        fetch('../api/class_sections.php')
            .then(res => res.json())
            .then(function (data) {
                if (!data.success || data.classes.length === 0) {
                    list.innerHTML = '<div class="classes-empty">No classes yet.</div>';
                    return;
                }
                list.innerHTML = data.classes.slice(0, 3).map(function (cls) {
                    const attendance = cls.attendance_pct !== null ? cls.attendance_pct + '%' : '—';
                    const gradeAvg = cls.grade_average !== null ? cls.grade_average + '%' : '—';
                    return `
                        <div class="class-card">
                            <div class="class-card-top">
                                <div class="class-card-icon">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                </div>
                                <div>
                                    <div class="class-card-title">${cls.course_code} ${cls.year_level}-${cls.section_label}</div>
                                    <div class="class-card-subject">${cls.subject_name}</div>
                                </div>
                            </div>
                            <div class="class-card-stats">
                                <div class="class-stat"><span class="class-stat-label">Students</span><span class="class-stat-value">${cls.students_count}</span></div>
                                <div class="class-stat"><span class="class-stat-label">Attendance</span><span class="class-stat-value">${attendance}</span></div>
                                <div class="class-stat"><span class="class-stat-label">Assignments</span><span class="class-stat-value">${cls.assignments_pending} Pending</span></div>
                                <div class="class-stat"><span class="class-stat-label">Grades</span><span class="class-stat-value">${gradeAvg}</span></div>
                            </div>
                            <a href="class_details.php?id=${cls.id}" class="class-view-details-btn">View Class Details &rarr;</a>
                        </div>
                    `;
                }).join('');
            });
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) loadClassOverview();
    });

    document.addEventListener('click', function (e) {
        if (panel.classList.contains('open') && !panel.contains(e.target) && e.target !== toggle) {
            panel.classList.remove('open');
        }
    });
});