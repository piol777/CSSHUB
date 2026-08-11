document.addEventListener('DOMContentLoaded', function () {
    const convList = document.getElementById('convList');
    const chatWindow = document.getElementById('chatWindow');
    let activeConversationId = null;
    let pollInterval = null;
    let lastMessagesSignature = '';
    let conversationsCache = [];

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function avatarStyle(path) {
        return path ? ` style="background-image:url('../${path}')"` : '';
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

    function loadConversations(selectAfterLoad) {
        fetch('../api/conversations.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                conversationsCache = data.conversations;

                if (data.conversations.length === 0) {
                    convList.innerHTML = '<div class="conv-empty">No conversations yet.</div>';
                    return;
                }

                convList.innerHTML = data.conversations.map(c => `
                    <div class="conv-item ${activeConversationId == c.conversation_id ? 'active-conv' : ''}" data-conv-id="${c.conversation_id}" data-other-id="${c.other_user_id}" data-name="${escapeHtml((CURRENT_ROLE === 'student' ? 'Prof. ' : '') + c.first_name + ' ' + c.last_name)}" data-avatar="${c.profile_picture ? escapeHtml(c.profile_picture) : ''}">
                        <div class="avatar-circle"${avatarStyle(c.profile_picture)}></div>
                        <div class="conv-item-info">
                            <div class="conv-item-name">${CURRENT_ROLE === 'student' ? 'Prof. ' : ''}${escapeHtml(c.first_name)} ${escapeHtml(c.last_name)}</div>
                            <div class="conv-item-preview">${c.last_message ? escapeHtml(c.last_message) : 'No messages yet'}</div>
                        </div>
                        <div class="conv-item-time">${c.last_time ? timeAgo(c.last_time) : ''}</div>
                    </div>
                `).join('');

                attachConvClickHandlers();

                if (selectAfterLoad) {
                    const target = document.querySelector(`.conv-item[data-conv-id="${selectAfterLoad}"]`);
                    if (target) target.click();
                }
            });
    }

    function attachConvClickHandlers() {
        document.querySelectorAll('.conv-item').forEach(item => {
            item.addEventListener('click', function () {
                document.querySelectorAll('.conv-item').forEach(i => i.classList.remove('active-conv'));
                this.classList.add('active-conv');
                openChat(this.dataset.convId, this.dataset.name, this.dataset.avatar);
            });
        });
    }

    function openChat(conversationId, name, avatar) {
        activeConversationId = conversationId;
        lastMessagesSignature = '';

        if (!avatar) {
            const cached = conversationsCache.find(c => String(c.conversation_id) === String(conversationId));
            avatar = cached ? cached.profile_picture : null;
        }

        chatWindow.innerHTML = `
            <div class="chat-header">
                <button class="chat-back-btn" id="chatBackBtn">&larr;</button>
                <div class="avatar-circle"${avatarStyle(avatar)}></div>
                <div class="chat-header-name">${name}</div>
            </div>
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input-area">
                <form class="chat-input-row" id="chatForm">
                    <input type="text" id="chatInput" placeholder="Type a message..." maxlength="2000" required autocomplete="off">
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

        loadMessages(true);

        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => loadMessages(false), 3000);

        const chatForm = document.getElementById('chatForm');
        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const input = document.getElementById('chatInput');
            const content = input.value.trim();
            if (!content) return;

            fetch('../api/messages.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'conversation_id=' + activeConversationId + '&content=' + encodeURIComponent(content)
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Failed to send.');
                        return;
                    }
                    input.value = '';
                    loadMessages(true);
                    loadConversations(null);
                });
        });
    }

    function loadMessages(scrollToBottom) {
        if (!activeConversationId) return;

        fetch('../api/messages.php?conversation_id=' + activeConversationId)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                const signature = data.messages.map(m => m.id + ':' + m.content).join('|');
                if (signature === lastMessagesSignature) return;
                lastMessagesSignature = signature;

                const container = document.getElementById('chatMessages');
                if (!container) return;

                container.innerHTML = data.messages.map(m => {
                    const isSent = m.sender_id == data.current_user_id;
                    const avatarHtml = !isSent
                        ? `<div class="avatar-circle msg-bubble-avatar"${avatarStyle(m.sender_profile_picture)}></div>`
                        : '';
                    return `
                        <div class="msg-bubble-row ${isSent ? 'sent' : 'received'}">
                            ${avatarHtml}
                            <div>
                                <div class="msg-bubble">${escapeHtml(m.content)}</div>
                                <div class="msg-time">${formatTime(m.created_at)}</div>
                            </div>
                        </div>
                    `;
                }).join('');

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