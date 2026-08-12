document.addEventListener('DOMContentLoaded', function () {

    // ===== Double-click kahit saan sa post frame = Like (parang Instagram) =====
    function showHeartBurst(container, clientX, clientY) {
        const rect = container.getBoundingClientRect();
        const heart = document.createElement('div');
        heart.className = 'heart-burst';
        heart.innerHTML = '<svg viewBox="0 0 24 24" fill="#ff3040"><path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z"></path></svg>';
        heart.style.left = (clientX - rect.left) + 'px';
        heart.style.top = (clientY - rect.top) + 'px';
        container.appendChild(heart);
        setTimeout(() => heart.remove(), 800);
    }

    document.querySelectorAll('.post-images-grid').forEach(function (grid) {
        // I-block ang browser's native text/image selection sa unang click pa lang
        grid.addEventListener('mousedown', function (e) {
            if (e.detail > 1) e.preventDefault();
        });
    });

    document.addEventListener('dblclick', function (e) {
        const postCard = e.target.closest('.post-card');
        if (!postCard) return;

        e.preventDefault();
        window.getSelection().removeAllRanges();

        // Kanselahin ang naka-antay na "open lightbox" mula sa unang click ng double-click
        if (postCard._pendingImgClick) {
            clearTimeout(postCard._pendingImgClick);
            postCard._pendingImgClick = null;
        }

        const likeBtn = postCard.querySelector('.like-btn');
        if (likeBtn && !likeBtn.classList.contains('liked')) {
            likeBtn.click();
        }
        showHeartBurst(postCard, e.clientX, e.clientY);
    });

    // ===== Online presence heartbeat =====
    function sendHeartbeat() {
        fetch('../api/heartbeat.php', { method: 'POST' }).catch(() => {});
    }
    sendHeartbeat();
    setInterval(sendHeartbeat, 30000);

    // ===== Logout confirmation (sidebar logout link + profile dropdown logout link) =====
    document.querySelectorAll('.logout-confirm-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const confirmed = confirm('Are you sure you want to log out?');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });

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

    // ===== Profile dropdown (top-right nav) =====
    const profileNavToggle = document.getElementById('profileNavToggle');
    const profileNavDropdown = document.getElementById('profileNavDropdown');

    // ===== Top nav student search (professor) =====
    const navSearchInput = document.getElementById('navSearchInput');
    const navSearchBtn = document.getElementById('navSearchBtn');
    const navSearchResults = document.getElementById('navSearchResults');

    function closeAllDropdowns() {
        if (notifPanel) notifPanel.classList.remove('open');
        if (createPanel) createPanel.classList.remove('open');
        if (profileNavDropdown) profileNavDropdown.classList.remove('open');
        if (navSearchResults) navSearchResults.classList.remove('open');
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

                // ===== I-combine ang magkakasunod na "liked your post" sa parehong post =====
                const grouped = [];
                const likeGroupIndex = {};

                data.notifications.forEach(function (n) {
                    const actorName = n.first_name + ' ' + n.last_name;
                    const actionText = n.message.replace(actorName, '').trim();

                    if (n.type === 'like' && n.announcement_id) {
                        const key = 'like-' + n.announcement_id;
                        if (likeGroupIndex[key] !== undefined) {
                            grouped[likeGroupIndex[key]].actors.push(actorName);
                            return;
                        }
                        likeGroupIndex[key] = grouped.length;
                    }

                    grouped.push({
                        type: n.type,
                        announcement_id: n.announcement_id,
                        comment_id: n.comment_id,
                        is_read: n.is_read,
                        created_at: n.created_at,
                        profile_picture: n.profile_picture,
                        actors: [actorName],
                        actionText: actionText
                    });
                });

                // ===== I-hati sa New / Today / Earlier =====
                const todayStr = new Date().toDateString();
                const sections = { New: [], Today: [], Earlier: [] };

                grouped.forEach(function (n) {
                    if (n.is_read == 0) {
                        sections.New.push(n);
                    } else if (new Date(n.created_at).toDateString() === todayStr) {
                        sections.Today.push(n);
                    } else {
                        sections.Earlier.push(n);
                    }
                });

                function renderGroup(n) {
                    const names = n.actors.length > 2
                        ? `${escapeHtmlGlobal(n.actors[0])}, ${escapeHtmlGlobal(n.actors[1])} and ${n.actors.length - 2} others`
                        : n.actors.map(escapeHtmlGlobal).join(' and ');

                    return `
                        <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" data-announcement-id="${n.announcement_id || ''}" data-comment-id="${n.comment_id || ''}" data-type="${n.type}">
                            <div class="notif-avatar"${avatarStyleGlobal(n.profile_picture)}></div>
                            <div>
                                <div class="notif-text"><strong>${names}</strong> ${escapeHtmlGlobal(n.actionText)}</div>
                                <div class="notif-time">${timeAgoJs(n.created_at)}</div>
                            </div>
                        </div>
                    `;
                }

                let html = '';
                ['New', 'Today', 'Earlier'].forEach(function (label) {
                    if (sections[label].length === 0) return;
                    html += `<div class="notif-section-header">${label}</div>`;
                    html += sections[label].map(renderGroup).join('');
                });
                notifList.innerHTML = html;

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

    if (profileNavToggle) {
        profileNavToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = profileNavDropdown.classList.contains('open');

            closeAllDropdowns();

            if (!isOpen) {
                profileNavDropdown.classList.add('open');
            }
        });
    }

    if (navSearchInput && navSearchResults) {
        let navSearchDebounce = null;

        function renderNavSearchResults(students) {
            if (!students.length) {
                navSearchResults.innerHTML = '<div class="nav-search-empty">No students found.</div>';
                return;
            }
            navSearchResults.innerHTML = students.map(function (s) {
                const meta = [s.course_code, s.year_level ? (s.year_level + 'Y') : null, s.section_label]
                    .filter(Boolean).join(' &middot; ');
                return `
                    <div class="nav-search-item" data-profile-user-id="${s.id}">
                        <div class="avatar-circle nav-search-item-avatar"${avatarStyleGlobal(s.profile_picture)}></div>
                        <div class="nav-search-item-info">
                            <div class="nav-search-item-name">${escapeHtmlGlobal(s.first_name + ' ' + s.last_name)}</div>
                            ${meta ? `<div class="nav-search-item-meta">${meta}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function runNavSearch() {
            const q = navSearchInput.value.trim();
            if (q.length === 0) {
                navSearchResults.classList.remove('open');
                return;
            }
            navSearchResults.innerHTML = '<div class="nav-search-loading">Searching...</div>';
            navSearchResults.classList.add('open');

            fetch('../api/search_students.php?q=' + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    renderNavSearchResults(data.students);
                })
                .catch(() => {
                    navSearchResults.innerHTML = '<div class="nav-search-empty">Search failed. Try again.</div>';
                });
        }

        navSearchInput.addEventListener('input', function () {
            clearTimeout(navSearchDebounce);
            navSearchDebounce = setTimeout(runNavSearch, 300);
        });

        navSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(navSearchDebounce);
                runNavSearch();
            }
        });

        navSearchInput.addEventListener('focus', function () {
            if (navSearchInput.value.trim().length > 0) {
                navSearchResults.classList.add('open');
            }
        });

        if (navSearchBtn) {
            navSearchBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                clearTimeout(navSearchDebounce);
                runNavSearch();
                navSearchInput.focus();
            });
        }

        // Selecting a student closes the results dropdown (profile_card.js handles showing the profile popup)
        navSearchResults.addEventListener('click', function (e) {
            if (e.target.closest('.nav-search-item')) {
                navSearchResults.classList.remove('open');
            }
        });
    }

    document.addEventListener('click', function (e) {
        const clickedInsideNotif = notifPanel && (notifPanel.contains(e.target) || e.target === notifToggle);
        const clickedInsideCreate = createPanel && (createPanel.contains(e.target) || e.target === createToggle);
        const clickedInsideProfile = profileNavDropdown && (profileNavDropdown.contains(e.target) || e.target === profileNavToggle || (profileNavToggle && profileNavToggle.contains(e.target)));
        const clickedInsideSearch = navSearchResults && (navSearchResults.contains(e.target) || e.target === navSearchInput || e.target === navSearchBtn || (navSearchBtn && navSearchBtn.contains(e.target)));

        if (!clickedInsideNotif && !clickedInsideCreate && !clickedInsideProfile && !clickedInsideSearch) {
            closeAllDropdowns();
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

        // Open lightbox lang kapag TALAGANG single click (naghihintay muna ng 250ms
        // kung sakaling maging double-click pala ito, para sa Like)
        document.querySelectorAll('.post-images-grid img').forEach(function (img) {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function () {
                const postCard = this.closest('.post-card');
                const grid = this.closest('.post-images-grid');
                const clickedImg = this;

                if (postCard._pendingImgClick) return; // parte na ng double-click, i-skip

                postCard._pendingImgClick = setTimeout(function () {
                    postCard._pendingImgClick = null;
                    const images = Array.from(grid.querySelectorAll('img')).map(i => i.getAttribute('src'));
                    const startIndex = Array.from(grid.querySelectorAll('img')).indexOf(clickedImg);
                    openLightbox(images, startIndex);
                }, 250);
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

    // ===== Focused modal view for a post (used by notification click-through) =====
    function openPostModal(postEl, focusCommentId) {
        let overlay = document.getElementById('postModalOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'postModalOverlay';
            overlay.className = 'post-modal-overlay';
            overlay.innerHTML = '<button type="button" class="post-modal-close" id="postModalClose">&times;</button><div class="post-modal-card" id="postModalCard"></div>';
            document.body.appendChild(overlay);
        }

        const card = document.getElementById('postModalCard');
        const originalParent = postEl.parentNode;
        const originalNext = postEl.nextSibling;

        card.appendChild(postEl);
        overlay.classList.add('open');
        document.body.classList.add('post-modal-lock');

        function closeModal() {
            overlay.classList.remove('open');
            document.body.classList.remove('post-modal-lock');
            if (originalNext) {
                originalParent.insertBefore(postEl, originalNext);
            } else {
                originalParent.appendChild(postEl);
            }
            // Alisin ang ?highlight=... sa URL para hindi na muling bumukas ang modal pag na-reload
            history.replaceState({}, '', window.location.pathname);
        }

        document.getElementById('postModalClose').onclick = closeModal;
        overlay.onclick = function (e) {
            if (e.target === overlay) closeModal();
        };

        const postId = postEl.dataset.postId;
        const commentSection = document.getElementById('comments-' + postId);
        if (commentSection) {
            commentSection.classList.add('open');
            loadComments(postId, function () {
                if (focusCommentId) {
                    const targetComment = document.getElementById('comment-' + focusCommentId);
                    if (targetComment) {
                        targetComment.scrollIntoView({ block: 'center' });
                        targetComment.classList.add('highlight-flash');
                    }
                }
            });
        }
    }

    // ===== Handle notification click-through: open the post in a focused, blurred-backdrop modal =====
    if (typeof HIGHLIGHT_TARGET !== 'undefined' && HIGHLIGHT_TARGET) {
        const targetPost = document.getElementById('post-' + HIGHLIGHT_TARGET);
        if (targetPost) {
            setTimeout(() => {
                const hasCommentTarget = typeof HIGHLIGHT_COMMENT_ID !== 'undefined' && HIGHLIGHT_COMMENT_ID;
                openPostModal(targetPost, hasCommentTarget ? HIGHLIGHT_COMMENT_ID : null);
            }, 200);
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

// ===== "Scroll for more" na batay sa totoong scroll position =====
(function () {
    const scrollHint = document.getElementById('scrollHint');
    const scrollEnd = document.getElementById('scrollEnd');
    if (!scrollHint || !scrollEnd) return; // wala sa page na ito

    function checkScrollState() {
        const scrollable = document.documentElement.scrollHeight > document.documentElement.clientHeight + 40;

        if (!scrollable) {
            // Kasya na lahat ng laman sa screen — walang lalabas na text
            scrollHint.style.display = 'none';
            scrollEnd.style.display = 'none';
            return;
        }

        const distanceFromBottom = document.documentElement.scrollHeight - (window.scrollY + window.innerHeight);
        const reachedEnd = distanceFromBottom < 60;

        scrollHint.style.display = reachedEnd ? 'none' : 'block';
        scrollEnd.style.display = reachedEnd ? 'block' : 'none';
    }

    checkScrollState();
    window.addEventListener('scroll', checkScrollState, { passive: true });
    window.addEventListener('resize', checkScrollState);
})();