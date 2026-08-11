const express = require('express');
const http = require('http');
const cors = require('cors');
const { Server } = require('socket.io');

const app = express();
app.use(cors());
const server = http.createServer(app);

const io = new Server(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    }
});

// Tracks who is in which room: { roomId: { professorSocketId, students: Set } }
// professorSocketId is null while the professor has stepped away but the room
// itself is still considered "live" (resumable) — it is only ever deleted when
// the professor explicitly ends/deletes the room.
const rooms = {};

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);

    // Professor starts (or resumes) a room
    socket.on('professor-start-room', (roomId) => {
        socket.join(roomId);

        if (rooms[roomId]) {
            // Resuming a room that was left/paused earlier — keep the existing viewers.
            rooms[roomId].professorSocketId = socket.id;
            console.log(`Professor resumed room: ${roomId}`);
        } else {
            rooms[roomId] = { professorSocketId: socket.id, students: new Set() };
            console.log(`Professor started room: ${roomId}`);
        }

        // Tell the professor who is already watching, so they can (re)connect to them
        const existingStudents = Array.from(rooms[roomId].students);
        if (existingStudents.length > 0) {
            socket.emit('existing-students', existingStudents);
        }

        // Let anyone already in the room know the professor is back
        socket.to(roomId).emit('professor-back');
    });

    // Student joins a room
    socket.on('student-join-room', (roomId) => {
        if (!rooms[roomId]) {
            socket.emit('room-not-found');
            return;
        }
        socket.join(roomId);
        rooms[roomId].students.add(socket.id);

        if (rooms[roomId].professorSocketId) {
            // Tell the professor a new student joined (so professor can create a peer connection to them)
            io.to(rooms[roomId].professorSocketId).emit('student-joined', socket.id);
        } else {
            // Professor is currently away — let this student know right away instead of leaving them guessing
            socket.emit('professor-away');
        }

        // Broadcast updated viewer count to everyone in the room
        io.to(roomId).emit('viewer-count', rooms[roomId].students.size);
    });

    // WebRTC signaling relay — passes offer/answer/ICE candidates between two specific peers
    socket.on('webrtc-signal', (data) => {
        io.to(data.targetSocketId).emit('webrtc-signal', {
            senderSocketId: socket.id,
            signal: data.signal
        });
    });

    // Professor toggles camera/mic/screen — broadcast state to students for UI updates
    socket.on('media-state-changed', (data) => {
        socket.to(data.roomId).emit('media-state-changed', data);
    });

    // Live chat — relay to everyone in the room (including sender, so one code path renders it)
    socket.on('chat-message', (data) => {
        io.to(data.roomId).emit('chat-message', {
            senderName: data.senderName,
            message: data.message,
            senderSocketId: socket.id
        });
    });

    // Professor steps away but keeps the room live/resumable (does NOT end it)
    socket.on('professor-leave-room', (roomId) => {
        const room = rooms[roomId];
        if (!room || room.professorSocketId !== socket.id) return;
        room.professorSocketId = null;
        socket.to(roomId).emit('professor-away');
        socket.leave(roomId);
    });

    // Professor ends the live session for everyone (Delete Room)
    socket.on('professor-end-room', (roomId) => {
        io.to(roomId).emit('room-ended');
        delete rooms[roomId];
    });

    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
        for (const roomId in rooms) {
            const room = rooms[roomId];
            if (room.professorSocketId === socket.id) {
                // Connection dropped without an explicit Leave/Delete (closed tab, browser
                // crash, wifi drop) — pause the room instead of ending it, same as a
                // deliberate Leave, so it can be resumed from the Live page.
                room.professorSocketId = null;
                io.to(roomId).emit('professor-away');
            } else if (room.students.has(socket.id)) {
                room.students.delete(socket.id);
                io.to(roomId).emit('student-left', socket.id);
                io.to(roomId).emit('viewer-count', room.students.size);
            }
        }
    });
});

const PORT = 3001;
server.listen(PORT, () => {
    console.log(`CDSGA HUB Live Server running on http://localhost:${PORT}`);
});