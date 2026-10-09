<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Live Machine Board — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
     <a href="index.php" class="btn btn-secondary" style="margin-top:10px;">← Back</a>
    <style>
        body {
            background: radial-gradient(circle at 20% 10%, #1e3a5f 0%, #0a1128 45%, #050818 100%);
            min-height: 100vh;
        }

        .wrap {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 30px auto 60px;
            padding: 0 20px;
        }

        /* ================= HEADER ================= */
        .head {
            text-align: center;
            margin-bottom: 26px;
        }
        .head h1 {
            color: var(--accent);
            font-size: 32px;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-shadow: 0 0 24px rgba(76,201,240,0.5);
        }
        .head p {
            color: var(--text-dim);
            font-size: 13px;
            margin-top: 6px;
        }

        .live-dot {
            display: inline-block;
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #2a9d8f;
            box-shadow: 0 0 12px #2a9d8f;
            animation: pulseDot 1.5s infinite;
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.4; transform: scale(1.3); }
        }

        /* ================= SUMMARY ================= */
        .summary {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }
        .summary .chip {
            background: rgba(27,38,59,0.7);
            border: 1px solid rgba(76,201,240,0.3);
            border-radius: 30px;
            padding: 8px 18px;
            font-size: 13px;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }
        .summary .chip b { color: var(--accent); font-size: 16px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot-green  { background: #2a9d8f; box-shadow: 0 0 10px #2a9d8f; }
        .dot-orange { background: #e76f51; box-shadow: 0 0 10px #e76f51; }
        .dot-gray   { background: #6c757d; box-shadow: 0 0 10px #6c757d; }

        /* ================= GRID ================= */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 18px;
            margin-top: 26px;
        }

        /* ================= MACHINE CARD ================= */
        .m {
            background: rgba(27,38,59,0.8);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(76,201,240,0.25);
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.35);
            transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s;
            position: relative;
            overflow: hidden;
            animation: cardIn 0.5s cubic-bezier(.2,.8,.3,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .m:hover {
            transform: translateY(-4px);
            border-color: rgba(76,201,240,0.6);
            box-shadow: 0 18px 44px rgba(76,201,240,0.25);
        }

        /* Left accent bar */
        .m::before {
            content: '';
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--accent), var(--accent-2));
            transition: background 0.4s;
        }
        .m.running::before    { background: linear-gradient(180deg, #e76f51, #c95a3c); }
        .m.available::before  { background: linear-gradient(180deg, #2a9d8f, #21867a); }
        .m.offline::before,
        .m.maintenance::before { background: linear-gradient(180deg, #6c757d, #4d545b); }

        .m.running    { border-color: rgba(231,111,81,0.5); }
        .m.available  { border-color: rgba(42,157,143,0.5); }

        /* Head */
        .head-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            gap: 10px;
        }
        .title { display: flex; align-items: center; gap: 10px; }
        .icon { font-size: 26px; }
        .code {
            font-size: 18px;
            font-weight: 900;
            color: var(--accent);
            letter-spacing: 1px;
        }
        .name { font-size: 12px; color: var(--text-dim); }

        /* Status badge */
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .badge.available   { background: #2a9d8f; color: #fff; }
        .badge.in_use      { background: #e76f51; color: #fff; }
        .badge.maintenance { background: #f4a261; color: #fff; }
        .badge.offline     { background: #6c757d; color: #fff; }

        /* Location */
        .loc {
            font-size: 12px;
            color: var(--text-dim);
            margin-bottom: 12px;
        }

        /* ============ BIG COUNTDOWN ============ */
        .time {
            font-size: 42px;
            font-weight: 900;
            color: var(--accent-3);
            text-shadow: 0 0 24px rgba(249,199,79,0.55);
            letter-spacing: 3px;
            font-variant-numeric: tabular-nums;
            text-align: center;
            margin: 6px 0 4px;
            transition: color 0.4s, text-shadow 0.4s;
        }
        .time.done   { color: #2a9d8f; text-shadow: 0 0 24px rgba(42,157,143,0.6); }
        .time.warn   { color: #f4a261; text-shadow: 0 0 26px rgba(244,162,97,0.7); }
        .time.danger {
            color: #e76f51;
            text-shadow: 0 0 26px rgba(231,111,81,0.8);
            animation: dangerPulse 1s ease-in-out infinite;
        }
        @keyframes dangerPulse {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.06); }
        }

        .label {
            text-align: center;
            font-size: 11px;
            color: var(--text-dim);
            letter-spacing: 3px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        /* Progress bar */
        .progress {
            width: 100%;
            height: 10px;
            background: rgba(10,17,40,0.6);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(76,201,240,0.2);
            margin-bottom: 12px;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4cc9f0, #7b2cbf, #f9c74f);
            background-size: 200% 100%;
            animation: shimmer 2s linear infinite;
            transition: width 1s linear;
            border-radius: 10px;
        }
        @keyframes shimmer {
            0%   { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        /* Meta */
        .meta {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--text-dim);
            margin-top: 8px;
        }

        /* ============ AVAILABLE-AT BADGE ============ */
        .avail {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 14px;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .avail.running {
            background: rgba(76,201,240,0.12);
            border: 1px solid rgba(76,201,240,0.35);
            color: #bfe9ff;
        }
        .avail.running .avail-time {
            color: var(--accent-3);
            font-weight: 900;
            font-size: 16px;
            letter-spacing: 1px;
            text-shadow: 0 0 12px rgba(249,199,79,0.55);
            font-variant-numeric: tabular-nums;
        }
        .avail.running.danger {
            background: rgba(231,111,81,0.15);
            border-color: rgba(231,111,81,0.6);
            color: #ffb3a1;
            animation: availPulse 1s ease-in-out infinite;
        }
        .avail.running.danger .avail-time {
            color: #ff8a68;
            text-shadow: 0 0 14px rgba(231,111,81,0.7);
        }
        @keyframes availPulse {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.03); }
        }
        .avail.now {
            background: rgba(42,157,143,0.15);
            border: 1px solid rgba(42,157,143,0.5);
            color: #7fe3d6;
        }
        .avail.offline {
            background: rgba(108,117,125,0.15);
            border: 1px solid rgba(108,117,125,0.4);
            color: #c8d4e0;
        }

        .avail-rel {
            display: block;
            text-align: center;
            font-size: 11px;
            color: var(--text-dim);
            letter-spacing: 1px;
            margin-top: 6px;
            text-transform: uppercase;
            font-variant-numeric: tabular-nums;
        }

        /* Available card CTA */
        .price {
            text-align: center;
            font-size: 22px;
            font-weight: 900;
            color: var(--accent-3);
            margin: 10px 0;
            text-shadow: 0 0 20px rgba(249,199,79,0.4);
        }
        .price small {
            font-size: 12px;
            color: var(--text-dim);
            font-weight: 400;
        }
        .cta {
            display: block;
            margin-top: 12px;
            padding: 11px;
            text-align: center;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            border-radius: 10px;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            font-size: 13px;
            box-shadow: 0 6px 20px rgba(76,201,240,0.4);
            transition: transform 0.2s;
        }
        .cta:hover { transform: translateY(-2px); }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: var(--text-dim);
            opacity: 0.7;
        }

        /* Empty state */
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-dim);
            grid-column: 1 / -1;
        }
        .empty span { font-size: 60px; display: block; margin-bottom: 12px; }

        @media (max-width: 480px) {
            .head h1 { font-size: 22px; }
            .time    { font-size: 34px; letter-spacing: 2px; }
        }
    </style>
</head>
<body>
    <?php include 'assets.php'; ?>

    <div class="wrap">
        <!-- HEADER -->
        <div class="head">
            <h1><span class="live-dot"></span> Live Machine Board</h1>
            <p>Everyone can see exactly when each machine will be free</p>

            <div class="summary">
                <div class="chip"><span class="dot dot-green"></span> Available: <b id="c-avail">0</b></div>
                <div class="chip"><span class="dot dot-orange"></span> Running: <b id="c-run">0</b></div>
                <div class="chip"><span class="dot dot-gray"></span> Offline: <b id="c-off">0</b></div>
                <div class="chip">Total: <b id="c-total">0</b></div>
            </div>
        </div>

        <!-- GRID -->
        <div class="grid" id="grid">
            <div class="empty"><span>⏳</span><p>Loading machines…</p></div>
        </div>

        <div class="footer" id="footer">Connecting…</div>
    </div>

<script>
/* ============================================================
   BOARD SCRIPT
   ============================================================ */

const API = 'api_realtime.php';
const POLL_MS = 3000;   // server sync every 3 seconds
const TICK_MS = 250;    // local countdown smoothness

let machines      = [];
let serverOffset  = 0;   // server_ts - client_time (in seconds)

const CURRENCY_SYMBOL = <?= json_encode(CURRENCY) ?>;

/* ---------- Safely convert to number or null ---------- */
function num(v) {
    if (v === null || v === undefined || v === '') return null;
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
}

/* ---------- Time formatters ---------- */
function fmtCountdown(seconds) {
    if (!Number.isFinite(seconds)) return '--:--';
    seconds = Math.max(0, Math.floor(seconds));
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

function fmtClock(epochSec) {
    const e = num(epochSec);
    if (e === null) return '—';
    const d = new Date(e * 1000);
    const now = new Date();
    const isTomorrow = d.getDate() !== now.getDate();
    const hh = String(d.getHours()).padStart(2,'0');
    const mm = String(d.getMinutes()).padStart(2,'0');
    return (isTomorrow ? 'tomorrow ' : '') + `${hh}:${mm}`;
}

function humanShort(sec) {
    if (!Number.isFinite(sec)) return '—';
    sec = Math.max(0, Math.floor(sec));
    if (sec < 60) return sec + ' sec';
    const m = Math.floor(sec / 60);
    if (m < 60) return m + ' min';
    const h = Math.floor(m / 60);
    const r = m % 60;
    return r > 0 ? `${h} hr ${r} min` : `${h} hr`;
}

/* ---------- Compute remaining seconds for a machine ---------- */
function remaining(m) {
    if (m.status !== 'in_use') return 0;
    const end = num(m.ends_ts);
    if (end === null) return 0;
    const now = Date.now() / 1000 + serverOffset;
    return Math.max(0, end - now);
}

/* ---------- Render one card ---------- */
function card(m) {
    const icon = m.machine_type === 'dryer' ? '🌀' : '🧺';
    const rem  = remaining(m);
    const running = (m.status === 'in_use' && num(m.ends_ts) !== null);

    /* -------- RUNNING -------- */
    if (running) {
        const isDanger = rem <= 10;
        const isWarn   = rem > 10 && rem <= 60;

        let timeCls = '';
        if (isDanger) timeCls = 'danger';
        else if (isWarn) timeCls = 'warn';

        const totalSec = num(m.ends_ts) - num(m.started_ts);
        const progress = totalSec > 0
            ? Math.min(100, Math.max(0, ((totalSec - rem) / totalSec) * 100))
            : 0;

        const availText = fmtClock(m.ends_ts);

        return `<div class="m running" data-code="${m.machine_code}" data-ends="${m.ends_ts}" data-start="${m.started_ts}">
            <div class="head-row">
                <div class="title">
                    <span class="icon">${icon}</span>
                    <div>
                        <div class="code">${m.machine_code}</div>
                        <div class="name">${m.machine_name}</div>
                    </div>
                </div>
                <span class="badge in_use">IN USE</span>
            </div>
            <div class="loc">📍 ${m.location}</div>

            <div class="time ${timeCls}" data-timer>${fmtCountdown(rem)}</div>
            <div class="label">Time Remaining</div>

            <div class="progress">
                <div class="progress-bar" data-progress style="width:${progress}%"></div>
            </div>

            <div class="meta">
                <span>👤 ${m.customer}</span>
                <span>Ends ${availText}</span>
            </div>

            <div class="avail running ${isDanger ? 'danger' : ''}" data-badge>
                <span>🕐</span>
                <span>Can use again <span class="avail-time" data-availtime>${availText}</span></span>
            </div>
            <span class="avail-rel" data-rel>≈ ${humanShort(rem)} left</span>
        </div>`;
    }

    /* -------- AVAILABLE -------- */
    if (m.status === 'available') {
        return `<div class="m available" data-code="${m.machine_code}">
            <div class="head-row">
                <div class="title">
                    <span class="icon">${icon}</span>
                    <div>
                        <div class="code">${m.machine_code}</div>
                        <div class="name">${m.machine_name}</div>
                    </div>
                </div>
                <span class="badge available">AVAILABLE</span>
            </div>
            <div class="loc">📍 ${m.location}</div>

            <div class="time done">READY</div>
            <div class="label">Ready to Use</div>

            <div class="price">
                ${CURRENCY_SYMBOL} ${parseFloat(m.price).toFixed(2)}
                <small>/ ${m.cycle_minutes} min</small>
            </div>

            <div class="avail now"><span>✓</span><span>Available now</span></div>

            <a class="cta" href="machine.php?code=${encodeURIComponent(m.machine_code)}">
                Use This Machine →
            </a>
        </div>`;
    }

    /* -------- MAINTENANCE / OFFLINE -------- */
    return `<div class="m ${m.status}" data-code="${m.machine_code}">
        <div class="head-row">
            <div class="title">
                <span class="icon">${icon}</span>
                <div>
                    <div class="code">${m.machine_code}</div>
                    <div class="name">${m.machine_name}</div>
                </div>
            </div>
            <span class="badge ${m.status}">${m.status.toUpperCase()}</span>
        </div>
        <div class="loc">📍 ${m.location}</div>
        <div class="time" style="opacity:.4;">—</div>
        <div class="label">Not Available</div>
        <div class="avail offline">
            <span>🚫</span>
            <span>${m.status === 'maintenance' ? 'Under maintenance' : 'Offline'}</span>
        </div>
    </div>`;
}

/* ---------- Render the whole grid ---------- */
function render() {
    const grid = document.getElementById('grid');
    if (!machines.length) {
        grid.innerHTML = `<div class="empty"><span>🚫</span><p>No machines configured.</p></div>`;
        updateSummary();
        return;
    }
    grid.innerHTML = machines.map(card).join('');
    updateSummary();
}

/* ---------- Update summary chips ---------- */
function updateSummary() {
    let avail = 0, run = 0, off = 0;
    machines.forEach(m => {
        if (m.status === 'available') avail++;
        else if (m.status === 'in_use') run++;
        else off++;
    });
    document.getElementById('c-avail').textContent = avail;
    document.getElementById('c-run').textContent   = run;
    document.getElementById('c-off').textContent   = off;
    document.getElementById('c-total').textContent = machines.length;
}

/* ---------- Local tick: update countdown every 250ms ---------- */
function tick() {
    const now = Date.now() / 1000 + serverOffset;

    document.querySelectorAll('.m[data-ends]').forEach(el => {
        const end = num(el.dataset.ends);
        if (end === null) return;

        const rem = Math.max(0, end - now);
        const timer = el.querySelector('[data-timer]');
        if (!timer) return;

        timer.textContent = fmtCountdown(rem);

        // Color zones
        timer.classList.remove('warn', 'danger');
        if (rem <= 10)      timer.classList.add('danger');
        else if (rem <= 60) timer.classList.add('warn');

        // Badge danger
        const badge = el.querySelector('[data-badge]');
        if (badge) badge.classList.toggle('danger', rem <= 10);

        // Relative text
        const rel = el.querySelector('[data-rel]');
        if (rel) rel.textContent = '≈ ' + humanShort(rem) + ' left';

        // Progress bar
        const start = num(el.dataset.start);
        const bar = el.querySelector('[data-progress]');
        if (bar && start !== null && end > start) {
            const pct = Math.min(100, Math.max(0, ((now - start) / (end - start)) * 100));
            bar.style.width = pct.toFixed(1) + '%';
        }

        // When it hits zero — swap to available via refresh
        if (rem <= 0) {
            timer.textContent = 'DONE';
            timer.classList.remove('warn', 'danger');
            timer.classList.add('done');
            setTimeout(refresh, 800);
        }
    });
}

/* ---------- Fetch server state ---------- */
async function refresh() {
    try {
        const r = await fetch(API + '?force=1&t=' + Date.now());
        const d = await r.json();

        // Sync clock
        const srv = num(d.server_ts);
        if (srv !== null) serverOffset = srv - Date.now() / 1000;

        machines = Array.isArray(d.machines) ? d.machines : [];

        render();
        document.getElementById('footer').textContent =
            'Updated ' + new Date().toLocaleTimeString() + ' — updates every 3 s';
    } catch (e) {
        console.error(e);
        document.getElementById('footer').textContent =
            '⚠ Connection error — retrying…';
    }
}

/* ---------- Boot ---------- */
refresh();
setInterval(refresh, POLL_MS);
setInterval(tick, TICK_MS);
</script>
</body>
</html>