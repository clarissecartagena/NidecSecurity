<style>
    /* ── Notifications Page ── */
    .notif-page-wrap {
        --notif-surface: hsl(var(--card));
        --notif-border: hsl(var(--border));
        --notif-muted: hsl(var(--muted));
    }

    /* Mark all read button */
    #notifications-page-mark-all {
        background: hsl(var(--primary) / 0.08);
        border: 1px solid hsl(var(--primary) / 0.28);
        color: hsl(var(--primary));
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 10px;
        padding: 0.48rem 0.95rem;
        display: flex; align-items: center; gap: 6px;
        transition: all 0.18s ease;
        cursor: pointer;
        align-self: flex-end;
    }
    #notifications-page-mark-all:hover {
        border-color: hsl(var(--primary));
        color: hsl(var(--primary));
        background: hsl(var(--primary) / 0.14);
        transform: translateY(-1px);
    }
    /* Tab bar */
    .notif-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 1px solid hsl(var(--border));
        margin-bottom: 0;
        padding: 0 0 10px;
        overflow-x: auto;
        scrollbar-width: thin;
    }
    .notif-tab {
        background: hsl(var(--muted) / 0.4);
        border: 1px solid transparent;
        padding: 0.5rem 0.95rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: hsl(var(--muted-foreground));
        cursor: pointer;
        border-bottom: 1px solid transparent;
        border-radius: 999px;
        transition: all 0.15s ease;
        display: flex; align-items: center; gap: 6px;
        white-space: nowrap;
    }
    .notif-tab:hover {
        color: hsl(var(--foreground));
        background: hsl(var(--muted) / 0.62);
    }
    .notif-tab.active {
        color: hsl(var(--primary));
        border-color: hsl(var(--primary) / 0.25);
        background: hsl(var(--primary) / 0.1);
        box-shadow: inset 0 0 0 1px hsl(var(--primary) / 0.08);
    }
    .notif-tab .notif-badge {
        background: hsl(var(--destructive));
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        border-radius: 20px;
        padding: 1px 7px;
        min-width: 20px;
        text-align: center;
        display: none;
    }
    .notif-tab .notif-badge.visible { display: inline-block; }

    /* Card container */
    .notif-card-wrap {
        background: var(--notif-surface);
        border: 1px solid var(--notif-border);
        border-radius: 14px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 10px 24px hsl(var(--foreground) / 0.05);
    }
    .notif-card-wrap::before {
        content: '';
        position: absolute;
        left: 0; right: 0; top: 0;
        height: 4px;
        background-image: linear-gradient(90deg,
            hsl(var(--primary)) 0%,
            hsl(var(--primary) / 0.35) 55%,
            transparent 100%
        );
        pointer-events: none;
        z-index: 1;
    }

    /* Individual notification item */
    .notif-item {
        display: flex;
        align-items: flex-start;
        gap: 0.9rem;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid hsl(var(--border));
        border-left: 3px solid transparent;
        text-decoration: none !important;
        color: inherit !important;
        background: hsl(var(--card));
        transition: background 0.15s ease, transform 0.12s ease;
        cursor: pointer;
        width: 100%;
        text-align: left;
        border-top: none;
        border-right: none;
        border-radius: 0;
    }
    .notif-item:last-child { border-bottom: none; }
    .notif-item:hover {
        background: hsl(var(--muted) / 0.52);
        transform: translateX(1px);
    }
    .notif-item.unread {
        border-left-color: hsl(var(--primary));
        background: hsl(var(--primary) / 0.045);
    }
    .notif-item.unread:hover { background: hsl(var(--primary) / 0.07); }

    /* Icon circle */
    .notif-icon {
        width: 40px; height: 40px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        margin-top: 1px;
        border: 1px solid hsl(var(--border) / 0.65);
    }
    .notif-icon-blue   { background: hsl(var(--info) / 0.12);        color: hsl(var(--info));        }
    .notif-icon-green  { background: hsl(var(--success) / 0.12);     color: hsl(var(--success));     }
    .notif-icon-amber  { background: hsl(var(--warning) / 0.12);     color: hsl(var(--warning));     }
    .notif-icon-red    { background: hsl(var(--destructive) / 0.12); color: hsl(var(--destructive)); }
    .notif-icon-purple { background: hsl(270 60% 55% / 0.12);        color: hsl(270 60% 50%);        }
    .notif-icon-cyan   { background: hsl(var(--info) / 0.12);        color: hsl(var(--info));        }
    .notif-icon-slate  { background: hsl(var(--muted));               color: hsl(var(--muted-foreground)); }

    /* Content */
    .notif-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: hsl(var(--foreground));
        margin-bottom: 3px;
        line-height: 1.3;
    }
    .notif-report-pill {
        display: inline-block;
        background: hsl(var(--primary) / 0.10);
        color: hsl(var(--primary));
        font-size: 0.7rem;
        font-weight: 600;
        border-radius: 20px;
        padding: 1px 9px;
        margin-bottom: 4px;
        border: 1px solid hsl(var(--primary) / 0.2);
    }
    .notif-msg {
        font-size: 0.835rem;
        color: hsl(var(--muted-foreground));
        line-height: 1.45;
    }

    /* Timestamp + dot */
    .notif-meta {
        flex-shrink: 0;
        text-align: right;
        min-width: 122px;
    }
    .notif-time {
        font-size: 0.75rem;
        color: hsl(var(--muted-foreground));
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 5px;
        white-space: nowrap;
    }
    .notif-dot-read   { width: 7px; height: 7px; border-radius: 50%; background: transparent; border: 1.5px solid hsl(var(--border)); display: inline-block; flex-shrink: 0; }
    .notif-dot-unread { width: 7px; height: 7px; border-radius: 50%; background: hsl(var(--primary)); display: inline-block; flex-shrink: 0; }

    @media (max-width: 768px) {
        .notif-item {
            flex-wrap: wrap;
            padding: 0.9rem 0.9rem;
        }

        .notif-meta {
            min-width: 100%;
            text-align: left;
            padding-left: calc(40px + 0.9rem);
            margin-top: -2px;
        }

        .notif-time {
            justify-content: flex-start;
        }
    }

    /* Empty state */
    .notif-empty {
        padding: 3rem 1rem;
        text-align: center;
        color: hsl(var(--muted-foreground));
        font-size: 0.875rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    .notif-empty i { font-size: 2.25rem; color: hsl(var(--border)); }

    /* Loading skeleton */
    .notif-skeleton {
        padding: 1rem 1.1rem;
        border-bottom: 1px solid hsl(var(--border));
        display: flex; gap: 0.75rem; align-items: flex-start;
    }
    .sk {
        background: linear-gradient(90deg, hsl(var(--muted)) 25%, hsl(var(--border)) 50%, hsl(var(--muted)) 75%);
        background-size: 400% 100%;
        animation: sk-shimmer 1.4s infinite;
        border-radius: 6px;
    }
    @keyframes sk-shimmer { 0%{background-position:100% 50%} 100%{background-position:0% 50%} }
    .sk-circle { width:40px; height:40px; border-radius:50%; flex-shrink:0; }
    .sk-line { height:12px; margin-bottom:6px; }
</style>

<main class="main-content">
    <div class="animate-fade-in notif-page-wrap">

        <!-- Header -->
        <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-bell-fill me-2 text-primary"></i>Notifications</h1>
                <p class="text-sm text-muted-foreground mb-0" id="notif-subtitle">View and manage all your notifications.</p>
            </div>
            <button id="notifications-page-mark-all" type="button">
                <i class="bi bi-check2-all"></i> Mark all read
            </button>
        </div>

        <!-- Card -->
        <div class="notif-card-wrap">
            <!-- Tab bar -->
            <div class="px-3 pt-3 pb-0">
                <div class="notif-tabs" id="notif-tabs" role="tablist">
                    <button class="notif-tab active" data-tab="all" role="tab">All</button>
                    <button class="notif-tab" data-tab="unread" role="tab">
                        Unread
                        <span class="notif-badge" id="notif-unread-badge">0</span>
                    </button>
                    <button class="notif-tab" data-tab="read" role="tab">Read</button>
                </div>
            </div>

            <!-- List -->
            <div id="notifications-page-list">
                <!-- skeleton -->
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="notif-skeleton">
                    <div class="sk sk-circle"></div>
                    <div class="flex-grow-1">
                        <div class="sk sk-line" style="width:40%;"></div>
                        <div class="sk sk-line" style="width:25%; height:10px;"></div>
                        <div class="sk sk-line" style="width:80%; height:10px;"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

    </div>
</main>

<!-- hidden: preserve old filter param for controller compat -->
<input type="hidden" id="notifications-filter" value="<?php echo htmlspecialchars($filter ?? 'all'); ?>"><?php
/* kept for JS compat */
?>

<script>
(() => {
    const listEl   = document.getElementById('notifications-page-list');
    const markAllBtn = document.getElementById('notifications-page-mark-all');
    const subtitle   = document.getElementById('notif-subtitle');
    const badge      = document.getElementById('notif-unread-badge');
    const tabs       = document.getElementById('notif-tabs');

    if (!listEl) return;

    /* ── state ── */
    let allItems   = [];
    let activeTab  = 'all';
    let unreadCnt  = 0;

    /* ── helpers ── */
    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }
    function escHtml(str) {
        return String(str)
            .replaceAll('&','&amp;').replaceAll('<','&lt;')
            .replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
    }
    function fmtDate(ds) {
        if (!ds) return '';
        const d = new Date(String(ds).replace(' ', 'T'));
        if (!isFinite(d)) return '';
        const yr = d.getFullYear();
        const mo = String(d.getMonth()+1).padStart(2,'0');
        const dy = String(d.getDate()).padStart(2,'0');
        const hr = d.getHours();
        const mi = String(d.getMinutes()).padStart(2,'0');
        const ampm = hr >= 12 ? 'PM' : 'AM';
        const hr12 = String((hr % 12) || 12).padStart(2,'0');
        return `${yr}-${mo}-${dy} ${hr12}:${mi} ${ampm}`;
    }

    /* derive a short title + icon class from the message text */
    function classify(msg) {
        const m = String(msg || '').toLowerCase();

        // Rejection / denial — check before 'returned' to avoid false match
        if (m.includes('rejected') || m.includes('denied'))
            return { title:'Report Rejected',          icon:'bi-x-circle-fill',             cls:'notif-icon-red'    };

        // Resolved / completed — check before generic keywords
        if (m.includes('fully resolved') || m.includes('completed'))
            return { title:'Report Resolved',           icon:'bi-patch-check-fill',          cls:'notif-icon-green'  };
        if (m.includes('not resolved'))
            return { title:'Issue Not Resolved',        icon:'bi-exclamation-circle-fill',   cls:'notif-icon-red'    };
        if (m.includes('resolved'))
            return { title:'Report Resolved',           icon:'bi-patch-check-fill',          cls:'notif-icon-green'  };

        // Timeline events — distinguish reached/due-soon from set
        if (m.includes('timeline reached') || m.includes('auto-escalated'))
            return { title:'Timeline Reached',          icon:'bi-alarm-fill',                cls:'notif-icon-red'    };
        if (m.includes('due soon') || m.includes('within 24'))
            return { title:'Due Date Reminder',         icon:'bi-clock-history',             cls:'notif-icon-amber'  };
        if (m.includes('timeline'))
            return { title:'Timeline Set',              icon:'bi-clock-fill',                cls:'notif-icon-amber'  };

        // Department mark-done confirmation
        if (m.includes('marked report as fixed') || m.includes('marked as fixed') || m.includes('please verify'))
            return { title:'Fix Confirmed by Dept',     icon:'bi-tools',                     cls:'notif-icon-blue'   };

        // Assignment to department
        if (m.includes('assigned to your department') || m.includes('new report assigned'))
            return { title:'Assigned to Department',    icon:'bi-building',                  cls:'notif-icon-blue'   };

        // Submitted (new report)
        if (m.includes('submitted'))
            return { title:'New Report Submitted',      icon:'bi-send-fill',                 cls:'notif-icon-blue'   };

        // Awaiting approval (not security final check)
        if (m.includes('waiting for final') || m.includes('waiting for.*approval') || m.includes('awaiting approval') || m.includes('final ga approval'))
            return { title:'Awaiting GA Approval',      icon:'bi-hourglass-split',           cls:'notif-icon-purple' };

        // Security final check request
        if (m.includes('final check') || m.includes('for_security_final') || m.includes('perform final'))
            return { title:'Final Check Required',      icon:'bi-search',                    cls:'notif-icon-purple' };

        // Approved / sent to department
        if (m.includes('approved') || m.includes('sent to department'))
            return { title:'Report Approved',           icon:'bi-check-circle-fill',         cls:'notif-icon-green'  };

        // Returned (actionable)
        if (m.includes('returned'))
            return { title:'Report Returned',           icon:'bi-arrow-counterclockwise',    cls:'notif-icon-amber'  };

        // Generic action required
        if (m.includes('action required') || m.includes('requires your') || m.includes('awaiting your'))
            return { title:'Action Required',           icon:'bi-exclamation-triangle-fill', cls:'notif-icon-red'    };

        // Overdue
        if (m.includes('overdue') || m.includes('past due'))
            return { title:'Overdue Alert',             icon:'bi-alarm-fill',                cls:'notif-icon-red'    };

        return { title:'Notification', icon:'bi-bell-fill', cls:'notif-icon-slate' };
    }

    /* ── render filtered list ── */
    function render() {
        let items = allItems;
        if (activeTab === 'unread') items = allItems.filter(n => Number(n.is_read||0) === 0);
        if (activeTab === 'read')   items = allItems.filter(n => Number(n.is_read||0) !== 0);

        if (items.length === 0) {
            const labels = { all:'No notifications yet.', unread:'All caught up — no unread notifications.', read:'No read notifications.' };
            listEl.innerHTML =
                `<div class="notif-empty"><i class="bi bi-bell-slash"></i>${escHtml(labels[activeTab])}</div>`;
            return;
        }

        listEl.innerHTML = items.map(n => {
            const unread  = Number(n.is_read||0) === 0;
            const { title, icon, cls } = classify(n.message);
            const reportNo = n.report_no ? String(n.report_no) : '';
            const msg      = n.message ? String(n.message) : '';
            const when     = fmtDate(n.created_at);
            const safeId   = Number(n.id||0);

            const pill = reportNo
                ? `<span class="notif-report-pill">${escHtml(reportNo)}</span>`
                : '';
            const dot  = unread
                ? '<span class="notif-dot-unread"></span>'
                : '<span class="notif-dot-read"></span>';

            return `<button class="notif-item${unread ? ' unread' : ''}"
                        data-id="${safeId}" data-report="${escHtml(reportNo)}" type="button">
                        <div class="notif-icon ${cls}"><i class="bi ${icon}"></i></div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="notif-title">${escHtml(title)}</div>
                            ${pill}
                            <div class="notif-msg">${escHtml(msg)}</div>
                        </div>
                        <div class="notif-meta">
                            <div class="notif-time">${dot}${escHtml(when)}</div>
                        </div>
                    </button>`;
        }).join('');

        /* click — mark read, refresh counts, then open modal if applicable */
        listEl.querySelectorAll('.notif-item').forEach(el => {
            el.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = Number(el.getAttribute('data-id')||0);
                const rn = el.getAttribute('data-report')||'';
                if (id > 0) {
                    await fetch(appUrl('api/notifications.php'), {
                        method: 'POST', credentials: 'same-origin',
                        headers: {'Content-Type':'application/json','X-CSRF-Token':csrfToken()},
                        body: JSON.stringify({action:'mark_read', id}), keepalive: true
                    });
                }
                /* Re-fetch list so counts, subtitle and tab badges update immediately */
                await load();
                /* Sync topnav bell badge */
                if (typeof Notifications !== 'undefined' && Notifications.refresh) {
                    Notifications.refresh();
                }
                if (rn) {
                    if (typeof ReportModal !== 'undefined' && ReportModal.overlay) {
                        ReportModal.open(rn);
                    }
                }
            });
        });
    }

    /* ── update header subtitle + badge ── */
    function updateMeta() {
        if (subtitle) {
            subtitle.textContent = unreadCnt > 0
                ? `You have ${unreadCnt} unread notification${unreadCnt===1?'':'s'}`
                : 'All notifications are read';
        }
        if (badge) {
            badge.textContent = String(unreadCnt);
            badge.classList.toggle('visible', unreadCnt > 0);
        }
    }

    /* ── tabs ── */
    if (tabs) {
        tabs.querySelectorAll('.notif-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                tabs.querySelectorAll('.notif-tab').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeTab = btn.getAttribute('data-tab') || 'all';
                render();
            });
        });
    }

    /* ── load ── */
    async function load() {
        try {
            const res = await fetch(appUrl('api/notifications.php?limit=100&filter=all'), {
                method: 'GET', credentials: 'same-origin'
            });
            if (!res.ok) {
                listEl.innerHTML = '<div class="notif-empty"><i class="bi bi-exclamation-circle"></i>Could not load notifications. Please refresh the page.</div>';
                return;
            }
            const data = await res.json();
            allItems  = data.items || [];
            unreadCnt = Number(data.unread_count ?? 0);
            updateMeta();
            render();
        } catch (_) {
            listEl.innerHTML = '<div class="notif-empty"><i class="bi bi-exclamation-circle"></i>Could not load notifications. Please refresh the page.</div>';
        }
    }

    /* ── mark all read ── */
    if (markAllBtn) {
        markAllBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await fetch(appUrl('api/notifications.php'), {
                    method: 'POST', credentials: 'same-origin',
                    headers: {'Content-Type':'application/json','X-CSRF-Token':csrfToken()},
                    body: JSON.stringify({action:'mark_all_read'})
                });
            } catch (_) {}
            await load();
            if (typeof Notifications !== 'undefined' && Notifications?.refresh) Notifications.refresh();
        });
    }

    window.addEventListener('load', load);
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
