(function () {
    // ---- Dark / Light mode ----
    const root = document.documentElement;
    const stored = localStorage.getItem('mbg_theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initial = stored || (prefersDark ? 'dark' : 'light');
    root.setAttribute('data-theme', initial);

    function updateToggleIcon(theme) {
        const btn = document.getElementById('themeToggle');
        if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
    }
    updateToggleIcon(initial);

    window.mbgToggleTheme = function () {
        const current = root.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        localStorage.setItem('mbg_theme', next);
        updateToggleIcon(next);
    };

    // ---- Mobile nav toggle ----
    window.mbgToggleNav = function () {
        const nav = document.getElementById('navLinks');
        if (nav) nav.classList.toggle('open');
    };

    // ---- Simple fetch helper for pages doing client-side calls (optional) ----
    window.mbgFlash = function (msg, type) {
        const box = document.getElementById('flashBox');
        if (!box) return;
        box.innerHTML = '<div class="alert ' + (type || 'success') + '">' + msg + '</div>';
    };

    // ---- Status bar (bawah layar): instance FE, FE->BE, FE->EFS, health BE ----
    // Di-poll berkala lewat fetch() ke status.php karena FE di-autoscale;
    // bar ini membantu tahu instance FE mana yang sedang melayani, dan
    // apakah koneksi ke BE/EFS + kesehatan BE sedang baik-baik saja.
    const STATUS_POLL_INTERVAL_MS = 20000;

    function setDot(id, status) {
        const el = document.getElementById(id);
        if (!el) return;
        el.className = 'status-dot status-dot--' + (
            status === 'connected' || status === 'ok' ? 'ok' :
            status === 'not_configured' || status === 'unknown' ? 'warn' :
            'error'
        );
    }

    function labelFor(status) {
        const map = {
            connected: 'Terhubung',
            disconnected: 'Terputus',
            not_configured: 'Belum dikonfigurasi',
            unknown: 'Tidak diketahui',
            unreachable: 'Tidak terjangkau',
            ok: 'Sehat',
            error: 'Bermasalah',
        };
        return map[status] || status || '-';
    }

    async function refreshStatusBar(url) {
        try {
            const res = await fetch(url, { cache: 'no-store' });
            const data = await res.json();

            const hostnameEl = document.getElementById('sbHostname');
            const ipEl = document.getElementById('sbServerIp');
            if (hostnameEl) hostnameEl.textContent = data.instance?.hostname || '-';
            if (ipEl) ipEl.textContent = data.instance?.server_ip || '-';

            const feToBe = data.fe_to_be || {};
            setDot('sbFeToBeDot', feToBe.status);
            const feToBeText = document.getElementById('sbFeToBeText');
            if (feToBeText) {
                feToBeText.textContent = labelFor(feToBe.status) +
                    (feToBe.latency_ms != null ? ' (' + feToBe.latency_ms + 'ms)' : '');
            }

            const feToEfs = data.fe_to_efs || {};
            setDot('sbFeToEfsDot', feToEfs.status);
            const feToEfsText = document.getElementById('sbFeToEfsText');
            if (feToEfsText) feToEfsText.textContent = labelFor(feToEfs.status);

            const beHealth = data.be_health || {};
            // BE /health melaporkan 'status': 'ok'/'error' untuk keseluruhan,
            // plus rincian db/s3/sns — tampilkan ringkas di bar, detail penuh
            // ada di title (tooltip) supaya bar tidak terlalu ramai.
            setDot('sbBeHealthDot', beHealth.status);
            const beHealthText = document.getElementById('sbBeHealthText');
            if (beHealthText) {
                const parts = [];
                if (beHealth.db) parts.push('DB ' + labelFor(beHealth.db));
                if (beHealth.s3 && beHealth.s3.status) parts.push('S3 ' + labelFor(beHealth.s3.status));
                if (beHealth.sns && beHealth.sns.status) parts.push('SNS ' + labelFor(beHealth.sns.status));
                beHealthText.textContent = parts.length ? parts.join(', ') : labelFor(beHealth.status);
            }

            const updatedEl = document.getElementById('sbUpdatedAt');
            if (updatedEl) {
                const t = data.checked_at ? new Date(data.checked_at) : new Date();
                updatedEl.textContent = 'Diperbarui ' + t.toLocaleTimeString('id-ID');
            }
        } catch (e) {
            ['sbFeToBeDot', 'sbFeToEfsDot', 'sbBeHealthDot'].forEach(id => setDot(id, 'disconnected'));
            const updatedEl = document.getElementById('sbUpdatedAt');
            if (updatedEl) updatedEl.textContent = 'Gagal memuat status';
        }
    }

    window.mbgInitStatusBar = function (statusUrl) {
        refreshStatusBar(statusUrl);
        setInterval(() => refreshStatusBar(statusUrl), STATUS_POLL_INTERVAL_MS);
    };
})();
