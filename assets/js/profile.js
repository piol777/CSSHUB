document.addEventListener('DOMContentLoaded', function () {
    const editBtn = document.getElementById('editProfileBtn');
    const modal = document.getElementById('editProfileModal');
    const closeBtn = document.getElementById('closeEditProfileModal');
    const fileInput = document.getElementById('profilePictureInput');
    const editAvatarPreview = document.getElementById('editAvatarPreview');
    const saveBtn = document.getElementById('saveProfilePictureBtn');
    const hint = document.getElementById('profileUploadHint');
    const mainAvatarPreview = document.getElementById('profileAvatarPreview');

    if (!editBtn || !modal) return;

    let selectedFile = null;

    editBtn.addEventListener('click', function () {
        modal.classList.add('open');
    });

    closeBtn.addEventListener('click', function () {
        modal.classList.remove('open');
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.classList.remove('open');
    });

    fileInput.addEventListener('change', function () {
        const file = fileInput.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            hint.textContent = 'Image must be under 5MB.';
            hint.classList.add('error');
            saveBtn.disabled = true;
            return;
        }

        selectedFile = file;
        hint.textContent = 'JPG, PNG, GIF, or WEBP. Max 5MB.';
        hint.classList.remove('error');
        saveBtn.disabled = false;

        const reader = new FileReader();
        reader.onload = function (e) {
            editAvatarPreview.style.backgroundImage = 'url(' + e.target.result + ')';
        };
        reader.readAsDataURL(file);
    });

    saveBtn.addEventListener('click', function () {
        if (!selectedFile) return;

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        const formData = new FormData();
        formData.append('profile_picture', selectedFile);

        fetch('../api/update_profile_picture.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                saveBtn.textContent = 'Save Photo';
                if (!data.success) {
                    hint.textContent = data.message || 'Upload failed.';
                    hint.classList.add('error');
                    saveBtn.disabled = false;
                    return;
                }

                const newUrl = '../' + data.profile_picture;
                if (mainAvatarPreview) mainAvatarPreview.style.backgroundImage = 'url(' + newUrl + ')';

                const sidebarAvatar = document.querySelector('.sidebar-footer .avatar-circle');
                if (sidebarAvatar) sidebarAvatar.style.backgroundImage = 'url(' + newUrl + ')';

                modal.classList.remove('open');
                selectedFile = null;
                saveBtn.disabled = true;
            })
            .catch(function () {
                saveBtn.textContent = 'Save Photo';
                hint.textContent = 'Something went wrong. Try again.';
                hint.classList.add('error');
                saveBtn.disabled = false;
            });
    });
});