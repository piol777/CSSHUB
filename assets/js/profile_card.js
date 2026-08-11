document.addEventListener('DOMContentLoaded', function () {
    const HOVER_DELAY_MS = 3000;
    let hoverTimer = null;
    let currentCard = null;
    let currentTriggerUserId = null;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : str;
        return div.innerHTML;
    }

    function closeCard() {
        if (currentCard) {
            currentCard.remove();
            currentCard = null;
            currentTriggerUserId = null;
        }
    }

    function positionCard(card, anchorEl) {
        const rect = anchorEl.getBoundingClientRect();
        const cardWidth = 260;
        let left = rect.left;
        let top = rect.bottom + 8;

        if (left + cardWidth > window.innerWidth - 12) {
            left = window.innerWidth - cardWidth - 12;
        }
        if (top + 280 > window.innerHeight) {
            top = rect.top - 8;
            card.classList.add('profile-hover-card-above');
        }

        card.style.left = Math.max(12, left) + 'px';
        card.style.top = top + 'px';
    }

    function showCard(userId, anchorEl) {
        if (currentTriggerUserId === userId) return; // already open for this user
        closeCard();
        currentTriggerUserId = userId;

        const card = document.createElement('div');
        card.className = 'profile-hover-card';
        card.innerHTML = '<div class="profile-hover-loading">Loading...</div>';
        document.body.appendChild(card);
        currentCard = card;
        positionCard(card, anchorEl);

        fetch('../api/user_profile_card.php?user_id=' + encodeURIComponent(userId))
            .then(res => res.json())
            .then(data => {
                if (!currentCard || currentTriggerUserId !== userId) return; // closed or switched while loading
                if (!data.success) {
                    card.innerHTML = '<div class="profile-hover-loading">Could not load profile.</div>';
                    return;
                }
                renderCard(card, data.profile);
                positionCard(card, anchorEl);
            })
            .catch(() => {
                if (currentCard) card.innerHTML = '<div class="profile-hover-loading">Could not load profile.</div>';
            });
    }

    function renderCard(card, p) {
        const isProfessor = p.role === 'professor';
        const displayName = (isProfessor ? 'Prof. ' : '') + p.first_name + ' ' + p.last_name;
        const roleLabel = isProfessor ? 'Professor' : 'Student';

        let infoRows = '';
        if (isProfessor) {
            infoRows += `<div class="profile-hover-row"><span>Department</span><span>${escapeHtml(p.department || 'N/A')}</span></div>`;
        } else {
            infoRows += `<div class="profile-hover-row"><span>Course</span><span>${escapeHtml(p.course || 'N/A')}</span></div>`;
            infoRows += `<div class="profile-hover-row"><span>Year & Section</span><span>${escapeHtml(p.year_section || 'N/A')}</span></div>`;
        }
        infoRows += `<div class="profile-hover-row"><span>Email</span><span>${escapeHtml(p.email)}</span></div>`;

        const avatarStyle = p.profile_picture ? `style="background-image:url('../${escapeHtml(p.profile_picture)}')"` : '';

        card.innerHTML = `
            <div class="profile-hover-avatar-wrap">
                <div class="avatar-circle profile-hover-avatar" ${avatarStyle}></div>
            </div>
            <div class="profile-hover-name">${escapeHtml(displayName)}</div>
            <div class="profile-hover-role">${roleLabel}</div>
            <div class="profile-hover-info">${infoRows}</div>
            ${p.can_message ? '<button type="button" class="profile-hover-msg-btn">Message</button>' : ''}
        `;

        const msgBtn = card.querySelector('.profile-hover-msg-btn');
        if (msgBtn) {
            msgBtn.addEventListener('click', function () {
                fetch('../api/start_conversation.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'other_user_id=' + encodeURIComponent(p.id)
                })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) return;
                        closeCard();
                        const msgWidget = document.getElementById('msgWidget');
                        if (msgWidget) msgWidget.classList.add('open');
                        if (typeof window.openWidgetConversation === 'function') {
                            window.openWidgetConversation(data.conversation_id, displayName);
                        }
                    });
            });
        }
    }

    // Delegate hover + click on any element carrying data-profile-user-id
    document.addEventListener('mouseover', function (e) {
        const trigger = e.target.closest('[data-profile-user-id]');
        if (!trigger) return;
        const userId = trigger.dataset.profileUserId;
        if (hoverTimer) clearTimeout(hoverTimer);
        hoverTimer = setTimeout(() => showCard(userId, trigger), HOVER_DELAY_MS);
    });

    document.addEventListener('mouseout', function (e) {
        const trigger = e.target.closest('[data-profile-user-id]');
        if (!trigger) return;
        if (hoverTimer) {
            clearTimeout(hoverTimer);
            hoverTimer = null;
        }
    });

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-profile-user-id]');
        if (trigger) {
            e.preventDefault();
            if (hoverTimer) {
                clearTimeout(hoverTimer);
                hoverTimer = null;
            }
            showCard(trigger.dataset.profileUserId, trigger);
            return;
        }
        // Clicking anywhere outside the card closes it
        if (currentCard && !currentCard.contains(e.target)) {
            closeCard();
        }
    });
});