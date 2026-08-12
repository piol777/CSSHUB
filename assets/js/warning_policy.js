document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('warningToggle');
    const panel = document.getElementById('warningPolicyPanel');
    const badge = document.getElementById('warningBadge');
    if (!toggle || !panel) return;

    function loadWarningStatus() {
        fetch('../api/student_warning_count.php')
            .then(res => res.json())
            .then(function (data) {
                if (!data.success) return;

                document.getElementById('warningPolicyCount').textContent = data.warning_count;
                document.getElementById('warningPolicyCount2').textContent = data.warning_count;

                if (data.warning_count > 0) {
                    badge.textContent = data.warning_count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.classList.toggle('open');
        loadWarningStatus();
    });

    document.addEventListener('click', function (e) {
        if (panel.classList.contains('open') && !panel.contains(e.target) && e.target !== toggle) {
            panel.classList.remove('open');
        }
    });

    loadWarningStatus();
});