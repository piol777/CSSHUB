document.addEventListener('DOMContentLoaded', function () {
    const imageBtn = document.getElementById('postComposerImageBtn');
    const videoBtn = document.getElementById('postComposerVideoBtn');
    const otherTriggers = [
        document.getElementById('postComposerInput'),
        document.getElementById('postComposerAvatar')
    ];

    function openPostModal() {
        const realTrigger = document.getElementById('openCreatePostModal');
        if (realTrigger) realTrigger.click();
    }

    otherTriggers.forEach(function (el) {
        if (!el) return;
        el.addEventListener('click', openPostModal);
    });

    if (imageBtn) {
        imageBtn.addEventListener('click', function () {
            openPostModal();
            setTimeout(function () {
                const imageInput = document.getElementById('postImages');
                if (imageInput) imageInput.click();
            }, 150);
        });
    }

    if (videoBtn) {
        videoBtn.addEventListener('click', function () {
            openPostModal();
            setTimeout(function () {
                const videoInput = document.getElementById('postVideo');
                if (videoInput) videoInput.click();
            }, 150);
        });
    }
});