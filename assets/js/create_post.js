document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('openCreatePostModal');
    const closeBtn = document.getElementById('closeCreatePostModal');
    const modal = document.getElementById('createPostModal');
    const form = document.getElementById('createPostForm');
    const courseSelect = document.getElementById('postCourse');
    const toast = document.getElementById('toast');
    const createPanel = document.getElementById('createPanel');
    const imageInput = document.getElementById('postImages');
    const imagePreviewRow = document.getElementById('imagePreviewRow');

    if (!modal) return;

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

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            imagePreviewRow.innerHTML = '';

            if (this.files.length > 2) {
                alert('You can only select up to 2 images.');
                this.value = '';
                return;
            }

            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview-thumb';
                    imagePreviewRow.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    function openModal() {
        if (createPanel) createPanel.classList.remove('open');
        modal.classList.add('open');
    }

    function closeModal() {
        modal.classList.remove('open');
    }

    function showToast(message) {
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    if (openBtn) {
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openModal();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('title', document.getElementById('postTitle').value.trim());
        formData.append('content', document.getElementById('postContent').value.trim());
        formData.append('target_course_id', document.getElementById('postCourse').value);
        formData.append('target_year_level', document.getElementById('postYearLevel').value);
        formData.append('target_section_label', document.getElementById('postSection').value.trim());

        if (imageInput && imageInput.files.length > 0) {
            Array.from(imageInput.files).forEach(file => {
                formData.append('images[]', file);
            });
        }

        fetch('../api/create_post.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Failed to post.');
                    return;
                }
                closeModal();
                form.reset();
                imagePreviewRow.innerHTML = '';
                showToast('Announcement posted successfully!');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(() => {
                alert('Something went wrong. Please try again.');
            });
    });
});