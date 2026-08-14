document.addEventListener('DOMContentLoaded', function () {
    const bar = document.getElementById('participantsBar');
    if (!bar) return;

    const MAX_VISIBLE = 12;
    let participants = [];
    let speakingIds = new Set();

    function initials(name) {
        return (name || '?').trim().split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
    }

    function render() {
        const visible = participants.slice(0, MAX_VISIBLE);
        const extra = participants.length - visible.length;

        bar.innerHTML = visible.map(function (p) {
            const avatarStyle = p.avatar ? ` style="background-image:url('../${p.avatar}')"` : '';
            const speaking = speakingIds.has(p.id) ? ' speaking' : '';
            return `<div class="participant-avatar${speaking}" data-id="${p.id}" title="${p.name}"${avatarStyle}>${p.avatar ? '' : initials(p.name)}</div>`;
        }).join('') + (extra > 0 ? `<div class="participant-avatar participant-avatar-extra">+${extra}</div>` : '');
    }

    window.__renderParticipants = function (list) {
        participants = list;
        render();
    };

    window.__setSpeaking = function (socketId, isSpeaking) {
        if (isSpeaking) speakingIds.add(socketId); else speakingIds.delete(socketId);
        render();
    };
});

function watchSpeaking(stream, onChange) {
    if (!stream || stream.getAudioTracks().length === 0) return;
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const analyser = audioCtx.createAnalyser();
    analyser.fftSize = 512;
    const source = audioCtx.createMediaStreamSource(stream);
    source.connect(analyser);
    const data = new Uint8Array(analyser.frequencyBinCount);
    let isSpeaking = false;

    function tick() {
        analyser.getByteFrequencyData(data);
        const avg = data.reduce((a, b) => a + b, 0) / data.length;
        const nowSpeaking = avg > 18;
        if (nowSpeaking !== isSpeaking) {
            isSpeaking = nowSpeaking;
            onChange(isSpeaking);
        }
        requestAnimationFrame(tick);
    }
    tick();
}

document.addEventListener('DOMContentLoaded', function () {
    const roomThemeToggle = document.getElementById('roomThemeToggle');
    function applyRoomTheme(isDark) {
        document.body.classList.toggle('theme-dark', isDark);
    }
    const savedRoomTheme = localStorage.getItem('cdsga_theme') || 'light';
    applyRoomTheme(savedRoomTheme !== 'light');

    if (roomThemeToggle) {
        roomThemeToggle.addEventListener('click', function () {
            const nextIsDark = !document.body.classList.contains('theme-dark');
            applyRoomTheme(nextIsDark);
            localStorage.setItem('cdsga_theme', nextIsDark ? 'dark' : 'light');
        });
    }

    const socket = io(LIVE_SERVER_URL);
    const mainVideo = document.getElementById('mainVideo');
    const mainVideoEmpty = document.getElementById('mainVideoEmpty');
    const viewerCountNum = document.getElementById('viewerCountNum');
    const roomTimer = document.getElementById('roomTimer');
    const cameraOffOverlay = document.getElementById('cameraOffOverlay');
    const mainVideoWrap = document.querySelector('.room-main-video-wrap');
    const roomPipWrap = document.getElementById('roomPipWrap');
    const pipVideo = document.getElementById('pipVideo');
    const pipOffLabel = document.getElementById('pipOffLabel');
    const roomChatMessages = document.getElementById('roomChatMessages');
    const roomChatEmpty = document.getElementById('roomChatEmpty');
    const roomChatInput = document.getElementById('roomChatInput');
    const professorAwayOverlay = document.getElementById('professorAwayOverlay');

    let localStream = null;
    let peerConnections = {};
    const ICE_SERVERS = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

    // Browsers block autoplay of an UNMUTED video unless the page already has
    // "user gesture" credit. Just setting .srcObject is not enough — the video
    // element quietly stays paused on its first frame (looks "frozen"/hindi
    // gumagalaw) until something calls .play(). This helper calls .play(),
    // and if the browser rejects it (NotAllowedError), it falls back to
    // muted playback and auto-unmutes on the viewer's next click anywhere
    // in the room (chat, controls, etc. all count).
    function safePlay(videoEl) {
        const playPromise = videoEl.play();
        if (playPromise && playPromise.catch) {
            playPromise.catch(function () {
                videoEl.muted = true;
                videoEl.play().catch(function () {});
                document.addEventListener('click', function unmuteOnce() {
                    videoEl.muted = false;
                    document.removeEventListener('click', unmuteOnce);
                }, { once: true });
            });
        }
    }

    function startTimer() {
        const startedAt = new Date(ROOM_STARTED_AT.replace(' ', 'T'));
        setInterval(function () {
            const diffSec = Math.max(0, Math.floor((Date.now() - startedAt.getTime()) / 1000));
            const h = Math.floor(diffSec / 3600);
            const m = Math.floor((diffSec % 3600) / 60);
            const s = diffSec % 60;
            const pad = n => String(n).padStart(2, '0');
            roomTimer.textContent = h > 0 ? (pad(h) + ':' + pad(m) + ':' + pad(s)) : (pad(m) + ':' + pad(s));
        }, 1000);
    }
    startTimer();

    socket.on('viewer-count', function (count) {
        viewerCountNum.textContent = count;
    });

    socket.on('participants-updated', function (list) {
        if (window.__renderParticipants) window.__renderParticipants(list);
    });
    socket.on('speaking-changed', function (data) {
        if (window.__setSpeaking) window.__setSpeaking(data.socketId, data.isSpeaking);
    });

    function appendChatMessage(senderName, message, isOwn) {
        if (roomChatEmpty) roomChatEmpty.remove();
        const row = document.createElement('div');
        row.className = 'room-chat-msg' + (isOwn ? ' own' : '');
        const senderEl = document.createElement('span');
        senderEl.className = 'room-chat-msg-sender';
        senderEl.textContent = senderName;
        const textEl = document.createElement('span');
        textEl.textContent = message;
        row.appendChild(senderEl);
        row.appendChild(textEl);
        roomChatMessages.appendChild(row);
        roomChatMessages.scrollTop = roomChatMessages.scrollHeight;
    }

    socket.on('chat-message', function (data) {
        appendChatMessage(data.senderName, data.message, data.senderSocketId === socket.id);
    });

    if (roomChatInput) {
        roomChatInput.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            const text = roomChatInput.value.trim();
            if (!text) return;
            socket.emit('chat-message', { roomId: ROOM_ID, senderName: CURRENT_USER_NAME, message: text });
            roomChatInput.value = '';
        });
    }

    // ================= PROFESSOR =================
    if (typeof IS_PROFESSOR !== 'undefined' && IS_PROFESSOR) {
        const ctrlCamera = document.getElementById('ctrlCamera');
        const ctrlMic = document.getElementById('ctrlMic');
        const ctrlScreen = document.getElementById('ctrlScreen');
        const ctrlEnd = document.getElementById('ctrlEnd');
        const endRoomPopup = document.getElementById('endRoomPopup');
        const popupLeaveBtn = document.getElementById('popupLeaveBtn');
        const popupDeleteBtn = document.getElementById('popupDeleteBtn');
        const popupCancelBtn = document.getElementById('popupCancelBtn');

        const streamRequestToggle = document.getElementById('streamRequestToggle');
        const streamRequestBadge = document.getElementById('streamRequestBadge');
        const streamRequestPanel = document.getElementById('streamRequestPanel');
        const streamRequestList = document.getElementById('streamRequestList');
        const presenterBox = document.getElementById('presenterBox');
        const presenterBoxLabel = document.getElementById('presenterBoxLabel');
        const presenterVideo = document.getElementById('presenterVideo');
        const presenterBoxStopBtn = document.getElementById('presenterBoxStopBtn');
        const pendingRequests = {};
        let presenterPc = null;
        let currentPresenterSocketId = null;

        function renderStreamRequests() {
            const ids = Object.keys(pendingRequests);
            streamRequestBadge.textContent = ids.length;
            streamRequestBadge.classList.toggle('hidden', ids.length === 0);

            if (ids.length === 0) {
                streamRequestList.innerHTML = '<div class="stream-request-empty">No requests yet.</div>';
                return;
            }
            streamRequestList.innerHTML = ids.map(function (id) {
                return `
                    <div class="stream-request-item" data-student-id="${id}">
                        <span class="stream-request-item-name">${pendingRequests[id]}</span>
                        <div class="stream-request-actions">
                            <button type="button" class="stream-request-approve-btn" data-action="approve">Approve</button>
                            <button type="button" class="stream-request-deny-btn" data-action="deny">Deny</button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        streamRequestToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            streamRequestPanel.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (streamRequestPanel.classList.contains('open') && !streamRequestPanel.contains(e.target) && e.target !== streamRequestToggle) {
                streamRequestPanel.classList.remove('open');
            }
        });

        streamRequestList.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const item = btn.closest('.stream-request-item');
            const studentSocketId = item.dataset.studentId;
            socket.emit('respond-stream-request', {
                roomId: ROOM_ID,
                studentSocketId: studentSocketId,
                approve: btn.dataset.action === 'approve'
            });
            delete pendingRequests[studentSocketId];
            renderStreamRequests();
        });

        socket.on('stream-request-received', function (data) {
            pendingRequests[data.studentSocketId] = data.name;
            renderStreamRequests();
        });

        socket.on('presenter-changed', function (data) {
            currentPresenterSocketId = data.presenterSocketId;
            if (presenterPc) { presenterPc.close(); presenterPc = null; }

            if (data.presenterSocketId) {
                presenterBoxLabel.textContent = (data.name || 'Student') + ' is presenting';
                presenterBox.style.display = 'flex';
            } else {
                presenterBox.style.display = 'none';
                presenterVideo.srcObject = null;
            }
        });

        presenterBoxStopBtn.addEventListener('click', function () {
            if (!currentPresenterSocketId) return;
            presenterBox.style.display = 'none';
        });

        const saved = JSON.parse(sessionStorage.getItem('liveMediaState') || '{"camera":true,"mic":true}');
        const mediaState = { camera: saved.camera !== false, mic: saved.mic !== false };
        let screenActive = false;
        let screenStream = null;
        let cameraTrack = null;
        let hasCameraDevice = true;
        let hasMicDevice = true;

        function updateCtrlUI(btn, isOn) {
            btn.classList.toggle('state-on', isOn);
            btn.classList.toggle('state-off', !isOn);
        }

        async function detectDevices() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                hasCameraDevice = devices.some(d => d.kind === 'videoinput');
                hasMicDevice = devices.some(d => d.kind === 'audioinput');
            } catch (err) {
                hasCameraDevice = false;
                hasMicDevice = false;
            }
            if (!hasCameraDevice) mediaState.camera = false;
            if (!hasMicDevice) mediaState.mic = false;
            ctrlCamera.disabled = !hasCameraDevice;
            ctrlCamera.title = hasCameraDevice ? 'Camera' : 'No camera detected on this device';
            ctrlMic.disabled = !hasMicDevice;
            ctrlMic.title = hasMicDevice ? 'Microphone' : 'No microphone detected on this device';
        }

        async function initLocalStream() {
            await detectDevices();

            if (mediaState.camera || mediaState.mic) {
                try {
                    localStream = await navigator.mediaDevices.getUserMedia({ video: mediaState.camera, audio: mediaState.mic });
                    cameraTrack = localStream.getVideoTracks()[0] || null;
                    mainVideo.srcObject = localStream;
                    mainVideo.muted = true;
                    mainVideoEmpty.style.display = 'none';
                } catch (err) {
                    console.error('Camera/mic error:', err);
                    mediaState.camera = false;
                    mediaState.mic = false;
                    mainVideoEmpty.style.display = 'none';
                }
            } else {
                mainVideoEmpty.style.display = 'none';
            }

            updateCtrlUI(ctrlCamera, mediaState.camera);
            updateCtrlUI(ctrlMic, mediaState.mic);
            cameraOffOverlay.classList.toggle('active', !mediaState.camera);

            socket.emit('professor-start-room', { roomId: ROOM_ID, name: CURRENT_USER_NAME, avatar: CURRENT_USER_AVATAR });

            socket.on('connect', function () {
                socket.emit('professor-start-room', { roomId: ROOM_ID, name: CURRENT_USER_NAME, avatar: CURRENT_USER_AVATAR });
            });
        }
        initLocalStream();

        const speakingWatcherReady = setInterval(function () {
            if (localStream && localStream.getAudioTracks().length > 0) {
                clearInterval(speakingWatcherReady);
                watchSpeaking(localStream, function (isSpeaking) {
                    socket.emit('speaking-changed', { roomId: ROOM_ID, isSpeaking });
                    if (window.__setSpeaking) window.__setSpeaking(socket.id, isSpeaking);
                });
            }
        }, 1000);

        function connectToStudent(studentSocketId) {
            if (peerConnections[studentSocketId]) {
                peerConnections[studentSocketId].close();
            }

            const pc = new RTCPeerConnection(ICE_SERVERS);
            peerConnections[studentSocketId] = pc;

            if (screenActive && screenStream) {
                pc.addTrack(screenStream.getVideoTracks()[0], screenStream);
            } else if (localStream) {
                localStream.getVideoTracks().forEach(track => pc.addTrack(track, localStream));
            }

            if (localStream) {
                localStream.getAudioTracks().forEach(track => pc.addTrack(track, localStream));
            }

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

        ctrlCamera.addEventListener('click', async function () {
            if (!hasCameraDevice) return;
            if (!mediaState.camera && (!localStream || localStream.getVideoTracks().length === 0)) {
                try {
                    const camStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    const newTrack = camStream.getVideoTracks()[0];
                    if (localStream) { localStream.addTrack(newTrack); } else { localStream = camStream; }
                    cameraTrack = newTrack;
                    mainVideo.srcObject = localStream;
                    mainVideo.muted = true;
                    mainVideoEmpty.style.display = 'none';
                    addOrReplaceOutgoingTrack(newTrack, localStream);
                } catch (err) {
                    console.error('Could not turn camera on:', err);
                    return;
                }
            }
            mediaState.camera = !mediaState.camera;
            if (localStream) localStream.getVideoTracks().forEach(t => t.enabled = mediaState.camera);
            updateCtrlUI(ctrlCamera, mediaState.camera);
            if (screenActive) {
                pipOffLabel.classList.toggle('active', !mediaState.camera);
            } else {
                cameraOffOverlay.classList.toggle('active', !mediaState.camera);
            }
            socket.emit('media-state-changed', { roomId: ROOM_ID, media: 'camera', isOn: mediaState.camera });
        });

        ctrlMic.addEventListener('click', async function () {
            if (!hasMicDevice) return;
            if (!mediaState.mic && (!localStream || localStream.getAudioTracks().length === 0)) {
                try {
                    const micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    const newTrack = micStream.getAudioTracks()[0];
                    if (localStream) { localStream.addTrack(newTrack); } else { localStream = micStream; }
                    addOrReplaceOutgoingTrack(newTrack, localStream);
                } catch (err) {
                    console.error('Could not turn mic on:', err);
                    return;
                }
            }
            mediaState.mic = !mediaState.mic;
            if (localStream) localStream.getAudioTracks().forEach(t => t.enabled = mediaState.mic);
            updateCtrlUI(ctrlMic, mediaState.mic);
            socket.emit('media-state-changed', { roomId: ROOM_ID, media: 'mic', isOn: mediaState.mic });
        });

        ctrlScreen.addEventListener('click', async function () {
            if (!screenActive) {
                try {
                    screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const screenTrack = screenStream.getVideoTracks()[0];
                    addOrReplaceOutgoingTrack(screenTrack, screenStream);
                    mainVideo.srcObject = screenStream;
                    screenActive = true;
                    updateCtrlUI(ctrlScreen, true);
                    mainVideoWrap.classList.add('screen-sharing');
                    cameraOffOverlay.classList.remove('active');
                    roomPipWrap.style.display = 'block';
                    pipVideo.srcObject = localStream;
                    pipOffLabel.classList.toggle('active', !mediaState.camera);
                    screenTrack.onended = stopScreenShare;
                    socket.emit('media-state-changed', { roomId: ROOM_ID, media: 'screen', isOn: true });
                } catch (err) {
                    console.error('Screen share cancelled:', err);
                }
            } else {
                stopScreenShare();
            }
        });

        function stopScreenShare() {
            if (screenStream) {
                screenStream.getTracks().forEach(t => t.stop());
                screenStream = null;
            }
            if (cameraTrack) addOrReplaceOutgoingTrack(cameraTrack, localStream);
            mainVideo.srcObject = localStream;
            screenActive = false;
            updateCtrlUI(ctrlScreen, false);
            mainVideoWrap.classList.remove('screen-sharing');
            roomPipWrap.style.display = 'none';
            cameraOffOverlay.classList.toggle('active', !mediaState.camera);
            socket.emit('media-state-changed', { roomId: ROOM_ID, media: 'screen', isOn: false });
        }

        function addOrReplaceOutgoingTrack(newTrack, streamForTrack) {
            const kind = newTrack.kind;
            Object.keys(peerConnections).forEach(function (studentSocketId) {
                const pc = peerConnections[studentSocketId];
                const sender = pc.getSenders().find(s => s.track && s.track.kind === kind);
                if (sender) {
                    sender.replaceTrack(newTrack);
                } else {
                    connectToStudent(studentSocketId);
                }
            });
        }

        socket.on('student-joined', connectToStudent);

        socket.on('existing-students', function (studentSocketIds) {
            studentSocketIds.forEach(connectToStudent);
        });

        socket.on('webrtc-signal', function (data) {
            if (data.signal.type === 'offer' && data.signal.role === 'presenter') {
                if (presenterPc) presenterPc.close();
                presenterPc = new RTCPeerConnection(ICE_SERVERS);

                presenterPc.ontrack = function (e) {
                    presenterVideo.srcObject = e.streams[0];
                    safePlay(presenterVideo);
                };
                presenterPc.onicecandidate = function (e) {
                    if (e.candidate) {
                        socket.emit('webrtc-signal', {
                            targetSocketId: data.senderSocketId,
                            signal: { type: 'ice-candidate', candidate: e.candidate }
                        });
                    }
                };
                presenterPc.setRemoteDescription(new RTCSessionDescription(data.signal.sdp))
                    .then(() => presenterPc.createAnswer())
                    .then(answer => presenterPc.setLocalDescription(answer))
                    .then(function () {
                        socket.emit('webrtc-signal', {
                            targetSocketId: data.senderSocketId,
                            signal: { type: 'answer', sdp: presenterPc.localDescription }
                        });
                    });
                return;
            }

            const pc = peerConnections[data.senderSocketId];
            if (!pc) return;
            if (data.signal.type === 'answer') {
                pc.setRemoteDescription(new RTCSessionDescription(data.signal.sdp));
            } else if (data.signal.type === 'ice-candidate') {
                pc.addIceCandidate(new RTCIceCandidate(data.signal.candidate)).catch(console.error);
            }
        });

        socket.on('student-left', function (studentSocketId) {
            if (peerConnections[studentSocketId]) {
                peerConnections[studentSocketId].close();
                delete peerConnections[studentSocketId];
            }
        });

        function closeAllPeerConnections() {
            Object.values(peerConnections).forEach(pc => pc.close());
            peerConnections = {};
        }

        ctrlEnd.addEventListener('click', () => endRoomPopup.classList.add('open'));
        popupCancelBtn.addEventListener('click', () => endRoomPopup.classList.remove('open'));

        popupLeaveBtn.addEventListener('click', function () {
            closeAllPeerConnections();
            socket.emit('professor-leave-room', ROOM_ID);
            if (localStream) localStream.getTracks().forEach(t => t.stop());
            if (screenStream) screenStream.getTracks().forEach(t => t.stop());
            socket.disconnect();
            window.location.href = 'dashboard.php';
        });

        popupDeleteBtn.addEventListener('click', function () {
            fetch('../api/end_live.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'room_id=' + encodeURIComponent(ROOM_ID)
            })
                .then(res => res.json())
                .then(function () {
                    closeAllPeerConnections();
                    socket.emit('professor-end-room', ROOM_ID);
                    if (localStream) localStream.getTracks().forEach(t => t.stop());
                    if (screenStream) screenStream.getTracks().forEach(t => t.stop());
                    socket.disconnect();
                    window.location.href = 'live.php';
                });
        });

        window.addEventListener('beforeunload', function () {
            if (localStream) localStream.getTracks().forEach(t => t.stop());
            if (screenStream) screenStream.getTracks().forEach(t => t.stop());
        });
    }

    // ================= STUDENT =================
    else {
        const ctrlLeave = document.getElementById('ctrlLeave');
        const ctrlRequestStream = document.getElementById('ctrlRequestStream');
        const ctrlStopPresenting = document.getElementById('ctrlStopPresenting');
        const presenterBox = document.getElementById('presenterBox');
        const presenterBoxLabel = document.getElementById('presenterBoxLabel');
        const presenterVideo = document.getElementById('presenterVideo');
        let profSocketId = null;
        let pc = null;

        let isPresenting = false;
        let presenterLocalStream = null;
        let presenterPeerConnections = {};
        let watchingPresenterPc = null;

        ctrlRequestStream.addEventListener('click', function () {
            if (ctrlRequestStream.classList.contains('requesting')) return;
            ctrlRequestStream.classList.add('requesting');
            ctrlRequestStream.title = 'Waiting for approval...';
            socket.emit('request-to-stream', { roomId: ROOM_ID, name: CURRENT_USER_NAME });
        });

        socket.on('stream-request-denied', function (data) {
            ctrlRequestStream.classList.remove('requesting');
            ctrlRequestStream.title = 'Send stream request';
            alert(data.reason || 'Your request was declined.');
        });

        socket.on('stream-request-approved', function (data) {
            isPresenting = true;
            ctrlRequestStream.style.display = 'none';
            ctrlStopPresenting.style.display = 'flex';

            navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(function (stream) {
                    presenterLocalStream = stream;
                    data.peers.forEach(connectToPeerAsPresenter);
                    watchSpeaking(presenterLocalStream, function (isSpeaking) {
                        socket.emit('speaking-changed', { roomId: ROOM_ID, isSpeaking });
                        if (window.__setSpeaking) window.__setSpeaking(socket.id, isSpeaking);
                    });
                })
                .catch(function (err) {
                    console.error('Could not start presenting stream:', err);
                    alert('Could not access your camera/microphone.');
                    stopPresenting();
                });
        });

        function connectToPeerAsPresenter(peerSocketId) {
            const peerPc = new RTCPeerConnection(ICE_SERVERS);
            presenterPeerConnections[peerSocketId] = peerPc;

            presenterLocalStream.getTracks().forEach(track => peerPc.addTrack(track, presenterLocalStream));

            peerPc.onicecandidate = function (e) {
                if (e.candidate) {
                    socket.emit('webrtc-signal', {
                        targetSocketId: peerSocketId,
                        signal: { type: 'ice-candidate', candidate: e.candidate }
                    });
                }
            };

            peerPc.createOffer()
                .then(offer => peerPc.setLocalDescription(offer))
                .then(function () {
                    socket.emit('webrtc-signal', {
                        targetSocketId: peerSocketId,
                        signal: { type: 'offer', sdp: peerPc.localDescription, role: 'presenter' }
                    });
                });
        }

        function stopPresenting() {
            isPresenting = false;
            Object.values(presenterPeerConnections).forEach(p => p.close());
            presenterPeerConnections = {};
            if (presenterLocalStream) {
                presenterLocalStream.getTracks().forEach(t => t.stop());
                presenterLocalStream = null;
            }
            ctrlRequestStream.style.display = 'flex';
            ctrlRequestStream.classList.remove('requesting');
            ctrlRequestStream.title = 'Send stream request';
            ctrlStopPresenting.style.display = 'none';
            socket.emit('stop-presenting', { roomId: ROOM_ID });
        }

        ctrlStopPresenting.addEventListener('click', stopPresenting);

        socket.on('presenter-changed', function (data) {
            if (isPresenting && data.presenterSocketId !== socket.id) {
                stopPresenting();
            }

            if (watchingPresenterPc) { watchingPresenterPc.close(); watchingPresenterPc = null; }

            if (data.presenterSocketId && data.presenterSocketId !== socket.id) {
                presenterBoxLabel.textContent = (data.name || 'Classmate') + ' is presenting';
                presenterBox.style.display = 'flex';
                ctrlRequestStream.disabled = true;
            } else if (!data.presenterSocketId) {
                presenterBox.style.display = 'none';
                presenterVideo.srcObject = null;
                ctrlRequestStream.disabled = false;
            }
        });

        console.log('[LIVE DEBUG] Joining room:', ROOM_ID);
        let joinAttempts = 0;
        socket.emit('student-join-room', { roomId: ROOM_ID, name: CURRENT_USER_NAME, avatar: CURRENT_USER_AVATAR });

        socket.on('media-state-changed', function (data) {
            if (data.media === 'camera') {
                cameraOffOverlay.classList.toggle('active', !data.isOn);
            } else if (data.media === 'screen' && data.isOn) {
                cameraOffOverlay.classList.remove('active');
            }
        });

        socket.on('professor-away', function () {
            if (pc) {
                pc.close();
                pc = null;
            }
            mainVideoEmpty.style.display = 'none';
            cameraOffOverlay.classList.remove('active');
            professorAwayOverlay.classList.add('active');
        });

        socket.on('professor-back', function () {
            professorAwayOverlay.classList.remove('active');
            mainVideoEmpty.style.display = 'flex';
        });

        socket.on('room-not-found', function () {
            joinAttempts++;
            console.log('[LIVE DEBUG] room-not-found, attempt', joinAttempts);
            if (joinAttempts < 3) {
                setTimeout(function () {
                    socket.emit('student-join-room', { roomId: ROOM_ID, name: CURRENT_USER_NAME, avatar: CURRENT_USER_AVATAR });
                }, 2000);
                return;
            }
            alert('This live class has ended.');
            window.location.href = 'live.php';
        });

        socket.on('room-ended', function () {
            alert('The professor has ended this live class.');
            window.location.href = 'live.php';
        });

        socket.on('webrtc-signal', function (data) {
            if (data.signal.type === 'offer' && data.signal.role === 'presenter') {
                if (watchingPresenterPc) watchingPresenterPc.close();
                watchingPresenterPc = new RTCPeerConnection(ICE_SERVERS);

                watchingPresenterPc.ontrack = function (e) {
                    presenterVideo.srcObject = e.streams[0];
                    safePlay(presenterVideo);
                };
                watchingPresenterPc.onicecandidate = function (e) {
                    if (e.candidate) {
                        socket.emit('webrtc-signal', {
                            targetSocketId: data.senderSocketId,
                            signal: { type: 'ice-candidate', candidate: e.candidate }
                        });
                    }
                };
                watchingPresenterPc.setRemoteDescription(new RTCSessionDescription(data.signal.sdp))
                    .then(() => watchingPresenterPc.createAnswer())
                    .then(answer => watchingPresenterPc.setLocalDescription(answer))
                    .then(function () {
                        socket.emit('webrtc-signal', {
                            targetSocketId: data.senderSocketId,
                            signal: { type: 'answer', sdp: watchingPresenterPc.localDescription }
                        });
                    });
                return;
            }

            if (data.signal.type === 'offer') {
                if (pc) pc.close();

                professorAwayOverlay.classList.remove('active');
                profSocketId = data.senderSocketId;
                pc = new RTCPeerConnection(ICE_SERVERS);

                pc.ontrack = function (e) {
                    mainVideo.srcObject = e.streams[0];
                    mainVideoEmpty.style.display = 'none';
                    safePlay(mainVideo);
                };

                pc.onicecandidate = function (e) {
                    if (e.candidate) {
                        socket.emit('webrtc-signal', {
                            targetSocketId: profSocketId,
                            signal: { type: 'ice-candidate', candidate: e.candidate }
                        });
                    }
                };

                pc.setRemoteDescription(new RTCSessionDescription(data.signal.sdp))
                    .then(() => pc.createAnswer())
                    .then(answer => pc.setLocalDescription(answer))
                    .then(function () {
                        socket.emit('webrtc-signal', {
                            targetSocketId: profSocketId,
                            signal: { type: 'answer', sdp: pc.localDescription }
                        });
                    });
            } else if (data.signal.type === 'ice-candidate' && pc) {
                pc.addIceCandidate(new RTCIceCandidate(data.signal.candidate)).catch(console.error);
            }
        });

        ctrlLeave.addEventListener('click', function () {
            if (pc) pc.close();
            if (watchingPresenterPc) watchingPresenterPc.close();
            if (isPresenting) stopPresenting();
            socket.disconnect();
            window.location.href = 'live.php';
        });
    }
});