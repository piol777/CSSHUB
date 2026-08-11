document.addEventListener('DOMContentLoaded', function () {
    // ===== Theme (Live Room lang): Light / Gray — walang Dark Purple dito =====
    const roomThemeToggle = document.getElementById('roomThemeToggle');
    function applyRoomTheme(isDark) {
        document.body.classList.toggle('theme-dark', isDark);
    }
    // Ginagamit yung parehong 'cdsga_theme' key ng buong site, pero dito sa Live
    // page, ang 'dark-purple' ay itinuturing na lang na 'dark' (gray).
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
                    mainVideoEmpty.textContent = 'No camera/microphone available. You can still share your screen.';
                }
            } else {
                mainVideoEmpty.textContent = 'No camera/microphone available. You can still share your screen.';
            }

            updateCtrlUI(ctrlCamera, mediaState.camera);
            updateCtrlUI(ctrlMic, mediaState.mic);
            cameraOffOverlay.classList.toggle('active', !mediaState.camera);

            // Register the room regardless of camera/mic outcome — screen share
            // alone is enough to run a live session.
            socket.emit('professor-start-room', ROOM_ID);

            // Re-register the room on the server if the socket reconnects
            // (e.g. brief WiFi drop, or liveserver restart) so students can still find it
            socket.on('connect', function () {
                socket.emit('professor-start-room', ROOM_ID);
            });
        }
        initLocalStream();

        // Build (or rebuild) a peer connection to one student and send them an offer.
        // Used both for brand-new joins and for students who were already watching
        // when the professor resumes a paused room.
        function connectToStudent(studentSocketId) {
            if (peerConnections[studentSocketId]) {
                peerConnections[studentSocketId].close();
            }

            const pc = new RTCPeerConnection(ICE_SERVERS);
            peerConnections[studentSocketId] = pc;

            // Video: kung naka-Screen Share na ang professor, ipadala agad yung
            // screen (hindi camera) para makita agad ng bagong student — hindi
            // na niya kailangang antayin pang i-restart ng professor.
            if (screenActive && screenStream) {
                pc.addTrack(screenStream.getVideoTracks()[0], screenStream);
            } else if (localStream) {
                localStream.getVideoTracks().forEach(track => pc.addTrack(track, localStream));
            }

            // Audio: laging galing sa mic (localStream), hiwalay sa video source.
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
                        signal: { type: 'offer', sdp: pc.localDescription }
                    });
                });
        }

        ctrlCamera.addEventListener('click', async function () {
            if (!hasCameraDevice) return;
            if (!mediaState.camera && (!localStream || localStream.getVideoTracks().length === 0)) {
                // Turning camera ON and we don't have a video track yet — get one now.
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
                    return; // walang crash, mananatiling off
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

        // Adds a track to every active peer connection. If that peer connection
        // already has a sender of the same kind (video/audio), just swap the
        // track (cheap, no renegotiation needed). If it has NONE yet (e.g. no
        // camera at all when the connection was first created), add it fresh
        // and send a new offer so the student's browser picks it up.
        function addOrReplaceOutgoingTrack(newTrack, streamForTrack) {
            const kind = newTrack.kind;
            Object.keys(peerConnections).forEach(function (studentSocketId) {
                const pc = peerConnections[studentSocketId];
                const sender = pc.getSenders().find(s => s.track && s.track.kind === kind);
                if (sender) {
                    sender.replaceTrack(newTrack);
                } else {
                    // Walang existing sender ng ganitong kind (hal. wala pang video
                    // dati, gaya ng preview na naka-connect bago pa mag-Screen Share
                    // ang professor). Sa halip na i-patch lang ang existing connection
                    // (na siyang may bug — hindi tama ang na-re-negotiate na video),
                    // gawin na lang ulit ang buong connection mula sa umpisa gamit ang
                    // parehong paraan na gumagana sa totoong pag-Join.
                    connectToStudent(studentSocketId);
                }
            });
        }

        // A student joined -> create a peer connection FROM professor TO that student
        socket.on('student-joined', connectToStudent);

        // Resuming a paused room -> reconnect to everyone who was already watching
        socket.on('existing-students', function (studentSocketIds) {
            studentSocketIds.forEach(connectToStudent);
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

        function closeAllPeerConnections() {
            Object.values(peerConnections).forEach(pc => pc.close());
            peerConnections = {};
        }

        // ===== End button -> Leave / Delete popup =====
        ctrlEnd.addEventListener('click', () => endRoomPopup.classList.add('open'));
        popupCancelBtn.addEventListener('click', () => endRoomPopup.classList.remove('open'));

        popupLeaveBtn.addEventListener('click', function () {
            // Room stays "live" in the database and on the Live page — the professor
            // (or anyone still watching) can come back to it later.
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
            // Just release the camera/mic here. The room itself is left "live" and
            // resumable — the server marks it paused automatically once this socket
            // disconnects (see liveserver/server.js), no database call needed.
            if (localStream) localStream.getTracks().forEach(t => t.stop());
            if (screenStream) screenStream.getTracks().forEach(t => t.stop());
        });
    }

    // ================= STUDENT =================
    else {
        const ctrlLeave = document.getElementById('ctrlLeave');
        let profSocketId = null;
        let pc = null;

        console.log('[LIVE DEBUG] Joining room:', ROOM_ID);
        let joinAttempts = 0;
        socket.emit('student-join-room', ROOM_ID);

        socket.on('media-state-changed', function (data) {
            if (data.media === 'camera') {
                cameraOffOverlay.classList.toggle('active', !data.isOn);
            } else if (data.media === 'screen' && data.isOn) {
                cameraOffOverlay.classList.remove('active');
            }
        });

        // Professor stepped away (Leave, dropped connection, etc.) — room is still live,
        // just paused. Don't kick the student out, show a waiting state instead.
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
                // Baka bagong na-restart lang ang liveserver o kakastart lang ng prof —
                // subukan ulit bago sabihing "ended".
                setTimeout(function () {
                    socket.emit('student-join-room', ROOM_ID);
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
            if (data.signal.type === 'offer') {
                // A fresh offer (first connect, OR the professor resuming) —
                // discard any stale connection first.
                if (pc) pc.close();

                professorAwayOverlay.classList.remove('active');
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