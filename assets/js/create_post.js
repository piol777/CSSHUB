document.addEventListener('DOMContentLoaded', function () {

    const videoInput = document.getElementById('postVideo');
    const videoPreviewRow = document.getElementById('videoPreviewRow');

    if (videoInput) {
        videoInput.addEventListener('change', function () {
            videoPreviewRow.innerHTML = '';
            if (!this.files.length) return;

            const file = this.files[0];
            if (file.size > 50 * 1024 * 1024) {
                alert('The video must be under 50MB.');
                this.value = '';
                return;
            }

            const chip = document.createElement('div');
            chip.className = 'attachment-chip';
            chip.innerHTML = `
                <div class="attachment-chip-icon" style="background-color:#8b5cf6;">MP4</div>
                <div class="attachment-chip-info">
                    <span class="attachment-chip-name">${file.name}</span>
                    <span class="attachment-chip-size">${(file.size / (1024 * 1024)).toFixed(1)} MB</span>
                </div>
                <button type="button" class="attachment-chip-remove">&times;</button>
            `;
            chip.querySelector('.attachment-chip-remove').addEventListener('click', function () {
                videoInput.value = '';
                videoPreviewRow.innerHTML = '';
            });
            videoPreviewRow.appendChild(chip);
        });
    }
    
    const openBtn = document.getElementById('openCreatePostModal');
    const closeBtn = document.getElementById('closeCreatePostModal');
    const modal = document.getElementById('createPostModal');
    const form = document.getElementById('createPostForm');
    const courseSelect = document.getElementById('postCourse');
    const toast = document.getElementById('toast');
    const createPanel = document.getElementById('createPanel');
    const imageInput = document.getElementById('postImages');
    const imagePreviewRow = document.getElementById('imagePreviewRow');
    const attachmentInput = document.getElementById('postAttachment');
    const attachmentPreviewRow = document.getElementById('attachmentPreviewRow');

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

    const cleanUploadBox = document.getElementById('cleanUploadBox');

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            imagePreviewRow.innerHTML = '';

            if (this.files.length > 2) {
                alert('You can only select up to 2 images.');
                this.value = '';
                return;
            }

            if (cleanUploadBox) {
                cleanUploadBox.classList.toggle('hidden', this.files.length > 0);
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

    // ===== Attachment file chip preview =====
    const ATTACHMENT_ICON_MAP = {
        pdf: { label: 'PDF', color: '#e5484d' },
        doc: { label: 'DOC', color: '#2952ff' },
        docx: { label: 'DOC', color: '#2952ff' },
        ppt: { label: 'PPT', color: '#e8730f' },
        pptx: { label: 'PPT', color: '#e8730f' },
        xls: { label: 'XLS', color: '#2f9e44' },
        xlsx: { label: 'XLS', color: '#2f9e44' },
        txt: { label: 'TXT', color: '#6b6885' },
        csv: { label: 'CSV', color: '#2f9e44' },
        zip: { label: 'ZIP', color: '#6b6885' },
        rar: { label: 'RAR', color: '#6b6885' },
        rtf: { label: 'RTF', color: '#6b6885' },
        odt: { label: 'ODT', color: '#2952ff' }
    };

    function formatFileSize(bytes) {
        if (bytes >= 1024 * 1024) {
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function renderAttachmentChip(file) {
        attachmentPreviewRow.innerHTML = '';

        const ext = file.name.split('.').pop().toLowerCase();
        const meta = ATTACHMENT_ICON_MAP[ext] || { label: 'FILE', color: '#6b6885' };

        const chip = document.createElement('div');
        chip.className = 'attachment-chip';

        const icon = document.createElement('div');
        icon.className = 'attachment-chip-icon';
        icon.style.backgroundColor = meta.color;
        icon.textContent = meta.label;

        const info = document.createElement('div');
        info.className = 'attachment-chip-info';
        info.innerHTML =
            '<span class="attachment-chip-name">' + file.name + '</span>' +
            '<span class="attachment-chip-size">' + formatFileSize(file.size) + '</span>';

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'attachment-chip-remove';
        removeBtn.textContent = '\u00d7';
        removeBtn.addEventListener('click', function () {
            attachmentInput.value = '';
            attachmentPreviewRow.innerHTML = '';
        });

        chip.appendChild(icon);
        chip.appendChild(info);
        chip.appendChild(removeBtn);
        attachmentPreviewRow.appendChild(chip);
    }

    if (attachmentInput) {
        attachmentInput.addEventListener('change', function () {
            if (!this.files.length) {
                attachmentPreviewRow.innerHTML = '';
                return;
            }

            const file = this.files[0];
            const maxSize = 20 * 1024 * 1024;

            if (file.size > maxSize) {
                alert('The attachment must be under 20MB.');
                this.value = '';
                attachmentPreviewRow.innerHTML = '';
                return;
            }

            renderAttachmentChip(file);
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

        if (attachmentInput && attachmentInput.files.length > 0) {
            formData.append('attachment', attachmentInput.files[0]);
        }

        if (videoInput && videoInput.files.length > 0) {
            formData.append('video', videoInput.files[0]);
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
                attachmentPreviewRow.innerHTML = '';
                if (videoPreviewRow) videoPreviewRow.innerHTML = '';
                if (cleanUploadBox) cleanUploadBox.classList.remove('hidden');
                showToast('Announcement posted successfully!');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(() => {
                alert('Something went wrong. Please try again.');
            });
    });

    const attachLink = document.querySelector('.clean-attach-link');
    if (attachLink && attachmentInput) {
        attachLink.addEventListener('click', function (e) {
            e.preventDefault();
            attachmentInput.click();
        });
    }
});