document.addEventListener('DOMContentLoaded', function () {
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

    let localStream = null;
    let peerConnections = {};
    const ICE_SERVERS = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

    // ===== TIMER (counts up from when the room started) =====
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

    // ===== VIEWER COUNT (both roles listen for this) =====
    socket.on('viewer-count', function (count) {
        viewerCountNum.textContent = count;
    });

    // ===== LIVE CHAT (both roles) =====
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

        const saved = JSON.parse(sessionStorage.getItem('liveMediaState') || '{"camera":true,"mic":true}');
        const mediaState = { camera: saved.camera !== false, mic: saved.mic !== false };
        let screenActive = false;
        let screenStream = null;
        let cameraTrack = null;

        function updateCtrlUI(btn, isOn) {
            btn.classList.toggle('state-on', isOn);
            btn.classList.toggle('state-off', !isOn);
        }

        async function initLocalStream() {
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: mediaState.camera, audio: mediaState.mic });
                cameraTrack = localStream.getVideoTracks()[0] || null;
                mainVideo.srcObject = localStream;
                mainVideo.muted = true;
                mainVideoEmpty.style.display = 'none';
                updateCtrlUI(ctrlCamera, mediaState.camera);
                updateCtrlUI(ctrlMic, mediaState.mic);
                cameraOffOverlay.classList.toggle('active', !mediaState.camera);
                socket.emit('professor-start-room', ROOM_ID);

                // Re-register the room on the server if the socket reconnects
                // (e.g. brief WiFi drop, or liveserver restart) so students can still find it
                socket.on('connect', function () {
                    socket.emit('professor-start-room', ROOM_ID);
                });
            } catch (err) {
                console.error('Camera/mic error:', err);
                mainVideoEmpty.textContent = 'Could not access camera/microphone. Check browser permissions.';
            }
        }
        initLocalStream();

        ctrlCamera.addEventListener('click', function () {
            if (!localStream) return;
            mediaState.camera = !mediaState.camera;
            localStream.getVideoTracks().forEach(t => t.enabled = mediaState.camera);
            updateCtrlUI(ctrlCamera, mediaState.camera);
            if (screenActive) {
                pipOffLabel.classList.toggle('active', !mediaState.camera);
            } else {
                cameraOffOverlay.classList.toggle('active', !mediaState.camera);
            }
            socket.emit('media-state-changed', { roomId: ROOM_ID, media: 'camera', isOn: mediaState.camera });
        });

        ctrlMic.addEventListener('click', function () {
            if (!localStream) return;
            mediaState.mic = !mediaState.mic;
            localStream.getAudioTracks().forEach(t => t.enabled = mediaState.mic);
            updateCtrlUI(ctrlMic, mediaState.mic);
            socket.emit('media-state-changed', { roomId: ROOM_ID, media: 'mic', isOn: mediaState.mic });
        });

        ctrlScreen.addEventListener('click', async function () {
            if (!screenActive) {
                try {
                    screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const screenTrack = screenStream.getVideoTracks()[0];
                    replaceOutgoingVideoTrack(screenTrack);
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
            if (cameraTrack) replaceOutgoingVideoTrack(cameraTrack);
            mainVideo.srcObject = localStream;
            screenActive = false;
            updateCtrlUI(ctrlScreen, false);
            mainVideoWrap.classList.remove('screen-sharing');
            roomPipWrap.style.display = 'none';
            cameraOffOverlay.classList.toggle('active', !mediaState.camera);
            socket.emit('media-state-changed', { roomId: ROOM_ID, media: 'screen', isOn: false });
        }

        function replaceOutgoingVideoTrack(newTrack) {
            Object.values(peerConnections).forEach(function (pc) {
                const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) sender.replaceTrack(newTrack);
            });
        }

        // A student joined -> create a peer connection FROM professor TO that student
        socket.on('student-joined', function (studentSocketId) {
            const pc = new RTCPeerConnection(ICE_SERVERS);
            peerConnections[studentSocketId] = pc;

            if (localStream) {
                localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
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
                        signal: { type: 'offer', sdp: pc.localDescription }
                    });
                });
        });

        socket.on('webrtc-signal', function (data) {
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

        // ===== End button -> Leave / Delete popup =====
        ctrlEnd.addEventListener('click', () => endRoomPopup.classList.add('open'));
        popupCancelBtn.addEventListener('click', () => endRoomPopup.classList.remove('open'));

        popupLeaveBtn.addEventListener('click', function () {
            // Leaving now ends the session the same way Delete Room does — there is
            // no "resume" feature, so a dangling "live" row only confuses students.
            fetch('../api/end_live.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'room_id=' + encodeURIComponent(ROOM_ID)
            }).finally(function () {
                socket.emit('professor-end-room', ROOM_ID);
                socket.disconnect();
                window.location.href = 'dashboard.php';
            });
        });

        popupDeleteBtn.addEventListener('click', function () {
            fetch('../api/end_live.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'room_id=' + encodeURIComponent(ROOM_ID)
            })
                .then(res => res.json())
                .then(function () {
                    socket.emit('professor-end-room', ROOM_ID);
                    socket.disconnect();
                    window.location.href = 'live.php';
                });
        });

        window.addEventListener('beforeunload', function () {
            if (localStream) localStream.getTracks().forEach(t => t.stop());
            if (screenStream) screenStream.getTracks().forEach(t => t.stop());
            // Safety net: if the professor closes the tab/browser directly instead of
            // clicking Leave or Delete Room, still mark the session ended in the database
            // so it doesn't stay stuck as "live" for students.
            navigator.sendBeacon('../api/end_live.php', new URLSearchParams({ room_id: ROOM_ID }));
        });
    }

    // ================= STUDENT =================
    else {
        const ctrlLeave = document.getElementById('ctrlLeave');
        let profSocketId = null;
        let pc = null;

        socket.emit('student-join-room', ROOM_ID);

        socket.on('media-state-changed', function (data) {
            if (data.media === 'camera') {
                cameraOffOverlay.classList.toggle('active', !data.isOn);
            } else if (data.media === 'screen' && data.isOn) {
                cameraOffOverlay.classList.remove('active');
            }
        });

        socket.on('room-not-found', function () {
            alert('This live class has ended.');
            window.location.href = 'live.php';
        });

        socket.on('room-ended', function () {
            alert('The professor has ended this live class.');
            window.location.href = 'live.php';
        });

        socket.on('webrtc-signal', function (data) {
            if (data.signal.type === 'offer') {
                profSocketId = data.senderSocketId;
                pc = new RTCPeerConnection(ICE_SERVERS);

                pc.ontrack = function (e) {
                    mainVideo.srcObject = e.streams[0];
                    mainVideoEmpty.style.display = 'none';
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
            socket.disconnect();
            window.location.href = 'live.php';
        });
    }
});