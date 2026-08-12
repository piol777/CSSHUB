document.addEventListener('DOMContentLoaded', function () {
    const convList = document.getElementById('convList');
    const chatWindow = document.getElementById('chatWindow');
    const convSearchInput = document.getElementById('convSearchInput');
    let activeConversationId = null;
    let pollInterval = null;
    let lastMessagesSignature = '';
    let conversationsCache = [];
    let selectedFile = null;

    const REACTION_EMOJI = { like: '👍', love: '❤️', haha: '😂', wow: '😮', sad: '😢', angry: '😠' };

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function avatarStyle(path) {
        return path ? ` style="background-image:url('../${path}')"` : '';
    }

    function isOnline(lastActive) {
        if (!lastActive) return false;
        return (Date.now() - new Date(lastActive.replace(' ', 'T')).getTime()) <= 120000;
    }

    function timeAgo(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr.replace(' ', 'T'))) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }

    function formatTime(dateStr) {
        const d = new Date(dateStr.replace(' ', 'T'));
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // ===== Reaction picker (isang instance lang, gaya ng sa widget) =====
    let reactionPicker = document.getElementById('msgReactionPicker');
    if (!reactionPicker) {
        reactionPicker = document.createElement('div');
        reactionPicker.id = 'msgReactionPicker';
        reactionPicker.className = 'msg-widget-reaction-picker';
        reactionPicker.innerHTML = Object.keys(REACTION_EMOJI).map(k =>
            `<button type="button" data-reaction="${k}">${REACTION_EMOJI[k]}</button>`
        ).join('');
        document.body.appendChild(reactionPicker);

        reactionPicker.querySelectorAll('button[data-reaction]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const messageId = reactionPicker.dataset.messageId;
                if (!messageId) return;
                fetch('../api/react_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'message_id=' + encodeURIComponent(messageId) + '&reaction=' + encodeURIComponent(this.dataset.reaction)
                })
                    .then(res => res.json())
                    .then(data => {
                        hideReactionPicker();
                        if (data.success) {
                            lastMessagesSignature = '';
                            loadMessages(false);
                        }
                    });
            });
        });

        document.addEventListener('click', function (e) {
            if (!reactionPicker.contains(e.target)) hideReactionPicker();
        });
    }

    function showReactionPicker(bubbleEl, messageId) {
        const rect = bubbleEl.getBoundingClientRect();
        reactionPicker.style.position = 'fixed';
        reactionPicker.style.left = Math.max(4, rect.left) + 'px';
        reactionPicker.style.top = (rect.top - 46) + 'px';
        reactionPicker.dataset.messageId = messageId;
        reactionPicker.classList.add('open');
    }

    function hideReactionPicker() {
        reactionPicker.classList.remove('open');
        delete reactionPicker.dataset.messageId;
    }

    function renderReactionBadge(reactions) {
        if (!reactions || !reactions.counts) return '';
        const counts = reactions.counts;
        const keys = Object.keys(counts);
        if (keys.length === 0) return '';
        const total = keys.reduce((sum, k) => sum + counts[k], 0);
        const sorted = keys.sort((a, b) => counts[b] - counts[a]).slice(0, 3);
        const emojis = sorted.map(k => REACTION_EMOJI[k] || '').join('');
        return `<div class="msg-widget-reaction-badge">${emojis}${total > 1 ? '<span>' + total + '</span>' : ''}</div>`;
    }

    // ===== Conversation list (search-filterable) =====
    function renderConvList(list) {
        if (list.length === 0) {
            convList.innerHTML = '<div class="conv-empty">No conversations found.</div>';
            return;
        }

        convList.innerHTML = list.map(c => `
            <div class="conv-item ${activeConversationId == c.conversation_id ? 'active-conv' : ''}" data-conv-id="${c.conversation_id}" data-other-id="${c.other_user_id}" data-name="${escapeHtml((CURRENT_ROLE === 'student' ? 'Prof. ' : '') + c.first_name + ' ' + c.last_name)}" data-avatar="${c.profile_picture ? escapeHtml(c.profile_picture) : ''}" data-last-active="${c.last_active || ''}">
                <div class="avatar-wrap">
                    <div class="avatar-circle"${avatarStyle(c.profile_picture)}></div>
                    <div class="directory-status-dot ${isOnline(c.last_active) ? 'online' : ''}"></div>
                </div>
                <div class="conv-item-info">
                    <div class="conv-item-name">${CURRENT_ROLE === 'student' ? 'Prof. ' : ''}${escapeHtml(c.first_name)} ${escapeHtml(c.last_name)}</div>
                    <div class="conv-item-preview">${c.last_message ? escapeHtml(c.last_message) : 'No messages yet'}</div>
                </div>
                <div class="conv-item-right">
                    <div class="conv-item-time">${c.last_time ? timeAgo(c.last_time) : ''}</div>
                    ${c.unread_count > 0 ? '<div class="msg-widget-unread-dot"></div>' : ''}
                </div>
            </div>
        `).join('');

        attachConvClickHandlers();
    }

    function loadConversations(selectAfterLoad) {
        fetch('../api/conversations.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                conversationsCache = data.conversations;
                renderConvList(conversationsCache);

                if (selectAfterLoad) {
                    const target = document.querySelector(`.conv-item[data-conv-id="${selectAfterLoad}"]`);
                    if (target) target.click();
                }
            });
    }

    if (convSearchInput) {
        convSearchInput.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            if (!q) {
                renderConvList(conversationsCache);
                return;
            }
            renderConvList(conversationsCache.filter(c =>
                (c.first_name + ' ' + c.last_name).toLowerCase().includes(q)
            ));
        });
    }

    function attachConvClickHandlers() {
        document.querySelectorAll('.conv-item').forEach(item => {
            item.addEventListener('click', function () {
                document.querySelectorAll('.conv-item').forEach(i => i.classList.remove('active-conv'));
                this.classList.add('active-conv');
                openChat(this.dataset.convId, this.dataset.name, this.dataset.avatar, this.dataset.lastActive);
            });
        });
    }

    // ===== Chat window =====
    function openChat(conversationId, name, avatar, lastActive) {
        activeConversationId = conversationId;
        lastMessagesSignature = '';
        selectedFile = null;

        if (!avatar) {
            const cached = conversationsCache.find(c => String(c.conversation_id) === String(conversationId));
            avatar = cached ? cached.profile_picture : null;
            lastActive = cached ? cached.last_active : null;
        }

        chatWindow.innerHTML = `
            <div class="chat-header">
                <button class="chat-back-btn" id="chatBackBtn">&larr;</button>
                <div class="avatar-wrap">
                    <div class="avatar-circle"${avatarStyle(avatar)}></div>
                    <div class="directory-status-dot ${isOnline(lastActive) ? 'online' : ''}"></div>
                </div>
                <div class="chat-header-name">${name}</div>
            </div>
            <div class="chat-messages" id="chatMessages"></div>
            <div class="msg-widget-file-preview" id="chatFilePreview" style="display:none;"></div>
            <div class="chat-input-area">
                <form class="chat-input-row" id="chatForm">
                    <button type="button" class="chat-attach-btn" id="chatAttachBtn" title="Attach file">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"></path></svg>
                    </button>
                    <input type="file" id="chatFileInput" style="display:none;">
                    <input type="text" id="chatInput" placeholder="Type a message..." maxlength="2000" autocomplete="off">
                    <button type="submit" class="chat-send-btn">
                        <svg viewBox="0 0 24 24"><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </form>
            </div>
        `;

        document.getElementById('convListPanel').classList.remove('show-mobile');
        chatWindow.classList.add('show-mobile');

        const backBtn = document.getElementById('chatBackBtn');
        if (backBtn) {
            backBtn.addEventListener('click', function () {
                document.getElementById('convListPanel').classList.add('show-mobile');
                chatWindow.classList.remove('show-mobile');
            });
        }

        const filePreview = document.getElementById('chatFilePreview');
        const fileInput = document.getElementById('chatFileInput');

        document.getElementById('chatAttachBtn').addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                if (this.files[0].size > 10 * 1024 * 1024) {
                    alert('File must be under 10MB.');
                    fileInput.value = '';
                    return;
                }
                selectedFile = this.files[0];
                const isImage = selectedFile.type.startsWith('image/');
                filePreview.style.display = 'flex';
                filePreview.innerHTML = `
                    <div class="msg-widget-file-preview-chip">
                        ${isImage ? `<img src="${URL.createObjectURL(selectedFile)}" alt="preview">` : `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>`}
                        <span>${escapeHtml(selectedFile.name)}</span>
                        <button type="button" id="chatFileRemove">&times;</button>
                    </div>
                `;
                document.getElementById('chatFileRemove').addEventListener('click', function () {
                    selectedFile = null;
                    fileInput.value = '';
                    filePreview.style.display = 'none';
                    filePreview.innerHTML = '';
                });
            }
        });

        loadMessages(true);

        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => loadMessages(false), 3000);

        const chatForm = document.getElementById('chatForm');
        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const input = document.getElementById('chatInput');
            const content = input.value.trim();
            if (!content && !selectedFile) return;

            const formData = new FormData();
            formData.append('conversation_id', activeConversationId);
            formData.append('content', content);
            if (selectedFile) formData.append('attachment', selectedFile);

            fetch('../api/messages.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Failed to send.');
                        return;
                    }
                    input.value = '';
                    selectedFile = null;
                    fileInput.value = '';
                    filePreview.style.display = 'none';
                    filePreview.innerHTML = '';
                    loadMessages(true);
                    loadConversations(null);
                });
        });
    }

    function renderMessage(m, currentUserId) {
        const isSent = String(m.sender_id) === String(currentUserId);
        const avatarHtml = !isSent
            ? `<div class="avatar-circle msg-bubble-avatar"${avatarStyle(m.sender_profile_picture)}></div>`
            : '';

        let bubbleHtml = '';
        if (m.content) {
            bubbleHtml += `<div class="msg-widget-bubble">${escapeHtml(m.content)}</div>`;
        }
        if (m.attachment_path) {
            if (m.attachment_type === 'image') {
                bubbleHtml += `<div class="msg-widget-bubble msg-widget-bubble-image"><img src="../${m.attachment_path}" alt="${escapeHtml(m.attachment_name || 'image')}"></div>`;
            } else {
                bubbleHtml += `<a class="msg-widget-bubble msg-widget-bubble-file" href="../${m.attachment_path}" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <span>${escapeHtml(m.attachment_name || 'File')}</span>
                </a>`;
            }
        }

        return `
            <div class="msg-widget-bubble-row ${isSent ? 'sent' : 'received'}" data-msg-id="${m.id}">
                ${avatarHtml}
                <div class="msg-widget-bubble-col">
                    ${bubbleHtml}
                    ${renderReactionBadge(m.reactions)}
                    <div class="msg-time">${formatTime(m.created_at)}</div>
                </div>
            </div>
        `;
    }

    function loadMessages(scrollToBottom) {
        if (!activeConversationId) return;

        fetch('../api/messages.php?conversation_id=' + activeConversationId)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                const signature = data.messages.map(m => m.id + ':' + m.content + ':' + m.attachment_path + ':' + JSON.stringify(m.reactions)).join('|');
                if (signature === lastMessagesSignature) return;
                lastMessagesSignature = signature;

                const container = document.getElementById('chatMessages');
                if (!container) return;

                container.innerHTML = data.messages.map(m => renderMessage(m, data.current_user_id)).join('');

                container.querySelectorAll('.msg-widget-bubble').forEach(bubble => {
                    bubble.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const row = this.closest('.msg-widget-bubble-row');
                        showReactionPicker(this, row.dataset.msgId);
                    });
                });

                if (scrollToBottom) {
                    container.scrollTop = container.scrollHeight;
                }
            });
    }

    loadConversations(null);

    // Auto-open a conversation if we arrived here from a professor's post (student only)
    if (typeof AUTO_OPEN_USER_ID !== 'undefined' && AUTO_OPEN_USER_ID) {
        fetch('../api/start_conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'professor_id=' + AUTO_OPEN_USER_ID
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadConversations(data.conversation_id);
                }
            });
    }
});