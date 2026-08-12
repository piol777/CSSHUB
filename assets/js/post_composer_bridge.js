document.addEventListener('DOMContentLoaded', function () {
    const imageBtn = document.getElementById('postComposerImageBtn');
    const otherTriggers = [
        document.getElementById('postComposerVideoBtn'),
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

    // Image icon: buksan ang modal AT direktang buksan ang file picker (quick access)
    if (imageBtn) {
        imageBtn.addEventListener('click', function () {
            openPostModal();
            setTimeout(function () {
                const imageInput = document.getElementById('postImages');
                if (imageInput) imageInput.click();
            }, 150);
        });
    }
});