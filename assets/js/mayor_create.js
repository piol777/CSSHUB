document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('mayorCreateToggle');
    const modal = document.getElementById('createPostModal');
    if (!toggle || !modal) return;

    const closeBtn = document.getElementById('closeCreatePostModal');
    const form = document.getElementById('createPostForm');
    const toast = document.getElementById('toast');

    toggle.addEventListener('click', () => modal.classList.add('open'));
    if (closeBtn) closeBtn.addEventListener('click', () => modal.classList.remove('open'));
    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.classList.remove('open');
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('../api/create_post.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(function (data) {
                if (!data.success) {
                    alert(data.message || 'Failed to post.');
                    return;
                }
                modal.classList.remove('open');
                form.reset();
                if (toast) {
                    toast.textContent = 'Post created!';
                    toast.classList.add('show');
                    setTimeout(() => toast.classList.remove('show'), 3000);
                }
                setTimeout(() => window.location.reload(), 800);
            });
    });
});