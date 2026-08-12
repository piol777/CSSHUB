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
        if (currentTriggerUserId === userId) return;
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
                if (!currentCard || currentTriggerUserId !== userId) return;
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

        const editBadge = p.is_own_profile
            ? `<button type="button" class="profile-hover-edit-badge" title="Edit profile picture">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
               </button>`
            : '';

        const warnBadge = p.can_warn
            ? `<button type="button" class="profile-hover-warn-badge" title="Give warning">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 114 4L7.5 20.5 2 22l1.5-5.5z"></path></svg>
               </button>`
            : '';

        const mayorTag = p.is_mayor ? '<span class="profile-hover-mayor-tag">Mayor</span>' : '';

        card.innerHTML = `
            <div class="profile-hover-avatar-wrap">
                <div class="avatar-circle profile-hover-avatar" ${avatarStyle}></div>
                ${editBadge}
                ${warnBadge}
            </div>
            <div class="profile-hover-name">${escapeHtml(displayName)} ${mayorTag}</div>
            <div class="profile-hover-role">${roleLabel}</div>
            <div class="profile-hover-info">${infoRows}</div>
            ${p.can_manage_mayor ? `<button type="button" class="profile-hover-mayor-btn" data-is-mayor="${p.is_mayor ? '1' : '0'}" data-student-id="${p.id}">${p.is_mayor ? 'Revoke Posting Access' : 'Grant Posting Access'}</button>` : ''}
            ${p.can_message ? '<button type="button" class="profile-hover-msg-btn">Message</button>' : ''}
        `;

        const mayorBtn = card.querySelector('.profile-hover-mayor-btn');
        if (mayorBtn) {
            mayorBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const willGrant = mayorBtn.dataset.isMayor !== '1';
                const fd = new FormData();
                fd.append('student_id', mayorBtn.dataset.studentId);
                fd.append('grant', willGrant ? '1' : '0');
                fetch('../api/toggle_mayor.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(function (res) {
                        if (res.success) {
                            mayorBtn.dataset.isMayor = willGrant ? '1' : '0';
                            mayorBtn.textContent = willGrant ? 'Revoke Posting Access' : 'Grant Posting Access';
                        }
                    });
            });
        }

        const editBtn = card.querySelector('.profile-hover-edit-badge');
        if (editBtn) {
            editBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                window.location.href = 'profile.php';
            });
        }

        const warnBtn = card.querySelector('.profile-hover-warn-badge');
        if (warnBtn) {
            warnBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                window.openWarningModal(p.id, displayName);
            });
        }

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
        if (currentCard && !currentCard.contains(e.target)) {
            closeCard();
        }
    });

    // ===== WARNING MODAL (global — pwede tawagin kahit saang page) =====
    let warningModal = document.getElementById('warningModal');
    if (!warningModal) {
        warningModal = document.createElement('div');
        warningModal.id = 'warningModal';
        warningModal.className = 'modal-overlay';
        warningModal.innerHTML = `
            <div class="modal-box">
                <div class="modal-header">
                    <span id="warningModalName">Warning</span>
                    <button type="button" class="modal-close-btn" id="closeWarningModal">&times;</button>
                </div>
                <div class="warning-modal-body">
                    <div class="warning-count-label" id="warningCountLabel">Warnings: 0 / 3</div>
                    <div class="warning-history" id="warningHistory"></div>
                    <textarea id="warningReasonInput" class="warning-reason-input" rows="3" placeholder="Reason type here.."></textarea>
                    <button type="button" class="modal-submit-btn" id="submitWarningBtn">Warning</button>
                    <button type="button" class="warning-reset-btn" id="resetWarningBtn" style="display:none;">Reset Warnings</button>
                </div>
            </div>
        `;
        document.body.appendChild(warningModal);

        document.getElementById('closeWarningModal').addEventListener('click', function () {
            warningModal.classList.remove('open');
        });
        warningModal.addEventListener('click', function (e) {
            if (e.target === warningModal) warningModal.classList.remove('open');
        });

        document.getElementById('submitWarningBtn').addEventListener('click', function () {
            const studentId = warningModal.dataset.studentId;
            const reason = document.getElementById('warningReasonInput').value.trim();
            if (!reason) { alert('Ilagay ang dahilan ng warning.'); return; }

            const fd = new FormData();
            fd.append('student_id', studentId);
            fd.append('reason', reason);

            fetch('../api/give_warning.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(function (res) {
                    if (res.success) {
                        document.getElementById('warningReasonInput').value = '';
                        loadWarningInfo(studentId);
                    } else {
                        alert(res.message || 'Failed to give warning.');
                    }
                });
        });

        document.getElementById('resetWarningBtn').addEventListener('click', function () {
            const studentId = warningModal.dataset.studentId;
            if (!confirm('I-reset ang warnings ng student na ito?')) return;

            const fd = new FormData();
            fd.append('student_id', studentId);

            fetch('../api/reset_warnings.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(function (res) {
                    if (res.success) loadWarningInfo(studentId);
                });
        });
    }

    function loadWarningInfo(studentId) {
        fetch('../api/student_warning_count.php?student_id=' + studentId)
            .then(res => res.json())
            .then(function (data) {
                if (!data.success) return;
                document.getElementById('warningCountLabel').textContent = 'Warnings: ' + data.warning_count + ' / 3';
                document.getElementById('warningHistory').innerHTML = data.warnings.map(w => `
                    <div class="warning-history-item">
                        <strong>${escapeHtml(w.first_name)} ${escapeHtml(w.last_name)}:</strong> ${escapeHtml(w.reason)}
                    </div>
                `).join('') || '<div class="warning-history-empty">Wala pang warning.</div>';
                document.getElementById('resetWarningBtn').style.display = data.warning_count > 0 ? 'block' : 'none';
            });
    }

    window.openWarningModal = function (studentId, name) {
        document.getElementById('warningModalName').textContent = name;
        document.getElementById('warningReasonInput').value = '';
        warningModal.dataset.studentId = studentId;
        loadWarningInfo(studentId);
        warningModal.classList.add('open');
    };
});