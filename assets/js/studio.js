document.addEventListener('DOMContentLoaded', function () {
    // ===== MODE + GAME SELECTION =====
    const modeSelect = document.getElementById('studioModeSelect');
    const modeOtherInput = document.getElementById('studioModeOtherInput');
    const gameSelect = document.getElementById('studioGameSelect');

    modeSelect.addEventListener('change', function () {
        modeOtherInput.style.display = this.value === 'other' ? 'block' : 'none';
        gameSelect.style.display = this.value === 'gaming' ? 'block' : 'none';
        if (this.value !== 'other') modeOtherInput.value = '';
        if (this.value !== 'gaming') gameSelect.value = '';
    });

    function getSelectedLiveType() {
        if (modeSelect.value === 'gaming') return gameSelect.value;
        if (modeSelect.value === 'other') return modeOtherInput.value.trim();
        if (modeSelect.value === 'live_class') return 'Live Class';
        return '';
    }

    // ===== CANVAS COMPOSITOR =====
    const canvas = document.getElementById('studioCanvas');
    canvas.width = 1280;
    canvas.height = 720;
    const ctx = canvas.getContext('2d');
    const stageEmpty = document.getElementById('studioStageEmpty');

    let sources = [];
    let nextSourceId = 1;

    function updateStageEmptyState() {
        stageEmpty.style.display = sources.some(s => s.visible) ? 'none' : 'flex';
    }

    const HANDLE_SIZE = 16;
    let selectedSource = null;

    function drawFrame() {
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        sources.forEach(function (s) {
            if (!s.visible) return;
            try { ctx.drawImage(s.video, s.x, s.y, s.w, s.h); } catch (e) {}

            // I-guhit ang selection border + resize handle para lang makita ng
            // professor sa Studio — HINDI ito kasama sa stream na napupunta sa
            // mga estudyante (dahil hiwalay na canvas ang kinukuha natin — tingnan
            // ang tala sa ibaba).
            if (s === selectedSource && !s.locked) {
                ctx.strokeStyle = '#7c5cff';
                ctx.lineWidth = 2;
                ctx.strokeRect(s.x, s.y, s.w, s.h);
                ctx.fillStyle = '#7c5cff';
                ctx.fillRect(s.x + s.w - HANDLE_SIZE, s.y + s.h - HANDLE_SIZE, HANDLE_SIZE, HANDLE_SIZE);
            }
        });
        requestAnimationFrame(drawFrame);
    }
    requestAnimationFrame(drawFrame);

    function addSource(type, stream, label) {
        if (sources.length >= 3) {
            alert('Maximum of 3 sources lang ang pwede.');
            stream.getTracks().forEach(t => t.stop());
            return;
        }
        const video = document.createElement('video');
        video.srcObject = stream;
        video.muted = true;
        video.playsInline = true;
        video.play();

        sources.push({
            id: nextSourceId++,
            type, label, stream, video,
            x: 40 + sources.length * 40,
            y: 40 + sources.length * 40,
            w: 480, h: 270,
            visible: true, locked: false
        });

        stream.getVideoTracks()[0].addEventListener('ended', function () {
            removeSourceById(sources[sources.length - 1].id);
        });

        renderSourcesList();
    }

    function removeSourceById(id) {
        const idx = sources.findIndex(s => s.id === id);
        if (idx > -1) {
            sources[idx].stream.getTracks().forEach(t => t.stop());
            sources.splice(idx, 1);
        }
        renderSourcesList();
    }

    function renderSourcesList() {
        const list = document.getElementById('studioSourcesList');
        if (sources.length === 0) {
            list.innerHTML = '<div class="studio-empty-small">No sources added yet.</div>';
            updateStageEmptyState();
            return;
        }
        list.innerHTML = sources.map(function (s) {
            return `
                <div class="studio-source-item">
                    <span>${s.label}</span>
                    <div class="studio-source-item-actions">
                        <button type="button" class="source-lock-btn ${s.locked ? 'active' : ''}" data-id="${s.id}" title="Lock position">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0110 0v4"></path></svg>
                        </button>
                        <button type="button" class="source-visibility-btn ${s.visible ? 'active' : ''}" data-id="${s.id}" title="Show/Hide">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                        <button type="button" class="source-remove-btn" data-id="${s.id}" title="Remove">&times;</button>
                    </div>
                </div>
            `;
        }).join('');

        list.querySelectorAll('.source-lock-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const s = sources.find(x => x.id == btn.dataset.id);
                s.locked = !s.locked;
                renderSourcesList();
            });
        });
        list.querySelectorAll('.source-visibility-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const s = sources.find(x => x.id == btn.dataset.id);
                s.visible = !s.visible;
                renderSourcesList();
            });
        });
        list.querySelectorAll('.source-remove-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                removeSourceById(parseInt(btn.dataset.id, 10));
            });
        });

        updateStageEmptyState();
    }

    // ===== "+" SOURCE MENU =====
    const addSourceBtn = document.getElementById('studioAddSourceBtn');
    const sourceMenu = document.getElementById('studioSourceMenu');

    addSourceBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        sourceMenu.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!sourceMenu.contains(e.target) && e.target !== addSourceBtn) {
            sourceMenu.classList.remove('open');
        }
    });

    sourceMenu.querySelectorAll('button[data-source-type]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            sourceMenu.classList.remove('open');
            const type = this.dataset.sourceType;
            try {
                let stream, label;
                if (type === 'camera') {
                    stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    label = '📷 Camera';
                } else if (type === 'window') {
                    stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    label = '🖥️ Window Captured';
                } else {
                    stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    label = '🖵 Screen Captured';
                }
                addSource(type, stream, label);
            } catch (err) {
                console.error('Source error:', err);
            }
        });
    });

    // ===== DRAG-TO-POSITION + RESIZE SA CANVAS =====
    let dragging = null, resizing = null, dragOffsetX = 0, dragOffsetY = 0;

    function toCanvasCoords(e) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (e.clientX - rect.left) * (canvas.width / rect.width),
            y: (e.clientY - rect.top) * (canvas.height / rect.height)
        };
    }

    canvas.addEventListener('mousedown', function (e) {
        const { x: mx, y: my } = toCanvasCoords(e);

        // Kung may selected source, tingnan muna kung tinatamaan ang resize handle niya
        if (selectedSource && !selectedSource.locked && selectedSource.visible) {
            const s = selectedSource;
            const hx = s.x + s.w - HANDLE_SIZE, hy = s.y + s.h - HANDLE_SIZE;
            if (mx >= hx && mx <= hx + HANDLE_SIZE && my >= hy && my <= hy + HANDLE_SIZE) {
                resizing = s;
                return;
            }
        }

        for (let i = sources.length - 1; i >= 0; i--) {
            const s = sources[i];
            if (s.locked || !s.visible) continue;
            if (mx >= s.x && mx <= s.x + s.w && my >= s.y && my <= s.y + s.h) {
                selectedSource = s;
                dragging = s;
                dragOffsetX = mx - s.x;
                dragOffsetY = my - s.y;
                return;
            }
        }
        selectedSource = null;
    });

    canvas.addEventListener('mousemove', function (e) {
        const { x: mx, y: my } = toCanvasCoords(e);

        if (resizing) {
            resizing.w = Math.max(100, Math.min(canvas.width - resizing.x, mx - resizing.x));
            resizing.h = Math.max(60, Math.min(canvas.height - resizing.y, my - resizing.y));
            return;
        }
        if (dragging) {
            dragging.x = Math.max(0, Math.min(canvas.width - dragging.w, mx - dragOffsetX));
            dragging.y = Math.max(0, Math.min(canvas.height - dragging.h, my - dragOffsetY));
        }
    });

    window.addEventListener('mouseup', function () {
        dragging = null;
        resizing = null;
    });

    // ===== START / STOP LIVE =====
    const startBtn = document.getElementById('studioStartBtn');
    const stopBtn = document.getElementById('studioStopBtn');
    const sectionSelect = document.getElementById('studioSectionSelect');

    let socket = null;
    let peerConnections = {};
    let currentRoomId = null;
    let outgoingStream = null;

    function connectToStudent(studentSocketId) {
        const pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
        peerConnections[studentSocketId] = pc;
        outgoingStream.getVideoTracks().forEach(t => pc.addTrack(t, outgoingStream));

        pc.onicecandidate = function (e) {
            if (e.candidate) {
                socket.emit('webrtc-signal', {
                    targetSocketId: studentSocketId,
                    signal: { type: 'ice-candidate', candidate: e.candidate }
                });
            }
        };

        pc.createOffer()
            .then(offer => pc.setLocalDescription(offer))
            .then(function () {
                socket.emit('webrtc-signal', {
                    targetSocketId: studentSocketId,
                    signal: { type: 'offer', sdp: pc.localDescription, role: 'professor' }
                });
            });
    }

    startBtn.addEventListener('click', function () {
        if (!sectionSelect.value) { alert('Please select a section.'); return; }
        if (!modeSelect.value) { alert('Please select a live mode.'); return; }
        const liveType = getSelectedLiveType();
        if (!liveType) { alert('Please complete your mode selection.'); return; }

        const opt = sectionSelect.options[sectionSelect.selectedIndex];
        const formData = new URLSearchParams();
        formData.append('course_id', opt.dataset.courseId);
        formData.append('year_level', opt.dataset.yearLevel);
        formData.append('section_label', opt.dataset.sectionLabel);
        formData.append('live_type', liveType);

        fetch('../api/start_live.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(res => res.json())
            .then(function (data) {
                if (!data.success) { alert('Failed to start live session.'); return; }

                currentRoomId = data.room_id;
                outgoingStream = canvas.captureStream(30);
                startBtn.disabled = true;
                stopBtn.disabled = false;
                sectionSelect.disabled = true;
                modeSelect.disabled = true;
                gameSelect.disabled = true;

                socket = io(LIVE_SERVER_URL);
                socket.emit('professor-start-room', { roomId: currentRoomId, name: CURRENT_USER_NAME, avatar: null });

                socket.on('viewer-count', function (count) {
                    document.getElementById('studioViewerCount').textContent = count;
                });

                socket.on('participants-updated', function (list) {
                    const container = document.getElementById('studioWatchingList');
                    if (list.length === 0) {
                        container.innerHTML = '<div class="studio-empty-small">No one watching yet.</div>';
                        return;
                    }
                    container.innerHTML = list.map(function (p) {
                        const avatarStyle = p.avatar ? ` style="background-image:url('../${p.avatar}')"` : '';
                        return `<div class="studio-watching-item"><div class="avatar-circle"${avatarStyle}></div><span>${p.name}</span></div>`;
                    }).join('');
                });

                socket.on('student-joined', connectToStudent);
                socket.on('existing-students', function (ids) { ids.forEach(connectToStudent); });

                socket.on('webrtc-signal', function (data) {
                    const pc = peerConnections[data.senderSocketId];
                    if (!pc) return;
                    if (data.signal.type === 'answer') {
                        pc.setRemoteDescription(new RTCSessionDescription(data.signal.sdp));
                    } else if (data.signal.type === 'ice-candidate') {
                        pc.addIceCandidate(new RTCIceCandidate(data.signal.candidate)).catch(console.error);
                    }
                });

                socket.on('student-left', function (id) {
                    if (peerConnections[id]) { peerConnections[id].close(); delete peerConnections[id]; }
                });

                socket.on('chat-message', function (data) {
                    appendChatMessage(data.senderName, data.message);
                });
            });
    });

    stopBtn.addEventListener('click', function () {
        if (!currentRoomId) return;
        fetch('../api/end_live.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'room_id=' + encodeURIComponent(currentRoomId)
        }).then(function () {
            Object.values(peerConnections).forEach(pc => pc.close());
            peerConnections = {};
            if (socket) socket.disconnect();
            currentRoomId = null;

            startBtn.disabled = false;
            stopBtn.disabled = true;
            sectionSelect.disabled = false;
            modeSelect.disabled = false;
            gameSelect.disabled = false;
        });
    });

    // ===== LIVE CHAT =====
    const chatInput = document.getElementById('studioChatInput');
    const chatSendBtn = document.getElementById('studioChatSendBtn');
    const chatMessages = document.getElementById('studioChatMessages');

    function appendChatMessage(name, msg) {
        const empty = chatMessages.querySelector('.studio-chat-empty');
        if (empty) empty.remove();
        const row = document.createElement('div');
        row.style.cssText = 'color:var(--text-light); font-size:12px; margin-bottom:8px; line-height:1.4;';
        row.innerHTML = '<strong>' + name + ':</strong> ' + msg;
        chatMessages.appendChild(row);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendChat() {
        const text = chatInput.value.trim();
        if (!text || !socket || !currentRoomId) return;
        socket.emit('chat-message', { roomId: currentRoomId, senderName: CURRENT_USER_NAME, message: text });
        chatInput.value = '';
    }

    chatSendBtn.addEventListener('click', sendChat);
    chatInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') sendChat();
    });

    window.addEventListener('beforeunload', function () {
        sources.forEach(s => s.stream.getTracks().forEach(t => t.stop()));
    });
});