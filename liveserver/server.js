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
const rooms = {};

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);

    // Professor starts a room
    socket.on('professor-start-room', (roomId) => {
        socket.join(roomId);
        rooms[roomId] = { professorSocketId: socket.id, students: new Set() };
        console.log(`Professor started room: ${roomId}`);
    });

    // Student joins a room
    socket.on('student-join-room', (roomId) => {
        if (!rooms[roomId]) {
            socket.emit('room-not-found');
            return;
        }
        socket.join(roomId);
        rooms[roomId].students.add(socket.id);

        // Tell the professor a new student joined (so professor can create a peer connection to them)
        io.to(rooms[roomId].professorSocketId).emit('student-joined', socket.id);

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

    // Professor ends the live session
    socket.on('professor-end-room', (roomId) => {
        io.to(roomId).emit('room-ended');
        delete rooms[roomId];
    });

    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
        // Clean up: remove this socket from any room it was in
        for (const roomId in rooms) {
            const room = rooms[roomId];
            if (room.professorSocketId === socket.id) {
                io.to(roomId).emit('room-ended');
                delete rooms[roomId];
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