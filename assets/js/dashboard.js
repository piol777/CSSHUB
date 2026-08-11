document.addEventListener('DOMContentLoaded', function () {

    // ===== Online presence heartbeat =====
    function sendHeartbeat() {
        fetch('../api/heartbeat.php', { method: 'POST' }).catch(() => {});
    }
    sendHeartbeat();
    setInterval(sendHeartbeat, 30000);

    // ===== Logout confirmation =====
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            const confirmed = confirm('Are you sure you want to log out?');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    // ===== Theme Toggle: Light (default) / Dark Purple / Dark Mode =====
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const THEME_ORDER = ['light', 'dark-purple', 'dark'];

    function applyTheme(theme) {
        body.classList.remove('theme-dark-purple', 'theme-dark');
        if (theme === 'dark-purple') {
            body.classList.add('theme-dark-purple');
        } else if (theme === 'dark') {
            body.classList.add('theme-dark');
        }
        // 'light' = walang class, gagamit ng :root defaults
    }

    const savedTheme = localStorage.getItem('cdsga_theme') || 'light';
    applyTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const current = localStorage.getItem('cdsga_theme') || 'light';
            const nextTheme = THEME_ORDER[(THEME_ORDER.indexOf(current) + 1) % THEME_ORDER.length];
            applyTheme(nextTheme);
            localStorage.setItem('cdsga_theme', nextTheme);
        });
    }

    // ===== Notification dropdown =====
    const notifToggle = document.getElementById('notifToggle');
    const notifPanel = document.getElementById('notifPanel');
    const notifBadge = document.getElementById('notifBadge');
    const notifList = document.getElementById('notifList');

    // ===== Create dropdown =====
    const createToggle = document.getElementById('createToggle');
    const createPanel = document.getElementById('createPanel');

    function closeAllDropdowns() {
        if (notifPanel) notifPanel.classList.remove('open');
        if (createPanel) createPanel.classList.remove('open');
    }

    function timeAgoJs(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr.replace(' ', 'T'))) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }

    function escapeHtmlGlobal(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function avatarStyleGlobal(path) {
        return path ? ` style="background-image:url('../${path}')"` : '';
    }

    // Determine the correct dashboard path depending on current role folder (student/ or professor/)
    function getDashboardPath() {
        const path = window.location.pathname;
        if (path.includes('/professor/')) return 'dashboard.php';
        if (path.includes('/student/')) return 'dashboard.php';
        return 'dashboard.php';
    }

    function refreshBadgeOnly() {
        if (!notifBadge) return;
        fetch('../api/notifications.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                if (data.unread_count > 0) {
                    notifBadge.textContent = data.unread_count;
                    notifBadge.classList.remove('hidden');
                } else {
                    notifBadge.classList.add('hidden');
                }
            });
    }

    function loadNotifications() {
        fetch('../api/notifications.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                if (data.unread_count > 0) {
                    notifBadge.textContent = data.unread_count;
                    notifBadge.classList.remove('hidden');
                } else {
                    notifBadge.classList.add('hidden');
                }

                if (data.notifications.length === 0) {
                    notifList.innerHTML = '<div class="notif-empty">No notifications yet.</div>';
                    return;
                }

                notifList.innerHTML = data.notifications.map(n => `
                    <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" data-announcement-id="${n.announcement_id || ''}" data-comment-id="${n.comment_id || ''}" data-type="${n.type}">
                        <div class="notif-avatar"${avatarStyleGlobal(n.profile_picture)}></div>
                        <div>
                            <div class="notif-text"><strong>${escapeHtmlGlobal(n.first_name)} ${escapeHtmlGlobal(n.last_name)}</strong> ${escapeHtmlGlobal(n.message.replace(n.first_name + ' ' + n.last_name, '').trim())}</div>
                            <div class="notif-time">${timeAgoJs(n.created_at)}</div>
                        </div>
                    </div>
                `).join('');

                // Live-class notifications go straight to the Live page instead of a post
                document.querySelectorAll('.notif-item[data-type="live_started"]').forEach(item => {
                    item.addEventListener('click', function () {
                        window.location.href = 'live.php';
                    });
                });

                // Clicking a notification navigates to the related post (and, for comments, the exact comment) and highlights it
                document.querySelectorAll('.notif-item').forEach(item => {
                    if (item.dataset.type === 'live_started') return;
                    item.addEventListener('click', function () {
                        const announcementId = this.dataset.announcementId;
                        const commentId = this.dataset.commentId;
                        const type = this.dataset.type;
                        if (!announcementId) return;
                        let url = getDashboardPath() + '?highlight=' + announcementId + '&type=' + type;
                        if (commentId) url += '&comment=' + commentId;
                        window.location.href = url;
                    });
                });
            });
    }

    if (notifToggle) {
        // Show badge count immediately on page load, without needing to click
        refreshBadgeOnly();

        notifToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = notifPanel.classList.contains('open');

            closeAllDropdowns();

            if (!isOpen) {
                notifPanel.classList.add('open');
                loadNotifications();

                setTimeout(() => {
                    fetch('../api/notifications.php', { method: 'POST' })
                        .then(() => {
                            notifBadge.classList.add('hidden');
                        });
                }, 1500);
            }
        });
    }

    if (createToggle) {
        createToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = createPanel.classList.contains('open');

            closeAllDropdowns();

            if (!isOpen) {
                createPanel.classList.add('open');
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (notifPanel && createPanel) {
            if (!notifPanel.contains(e.target) && e.target !== notifToggle &&
                !createPanel.contains(e.target) && e.target !== createToggle) {
                closeAllDropdowns();
            }
        }
    });

    // ===== Sidebar menu toggle =====
    const menuToggle = document.getElementById('menuToggle');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function closeSidebar() {
        appSidebar.classList.remove('open');
        sidebarOverlay.classList.remove('open');
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            closeAllDropdowns();
            appSidebar.classList.add('open');
            sidebarOverlay.classList.add('open');
        });

        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // ===== Like button (AJAX) =====
    document.querySelectorAll('.like-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const announcementId = this.dataset.id;
            const countEl = this.querySelector('.like-count');

            fetch('../api/toggle_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'announcement_id=' + encodeURIComponent(announcementId)
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    countEl.textContent = data.like_count;
                    if (data.liked) {
                        btn.classList.add('liked');
                    } else {
                        btn.classList.remove('liked');
                    }
                })
                .catch(() => {
                    alert('Something went wrong. Please try again.');
                });
        });
    });

    // ===== Comment toggle + load + submit =====
    document.querySelectorAll('.comment-toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const announcementId = this.dataset.id;
            const section = document.getElementById('comments-' + announcementId);
            const isOpen = section.classList.contains('open');

            document.querySelectorAll('.comment-section.open').forEach(s => s.classList.remove('open'));

            if (!isOpen) {
                section.classList.add('open');
                loadComments(announcementId);
            }
        });
    });

    function loadComments(announcementId, afterLoad) {
        const list = document.getElementById('comment-list-' + announcementId);
        list.innerHTML = '<div class="comment-empty">Loading...</div>';

        fetch('../api/comments.php?announcement_id=' + encodeURIComponent(announcementId))
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    list.innerHTML = '<div class="comment-empty">Failed to load comments.</div>';
                    return;
                }
                renderComments(announcementId, data.comments);
                if (typeof afterLoad === 'function') afterLoad();
            })
            .catch(() => {
                list.innerHTML = '<div class="comment-empty">Failed to load comments.</div>';
            });
    }

    function renderComments(announcementId, comments) {
        const list = document.getElementById('comment-list-' + announcementId);

        if (comments.length === 0) {
            list.innerHTML = '<div class="comment-empty">No comments yet. Be the first!</div>';
            return;
        }

        list.innerHTML = comments.map(c => {
            const isReply = c.commenter_role === 'professor';
            return `
            <div class="comment-item ${isReply ? 'is-reply' : ''}" id="comment-${c.id}">
                <div class="comment-avatar" data-profile-user-id="${c.commenter_id}"${c.profile_picture ? ` style="background-image:url('../${c.profile_picture}')"` : ''}></div>
                <div class="comment-item-body">
                    <div class="comment-bubble">
                        <div class="comment-author" data-profile-user-id="${c.commenter_id}">${isReply ? 'Prof. ' : ''}${escapeHtmlGlobal(c.first_name)} ${escapeHtmlGlobal(c.last_name)}</div>
                        <div>${escapeHtmlGlobal(c.content)}</div>
                    </div>
                    <div class="comment-actions-row">
                        <button class="comment-action-link comment-like-btn ${c.user_liked > 0 ? 'liked' : ''}" data-comment-id="${c.id}">Like</button>
                        <button class="comment-action-link comment-reply-btn" data-announcement-id="${announcementId}">Reply</button>
                        <span class="comment-like-count" id="comment-like-count-${c.id}">${c.like_count > 0 ? c.like_count + (c.like_count == 1 ? ' like' : ' likes') : ''}</span>
                    </div>
                </div>
            </div>
        `;
        }).join('');

        list.scrollTop = list.scrollHeight;
        attachCommentActionHandlers(announcementId);
    }

    function attachCommentActionHandlers(announcementId) {
        document.querySelectorAll('#comment-list-' + announcementId + ' .comment-like-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const commentId = this.dataset.commentId;

                fetch('../api/toggle_comment_like.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'comment_id=' + encodeURIComponent(commentId)
                })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) return;
                        this.classList.toggle('liked', data.liked);
                        const countEl = document.getElementById('comment-like-count-' + commentId);
                        countEl.textContent = data.like_count > 0 ? data.like_count + (data.like_count == 1 ? ' like' : ' likes') : '';
                    });
            });
        });

        document.querySelectorAll('#comment-list-' + announcementId + ' .comment-reply-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const form = document.querySelector('.comment-form[data-id="' + announcementId + '"]');
                if (form) {
                    const input = form.querySelector('.comment-text-input');
                    input.focus();
                }
            });
        });
    }

    document.querySelectorAll('.comment-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const announcementId = this.dataset.id;
            const input = this.querySelector('.comment-text-input');
            const content = input.value.trim();

            if (!content) return;

            fetch('../api/comments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'announcement_id=' + encodeURIComponent(announcementId) + '&content=' + encodeURIComponent(content)
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Failed to post comment.');
                        return;
                    }

                    const list = document.getElementById('comment-list-' + announcementId);
                    const emptyMsg = list.querySelector('.comment-empty');
                    if (emptyMsg) emptyMsg.remove();

                    const item = document.createElement('div');
                    item.className = 'comment-item';
                    item.id = 'comment-' + data.comment.id;
                    item.innerHTML = `
                        <div class="comment-avatar" data-profile-user-id="${data.comment.commenter_id}"${data.comment.profile_picture ? ` style="background-image:url('../${data.comment.profile_picture}')"` : ''}></div>
                        <div class="comment-bubble">
                            <div class="comment-author" data-profile-user-id="${data.comment.commenter_id}">${data.comment.commenter_role === 'professor' ? 'Prof. ' : ''}${escapeHtmlGlobal(data.comment.first_name)} ${escapeHtmlGlobal(data.comment.last_name)}</div>
                            <div>${escapeHtmlGlobal(data.comment.content)}</div>
                        </div>
                    `;
                    list.appendChild(item);
                    list.scrollTop = list.scrollHeight;

                    const countBadge = document.getElementById('comment-count-' + announcementId);
                    if (countBadge) countBadge.textContent = data.comment_count;

                    input.value = '';
                })
                .catch(() => {
                    alert('Something went wrong. Please try again.');
                });
        });
    });

    // ===== Image Lightbox (post photos, click-to-enlarge with swipe) =====
    const lightboxOverlay = document.getElementById('lightboxOverlay');

    if (lightboxOverlay) {
        const lightboxImage = document.getElementById('lightboxImage');
        const lightboxClose = document.getElementById('lightboxClose');
        const lightboxPrev = document.getElementById('lightboxPrev');
        const lightboxNext = document.getElementById('lightboxNext');
        const lightboxDots = document.getElementById('lightboxDots');

        let currentImages = [];
        let currentIndex = 0;

        function renderLightboxDots() {
            if (currentImages.length <= 1) {
                lightboxDots.classList.add('hidden');
                lightboxDots.innerHTML = '';
                return;
            }
            lightboxDots.classList.remove('hidden');
            lightboxDots.innerHTML = currentImages.map((_, i) =>
                `<span class="lightbox-dot ${i === currentIndex ? 'active' : ''}" data-index="${i}"></span>`
            ).join('');

            lightboxDots.querySelectorAll('.lightbox-dot').forEach(dot => {
                dot.addEventListener('click', function () {
                    currentIndex = parseInt(this.dataset.index, 10);
                    updateLightboxImage();
                });
            });
        }

        function updateLightboxImage() {
            lightboxImage.src = currentImages[currentIndex];
            const hasMultiple = currentImages.length > 1;
            lightboxPrev.classList.toggle('hidden', !hasMultiple);
            lightboxNext.classList.toggle('hidden', !hasMultiple);
            renderLightboxDots();
        }

        function openLightbox(images, startIndex) {
            currentImages = images;
            currentIndex = startIndex;
            updateLightboxImage();
            lightboxOverlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightboxOverlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        function showNext() {
            if (currentImages.length <= 1) return;
            currentIndex = (currentIndex + 1) % currentImages.length;
            updateLightboxImage();
        }

        function showPrev() {
            if (currentImages.length <= 1) return;
            currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
            updateLightboxImage();
        }

        // Open lightbox when any post image is clicked
        document.querySelectorAll('.post-images-grid img').forEach(function (img) {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function () {
                const grid = this.closest('.post-images-grid');
                const images = Array.from(grid.querySelectorAll('img')).map(i => i.getAttribute('src'));
                const startIndex = Array.from(grid.querySelectorAll('img')).indexOf(this);
                openLightbox(images, startIndex);
            });
        });

        lightboxClose.addEventListener('click', closeLightbox);
        lightboxNext.addEventListener('click', showNext);
        lightboxPrev.addEventListener('click', showPrev);

        // Click outside the image (on the dark blurred backdrop) closes it
        lightboxOverlay.addEventListener('click', function (e) {
            if (e.target === lightboxOverlay) closeLightbox();
        });

        // Keyboard support
        document.addEventListener('keydown', function (e) {
            if (!lightboxOverlay.classList.contains('open')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') showNext();
            if (e.key === 'ArrowLeft') showPrev();
        });

        // Swipe support (touch)
        let touchStartX = 0;
        let touchEndX = 0;

        lightboxOverlay.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        lightboxOverlay.addEventListener('touchend', function (e) {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchEndX - touchStartX;
            if (Math.abs(diff) < 40) return; // ignore small taps
            if (diff < 0) {
                showNext(); // swiped left -> next image
            } else {
                showPrev(); // swiped right -> previous image
            }
        }, { passive: true });
    }

    // ===== Handle notification click-through: scroll + highlight target post (and exact comment, if any) =====
    if (typeof HIGHLIGHT_TARGET !== 'undefined' && HIGHLIGHT_TARGET) {
        const targetPost = document.getElementById('post-' + HIGHLIGHT_TARGET);
        if (targetPost) {
            setTimeout(() => {
                const hasCommentTarget = typeof HIGHLIGHT_COMMENT_ID !== 'undefined' && HIGHLIGHT_COMMENT_ID;

                // If it's a comment/reply notification, open the comment section and highlight the exact comment
                if (typeof HIGHLIGHT_TYPE !== 'undefined' && HIGHLIGHT_TYPE === 'comment') {
                    const commentSection = document.getElementById('comments-' + HIGHLIGHT_TARGET);
                    if (commentSection) {
                        commentSection.classList.add('open');
                        loadComments(HIGHLIGHT_TARGET, function () {
                            if (hasCommentTarget) {
                                const targetComment = document.getElementById('comment-' + HIGHLIGHT_COMMENT_ID);
                                if (targetComment) {
                                    targetComment.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    targetComment.classList.add('highlight-flash');
                                    setTimeout(() => targetComment.classList.remove('highlight-flash'), 5000);
                                    return;
                                }
                            }
                            // Fallback: no exact comment found, highlight the post instead
                            targetPost.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            targetPost.classList.add('highlight-flash');
                            setTimeout(() => targetPost.classList.remove('highlight-flash'), 5000);
                        });
                        return;
                    }
                }

                targetPost.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetPost.classList.add('highlight-flash');
                setTimeout(() => targetPost.classList.remove('highlight-flash'), 5000);
            }, 300);
        }
    }

});

// ===== Pinned Daily Verse — subtle nudge on scroll =====
(function () {
    const verseCard = document.getElementById('verseCard');
    if (!verseCard) return; // Only exists on student Home dashboard

    let nudgeTimeout;
    window.addEventListener('scroll', function () {
        verseCard.classList.add('nudge');
        clearTimeout(nudgeTimeout);
        nudgeTimeout = setTimeout(function () {
            verseCard.classList.remove('nudge');
        }, 800);
    }, { passive: true });
})();