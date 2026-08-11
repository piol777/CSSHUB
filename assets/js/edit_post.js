document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editPostModal');
    if (!editModal) return;

    const closeEditBtn = document.getElementById('closeEditPostModal');
    const editForm = document.getElementById('editPostForm');
    const editCourseSelect = document.getElementById('editPostCourse');
    const toast = document.getElementById('toast');

    // Populate the course dropdown once
    fetch('../api/courses.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            data.courses.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name + ' (' + c.code + ')';
                editCourseSelect.appendChild(opt);
            });
        });

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    function openEditModal(card) {
        document.getElementById('editPostId').value = card.dataset.postId;
        document.getElementById('editPostTitle').value = card.dataset.postTitle || '';
        document.getElementById('editPostContent').value = card.dataset.postContent || '';
        document.getElementById('editPostCourse').value = card.dataset.courseId || '';
        document.getElementById('editPostYearLevel').value = card.dataset.yearLevel || '';
        document.getElementById('editPostSection').value = card.dataset.section || '';
        editModal.classList.add('open');
    }

    function closeEditModal() {
        editModal.classList.remove('open');
    }

    if (closeEditBtn) closeEditBtn.addEventListener('click', closeEditModal);
    editModal.addEventListener('click', function (e) {
        if (e.target === editModal) closeEditModal();
    });

    // ===== Kebab menu open/close (event delegation — works for every post card) =====
    document.addEventListener('click', function (e) {
        const toggleBtn = e.target.closest('[data-menu-toggle]');

        // Close any open dropdowns first
        document.querySelectorAll('.post-menu-dropdown.open').forEach(dd => {
            if (!toggleBtn || dd !== toggleBtn.nextElementSibling) {
                dd.classList.remove('open');
            }
        });

        if (toggleBtn) {
            toggleBtn.nextElementSibling.classList.toggle('open');
            return;
        }

        // ===== Edit button =====
        const editBtn = e.target.closest('.post-edit-btn');
        if (editBtn) {
            const card = editBtn.closest('.post-card');
            openEditModal(card);
            return;
        }

        // ===== Delete button =====
        const deleteBtn = e.target.closest('.post-delete-btn');
        if (deleteBtn) {
            const card = deleteBtn.closest('.post-card');
            const postId = card.dataset.postId;

            if (!confirm('Delete this post? This cannot be undone.')) return;

            const formData = new FormData();
            formData.append('id', postId);

            fetch('../api/delete_post.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Failed to delete post.');
                        return;
                    }
                    card.remove();
                    showToast('Post deleted.');
                })
                .catch(() => alert('Something went wrong. Please try again.'));
            return;
        }
    });

    // ===== Submit edit form =====
    editForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(editForm);

        fetch('../api/update_post.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Failed to update post.');
                    return;
                }
                closeEditModal();
                showToast('Post updated.');
                setTimeout(() => location.reload(), 800);
            })
            .catch(() => alert('Something went wrong. Please try again.'));
    });
});