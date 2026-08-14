document.addEventListener('DOMContentLoaded', function () {
    const upcomingCard = document.getElementById('upcomingCard');
    const upcomingList = document.getElementById('upcomingList');
    const liveNowCard = document.getElementById('liveNowCard');
    if (!upcomingCard && !liveNowCard) return; // Only exists on student dashboard

    const TYPE_ICON = { class: '📚', live: '🎬', exam: '📝', event: '📌' };
    const TYPE_COLOR = { class: '#4a7dff', live: '#ff4757', exam: '#ffa726', event: '#8b5cf6' };

    function formatEventDate(dateStr, timeStr) {
        const eventDate = new Date(dateStr + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);

        let dayLabel;
        if (eventDate.getTime() === today.getTime()) {
            dayLabel = 'Today';
        } else if (eventDate.getTime() === tomorrow.getTime()) {
            dayLabel = 'Tomorrow';
        } else {
            dayLabel = eventDate.toLocaleDateString('en-US', { weekday: 'long' });
        }

        let timeLabel = '';
        if (timeStr) {
            const [h, m] = timeStr.split(':');
            const hour = parseInt(h, 10);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 === 0 ? 12 : hour % 12;
            timeLabel = ' • ' + hour12 + ':' + m + ' ' + ampm;
        }

        return dayLabel + timeLabel;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function loadUpcoming() {
        if (!upcomingCard || !upcomingList) return;

        fetch('../api/upcoming_events.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.events.length === 0) {
                    upcomingCard.style.display = 'none';
                    upcomingList.innerHTML = '';
                    return;
                }

                upcomingList.innerHTML = data.events.map(ev => `
                    <div class="upcoming-item">
                        <div class="upcoming-item-title">
                            <span class="upcoming-item-icon">${TYPE_ICON[ev.event_type] || '📌'}</span>
                            ${escapeHtml(ev.title)}
                        </div>
                        <div class="upcoming-item-bar" style="background-color:${TYPE_COLOR[ev.event_type] || '#8b5cf6'}"></div>
                        <div class="upcoming-item-time">${formatEventDate(ev.event_date, ev.event_time)}</div>
                    </div>
                `).join('');
                upcomingCard.style.display = '';
            })
            .catch(() => {
                upcomingCard.style.display = 'none';
            });
    }

    function loadLiveNow() {
        if (!liveNowCard) return;

        fetch('../api/active_live_sessions.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.sessions.length === 0) {
                    liveNowCard.style.display = 'none';
                    return;
                }
                liveNowCard.style.display = 'flex';
            })
            .catch(() => {
                liveNowCard.style.display = 'none';
            });
    }

    loadUpcoming();
    loadLiveNow();

    // Re-check every 30s so the LIVE NOW card appears/disappears automatically
    // if a professor starts or ends a live class while the student has the dashboard open.
    setInterval(loadLiveNow, 30000);
});