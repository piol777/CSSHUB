document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('msgWidgetToggle');
    const widget = document.getElementById('msgWidget');
    const closeBtn = document.getElementById('msgWidgetClose');
    const backBtn = document.getElementById('msgWidgetBack');
    const titleText = document.getElementById('msgWidgetTitleText');
    const headerAvatar = document.getElementById('msgWidgetHeaderAvatar');
    const listView = document.getElementById('msgWidgetList');
    const chatView = document.getElementById('msgWidgetChat');
    const messagesArea = document.getElementById('msgWidgetMessages');
    const form = document.getElementById('msgWidgetForm');
    const input = document.getElementById('msgWidgetInput');
    const fileInput = document.getElementById('msgWidgetFileInput');
    const attachBtn = document.getElementById('msgWidgetAttachBtn');
    const filePreviewRow = document.getElementById('msgWidgetFilePreview');
    const reactionPicker = document.getElementById('msgWidgetReactionPicker');

    if (!toggle || !widget) return;

    let activeConversationId = null;
    let lastMsgSignature = null;
    let pollInterval = null;
    let selectedFile = null;
    let conversationsCache = [];
    const msgBadge = document.getElementById('msgBadge');

    const REACTION_EMOJI = { like: '👍', love: '❤️', haha: '😂', wow: '😮', sad: '😢', angry: '😠' };

    function refreshMsgBadge() {
        if (!msgBadge) return;
        fetch('../api/unread_messages_count.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                if (data.unread_count > 0) {
                    msgBadge.textContent = data.unread_count;
                    msgBadge.classList.remove('hidden');
                } else {
                    msgBadge.classList.add('hidden');
                }
            });
    }

    refreshMsgBadge();
    setInterval(refreshMsgBadge, 10000);

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function avatarStyle(path) {
        return path ? ` style="background-image:url('../${path}')"` : '';
    }

    function showListView() {
        chatView.style.display = 'none';
        listView.style.display = 'block';
        backBtn.style.display = 'none';
        titleText.textContent = 'Messages';
        headerAvatar.removeAttribute('style');
        headerAvatar.style.display = 'none';
        activeConversationId = null;
        clearSelectedFile();
        hideReactionPicker();
        if (pollInterval) clearInterval(pollInterval);
        loadConversationList();
    }

    function loadConversationList() {
        fetch('../api/conversations.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                conversationsCache = data.conversations;

                if (data.conversations.length === 0) {
                    listView.innerHTML = '<div class="msg-widget-empty">No conversations yet.</div>';
                    return;
                }

                listView.innerHTML = data.conversations.map(c => `
                    <div class="msg-widget-conv-item" data-conv-id="${c.conversation_id}" data-name="${escapeHtml((typeof CURRENT_ROLE !== 'undefined' && CURRENT_ROLE === 'student' ? 'Prof. ' : '') + c.first_name + ' ' + c.last_name)}" data-avatar="${c.profile_picture ? escapeHtml(c.profile_picture) : ''}">
                        <div class="avatar-circle"${avatarStyle(c.profile_picture)}></div>
                        <div class="msg-widget-conv-info">
                            <div class="msg-widget-conv-name">${escapeHtml(c.first_name)} ${escapeHtml(c.last_name)}</div>
                            <div class="msg-widget-conv-preview">${c.last_message ? escapeHtml(c.last_message) : 'No messages yet'}</div>
                        </div>
                        ${c.unread_count > 0 ? '<div class="msg-widget-unread-dot"></div>' : ''}
                    </div>
                `).join('');

                document.querySelectorAll('.msg-widget-conv-item').forEach(item => {
                    item.addEventListener('click', function () {
                        openChatView(this.dataset.convId, this.dataset.name, this.dataset.avatar);
                    });
                });
            });
    }

    window.openWidgetConversation = function (conversationId, name, avatar) {
        openChatView(conversationId, name, avatar);
    };

    function openChatView(conversationId, name, avatar) {
        activeConversationId = conversationId;
        lastMsgSignature = null;
        listView.style.display = 'none';
        chatView.style.display = 'flex';
        backBtn.style.display = 'flex';
        titleText.textContent = name;
        clearSelectedFile();
        hideReactionPicker();

        if (!avatar) {
            const cached = conversationsCache.find(c => String(c.conversation_id) === String(conversationId));
            avatar = cached ? cached.profile_picture : null;
        }
        if (avatar) {
            headerAvatar.style.backgroundImage = `url('../${avatar}')`;
            headerAvatar.style.display = 'block';
        } else {
            headerAvatar.removeAttribute('style');
            headerAvatar.style.display = 'block';
        }

        loadMessages(true);

        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => loadMessages(false), 3000);
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

    function renderMessage(m, currentUserId) {
        const isSent = String(m.sender_id) === String(currentUserId);
        const avatarHtml = !isSent
            ? `<div class="avatar-circle msg-widget-msg-avatar"${avatarStyle(m.sender_profile_picture)}></div>`
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
                if (signature === lastMsgSignature) return;
                lastMsgSignature = signature;

                messagesArea.innerHTML = data.messages.map(m => renderMessage(m, data.current_user_id)).join('');

                messagesArea.querySelectorAll('.msg-widget-bubble').forEach(bubble => {
                    bubble.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const row = this.closest('.msg-widget-bubble-row');
                        showReactionPicker(this, row.dataset.msgId);
                    });
                });

                if (scrollToBottom) {
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                    messagesArea.querySelectorAll('.msg-widget-bubble-image img').forEach(img => {
                        if (!img.complete) {
                            img.addEventListener('load', () => {
                                messagesArea.scrollTop = messagesArea.scrollHeight;
                            });
                        }
                    });
                }
            });
    }

function showReactionPicker(bubbleEl, messageId) {
        const bubbleRect = bubbleEl.getBoundingClientRect();
        const widgetRect = widget.getBoundingClientRect();
        let left = bubbleRect.left - widgetRect.left;
        const maxLeft = widgetRect.width - 190;
        if (left > maxLeft) left = Math.max(4, maxLeft);
        if (left < 4) left = 4;
        reactionPicker.style.left = left + 'px';
        reactionPicker.style.top = (bubbleRect.top - widgetRect.top - 42) + 'px';
        reactionPicker.dataset.messageId = messageId;
        reactionPicker.classList.add('open');
    }

    function hideReactionPicker() {
        reactionPicker.classList.remove('open');
        delete reactionPicker.dataset.messageId;
    }

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
                        lastMsgSignature = null;
                        loadMessages(false);
                    }
                });
        });
    });

    document.addEventListener('click', function (e) {
        if (!reactionPicker.contains(e.target)) hideReactionPicker();
    });

    function clearSelectedFile() {
        selectedFile = null;
        fileInput.value = '';
        filePreviewRow.style.display = 'none';
        filePreviewRow.innerHTML = '';
    }

    function renderFilePreview() {
        if (!selectedFile) return;
        const isImage = selectedFile.type.startsWith('image/');
        filePreviewRow.style.display = 'flex';
        filePreviewRow.innerHTML = `
            <div class="msg-widget-file-preview-chip">
                ${isImage
                    ? `<img src="${URL.createObjectURL(selectedFile)}" alt="preview">`
                    : `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>`
                }
                <span>${escapeHtml(selectedFile.name)}</span>
                <button type="button" id="msgWidgetFileRemove" title="Remove">&times;</button>
            </div>
        `;
        document.getElementById('msgWidgetFileRemove').addEventListener('click', clearSelectedFile);
    }

    attachBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const maxSize = 10 * 1024 * 1024;
            if (this.files[0].size > maxSize) {
                alert('File must be under 10MB.');
                clearSelectedFile();
                return;
            }
            selectedFile = this.files[0];
            renderFilePreview();
        }
    });

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = widget.classList.contains('open');

        if (!isOpen) {
            widget.classList.add('open');
            showListView();
            setTimeout(refreshMsgBadge, 1000);
        } else {
            widget.classList.remove('open');
        }
    });

    closeBtn.addEventListener('click', function () {
        widget.classList.remove('open');
        hideReactionPicker();
        if (pollInterval) clearInterval(pollInterval);
    });

    backBtn.addEventListener('click', showListView);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const content = input.value.trim();
        if ((!content && !selectedFile) || !activeConversationId) return;

        const formData = new FormData();
        formData.append('conversation_id', activeConversationId);
        formData.append('content', content);
        if (selectedFile) formData.append('attachment', selectedFile);

        fetch('../api/messages.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    if (data.message) alert(data.message);
                    return;
                }
                input.value = '';
                clearSelectedFile();
                lastMsgSignature = null;
                loadMessages(true);
            });
    });
});