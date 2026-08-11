document.addEventListener('DOMContentLoaded', function () {
    const liveGrid = document.getElementById('liveGrid');
    const createRoomModal = document.getElementById('createRoomModal');
    const createRoomStep = document.getElementById('createRoomStep');
    const settingsFormatStep = document.getElementById('settingsFormatStep');
    const goLiveBtn = document.getElementById('goLiveBtn');
    const finalGoBtn = document.getElementById('finalGoBtn');
    const settingsCountdown = document.getElementById('settingsCountdown');
    const formatPreviewVideo = document.getElementById('formatPreviewVideo');

    // Media state for the room about to be created
    const mediaState = { camera: true, screen: false, mic: true };
    let localStream = null;
    let screenStream = null;
    let countdownTimer = null;
    let countdownValue = 40;
    const COOLDOWN_MS = 40000;
    const toggleCooldowns = { camera: false, screen: false, mic: false };

    // ===== STUDENT: live preview (isang room lang, muted, receive-only) =====
    let previewSocket = null;
    let previewPc = null;
    let previewRoomId = null;
    let previewProfSocketId = null;
    let previewStream = null;

    function teardownPreview() {
        if (previewPc) { previewPc.close(); previewPc = null; }
        previewRoomId = null;
        previewProfSocketId = null;
        previewStream = null;
    }

    function attachPreviewStreamToDom() {
        if (!previewStream || !previewRoomId) return;
        const video = document.querySelector('.live-preview-screen video[data-room="' + previewRoomId + '"]');
        if (video) {
            video.srcObject = previewStream;
            video.closest('.live-preview-screen').classList.add('is-live');
        }
    }

    function connectPreview(roomId) {
        if (previewRoomId === roomId) {
            attachPreviewStreamToDom(); // baka bagong DOM lang galing sa 8s refresh
            return;
        }
        teardownPreview();
        previewRoomId = roomId;

        if (!previewSocket) {
            previewSocket = io(LIVE_SERVER_URL);

            previewSocket.on('webrtc-signal', function (data) {
                if (data.signal.type === 'offer') {
                    if (previewPc) previewPc.close();
                    previewProfSocketId = data.senderSocketId;
                    previewPc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });

                    previewPc.ontrack = function (e) {
                        previewStream = e.streams[0];
                        attachPreviewStreamToDom();
                    };

                    previewPc.onicecandidate = function (e) {
                        if (e.candidate) {
                            previewSocket.emit('webrtc-signal', {
                                targetSocketId: previewProfSocketId,
                                signal: { type: 'ice-candidate', candidate: e.candidate }
                            });
                        }
                    };

                    previewPc.setRemoteDescription(new RTCSessionDescription(data.signal.sdp))
                        .then(() => previewPc.createAnswer())
                        .then(answer => previewPc.setLocalDescription(answer))
                        .then(function () {
                            previewSocket.emit('webrtc-signal', {
                                targetSocketId: previewProfSocketId,
                                signal: { type: 'answer', sdp: previewPc.localDescription }
                            });
                        });
                } else if (data.signal.type === 'ice-candidate' && previewPc) {
                    previewPc.addIceCandidate(new RTCIceCandidate(data.signal.candidate)).catch(() => {});
                }
            });

            previewSocket.on('room-not-found', teardownPreview);
            previewSocket.on('room-ended', teardownPreview);
        }

        previewSocket.emit('student-join-room', roomId);
    }

    function renderStudentView(sessions) {
        if (sessions.length === 0) {
            teardownPreview();
            liveGrid.innerHTML = `
                <div class="live-preview-card">
                    <div class="live-preview-status">
                        <span class="live-preview-dot"></span>
                        <span>Not streaming</span>
                    </div>
                    <div class="live-preview-screen"></div>
                    <button type="button" class="live-preview-join-btn" disabled>Join</button>
                </div>
            `;
            return;
        }

        let html = '';
        sessions.forEach(function (s) {
            const profName = 'Prof. ' + s.first_name + ' ' + s.last_name;
            const label = (s.course_code || 'General') + (s.year_level ? ' ' + s.year_level + 'Y' : '') + (s.section_label ? '-' + s.section_label : '');
            html += `
                <div class="live-preview-card">
                    <div class="live-preview-status is-live">
                        <span class="live-preview-dot"></span>
                        <span>${profName} — ${label}</span>
                    </div>
                    <div class="live-preview-screen">
                        <video data-room="${s.room_id}" autoplay playsinline muted></video>
                        <span class="live-preview-blur-label">Join to watch clearly</span>
                    </div>
                    <button type="button" class="live-preview-join-btn enabled" data-room-id="${s.room_id}">Join</button>
                </div>
            `;
        });
        liveGrid.innerHTML = html;

        document.querySelectorAll('.live-preview-join-btn.enabled').forEach(function (btn) {
            btn.addEventListener('click', function () {
                window.location.href = 'live_room.php?room=' + encodeURIComponent(this.dataset.roomId);
            });
        });

        // Iisang room lang ang pini-preview — karaniwan iisa lang naman ang
        // kaugnay na klase ng student anumang oras.
        connectPreview(sessions[0].room_id);
    }

    function loadActiveSessions() {
        fetch('../api/active_live_sessions.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                if (typeof IS_PROFESSOR !== 'undefined' && IS_PROFESSOR) {
                    let html = '';

                    data.sessions.forEach(s => {
                        const label = (s.course_code || 'General') + (s.year_level ? ' ' + s.year_level : '') + (s.section_label ? '-' + s.section_label : '');
                        html += `
                            <div class="live-room-card" data-room-id="${s.room_id}">
                                <div class="live-room-thumb">
                                    <span class="live-badge-small">LIVE</span>
                                </div>
                                <div class="live-room-label">${label}</div>
                            </div>
                        `;
                    });

                    html += `
                        <div class="live-room-card" id="addRoomCard">
                            <div class="add-room-thumb">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </div>
                            <div class="live-room-label">ADD ROOM</div>
                        </div>
                    `;

                    liveGrid.innerHTML = html;

                    const addRoomCard = document.getElementById('addRoomCard');
                    if (addRoomCard) {
                        addRoomCard.addEventListener('click', function () {
                            openCreateRoomModal();
                        });
                    }

                    document.querySelectorAll('.live-room-card[data-room-id]').forEach(card => {
                        card.addEventListener('click', function () {
                            window.location.href = 'live_room.php?room=' + encodeURIComponent(this.dataset.roomId);
                        });
                    });
                } else {
                    renderStudentView(data.sessions);
                }
            });
    }

    loadActiveSessions();
    setInterval(loadActiveSessions, 8000);

    if (!createRoomModal) return; // student pages have no modal, stop here

    function openCreateRoomModal() {
        createRoomStep.style.display = 'block';
        settingsFormatStep.style.display = 'none';
        createRoomModal.classList.add('open');
    }

    function closeCreateRoomModal() {
        createRoomModal.classList.remove('open');
        stopCountdown();
        stopLocalStream();
    }

    createRoomModal.addEventListener('click', function (e) {
        if (e.target === createRoomModal) closeCreateRoomModal();
    });

    // Tignan muna kung meron talagang camera/mic sa device BAGO humingi ng
    // permission — para hindi na lumabas 'yung blocking browser error sa mga
    // PC na walang webcam/mic (gaya nito).
    async function detectDevices() {
        let hasCam = true, hasMic = true;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            hasCam = devices.some(d => d.kind === 'videoinput');
            hasMic = devices.some(d => d.kind === 'audioinput');
        } catch (err) {
            hasCam = false;
            hasMic = false;
        }
        if (!hasCam) mediaState.camera = false;
        if (!hasMic) mediaState.mic = false;

        const camBtn = document.getElementById('toggleCamera');
        const micBtn = document.getElementById('toggleMic');
        if (camBtn) { camBtn.disabled = !hasCam; camBtn.title = hasCam ? '' : 'No camera detected on this device'; }
        if (micBtn) { micBtn.disabled = !hasMic; micBtn.title = hasMic ? '' : 'No microphone detected on this device'; }
    }

    // ===== STEP 1 -> STEP 2 =====
    goLiveBtn.addEventListener('click', async function () {
        createRoomStep.style.display = 'none';
        settingsFormatStep.style.display = 'flex';
        await detectDevices();
        await startLocalStream();
        // Apply initial ON/OFF state to the side icons (reflects real hardware availability)
        Object.keys(mediaState).forEach(media => updateToggleUI(media, mediaState[media]));
        startCountdown();
    });

    // ===== Camera / Mic / Screen preview =====
    async function startLocalStream() {
        if (!mediaState.camera && !mediaState.mic) {
            // Walang camera/mic na hihingin — screen share pa rin gagana nang maayos.
            return;
        }
        try {
            localStream = await navigator.mediaDevices.getUserMedia({
                video: mediaState.camera,
                audio: mediaState.mic
            });
            formatPreviewVideo.srcObject = localStream;
        } catch (err) {
            console.error('Could not access camera/mic:', err);
            // Huwag na i-block ng alert() ang user — magpatuloy lang na walang
            // camera/mic. Screen share pa rin ang gagana.
            mediaState.camera = false;
            mediaState.mic = false;
        }
    }

    function stopLocalStream() {
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
        }
        stopScreenPreview();
    }

    // ===== Toggle buttons with 40s cooldown per button =====
    document.querySelectorAll('.settings-toggle-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const media = btn.dataset.media;
            if (toggleCooldowns[media]) return;

            if (media === 'screen') {
                if (!mediaState.screen) {
                    try {
                        screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                        formatPreviewVideo.srcObject = screenStream;
                        screenStream.getVideoTracks()[0].onended = function () {
                            mediaState.screen = false;
                            updateToggleUI('screen', false);
                            formatPreviewVideo.srcObject = localStream;
                        };
                    } catch (err) {
                        console.error('Screen preview cancelled:', err);
                        return; // user cancelled the picker — don't flip the toggle
                    }
                } else {
                    stopScreenPreview();
                    formatPreviewVideo.srcObject = localStream;
                }
            }

            mediaState[media] = !mediaState[media];
            updateToggleUI(media, mediaState[media]);
            applyMediaStateToStream(media);

            toggleCooldowns[media] = true;
            btn.disabled = true;
            setTimeout(() => {
                toggleCooldowns[media] = false;
                btn.disabled = false;
            }, COOLDOWN_MS);
        });
    });

    function stopScreenPreview() {
        if (screenStream) {
            screenStream.getTracks().forEach(t => t.stop());
            screenStream = null;
        }
    }

    function updateToggleUI(media, isOn) {
        const btn = document.getElementById('toggle' + media.charAt(0).toUpperCase() + media.slice(1));
        const label = document.getElementById(media + 'StateLabel');
        if (btn && label) {
            btn.classList.toggle('state-on', isOn);
            btn.classList.toggle('state-off', !isOn);
            label.textContent = isOn ? 'On' : 'Off';
        }

        // Format side icon: hidden entirely when that media is OFF
        const formatIcon = document.getElementById('format' + media.charAt(0).toUpperCase() + media.slice(1) + 'Icon');
        if (formatIcon) {
            formatIcon.classList.toggle('hidden', !isOn);
        }

        // Camera PIP thumbnail only shows while Screen Share is ON
        if (media === 'screen') {
            const formatPip = document.getElementById('formatPip');
            const formatPipVideo = document.getElementById('formatPipVideo');
            if (formatPip) {
                formatPip.style.display = isOn ? 'block' : 'none';
                if (isOn && localStream && formatPipVideo) {
                    formatPipVideo.srcObject = localStream;
                }
            }
        }
    }

    function applyMediaStateToStream(media) {
        if (!localStream) return;
        if (media === 'camera') {
            localStream.getVideoTracks().forEach(t => t.enabled = mediaState.camera);
        } else if (media === 'mic') {
            localStream.getAudioTracks().forEach(t => t.enabled = mediaState.mic);
        }
        // 'screen' is handled inside the actual live_room.php (Step 16D), not in this preview
    }

    // ===== 40s auto-Go countdown =====
    function startCountdown() {
        countdownValue = 40;
        settingsCountdown.textContent = countdownValue + 's';
        countdownTimer = setInterval(() => {
            countdownValue--;
            settingsCountdown.textContent = countdownValue + 's';
            if (countdownValue <= 0) {
                stopCountdown();
                goLiveFinal();
            }
        }, 1000);
    }

    function stopCountdown() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    finalGoBtn.addEventListener('click', function () {
        stopCountdown();
        goLiveFinal();
    });

    // ===== Actually create the room and go to live_room.php =====
    function goLiveFinal() {
        const courseId = document.getElementById('roomCourse').value;
        const yearLevel = document.getElementById('roomYear').value;
        const section = document.getElementById('roomSection').value.trim();

        const formData = new URLSearchParams();
        if (courseId) formData.append('course_id', courseId);
        if (yearLevel) formData.append('year_level', yearLevel);
        if (section) formData.append('section_label', section);

        fetch('../api/start_live.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('Failed to start live session.');
                    return;
                }
                // Pass initial media state to live_room.php via sessionStorage
                sessionStorage.setItem('liveMediaState', JSON.stringify(mediaState));
                stopLocalStream();
                window.location.href = 'live_room.php?room=' + encodeURIComponent(data.room_id) + '&host=1';
            });
    }
});