/**
 * NIDEC Security System - Vanilla JavaScript
 * Converted from React
 */

// Base URL helper (supports pages in subfolders like ga_staff/*)
function appUrl(path) {
    const base = window && window.APP_BASE_URL ? String(window.APP_BASE_URL) : '';
    const p = String(path || '').replace(/^\/+/, '');
    return base + '/' + p;
}

// ============================================
// AUTHENTICATION
// ============================================
const Auth = {
    user: null,

    roleNames: {
        security: 'Security',
        ga_staff: 'General Affairs Staff',
        ga_manager: 'General Affairs Manager',
        department: 'Department',
    },

    init() {
        const savedUser = sessionStorage.getItem('nidec_user');
        if (savedUser) {
            this.user = JSON.parse(savedUser);
        }
    },

    login(username, password, role) {
        if (!username.trim()) return false;
        if (!password.trim()) return false;

        this.user = {
            username: username,
            role: role,
            displayName: this.roleNames[role],
        };

        sessionStorage.setItem('nidec_user', JSON.stringify(this.user));
        return true;
    },

    logout() {
        this.user = null;
        sessionStorage.removeItem('nidec_user');
        window.location.href = appUrl('logout.php');
    },

    isLoggedIn() {
        return this.user !== null;
    },

    getUser() {
        return this.user;
    },

    hasRole(role) {
        return this.user && this.user.role === role;
    },
};

// Make available to inline onclick handlers (e.g. topnav logout)
window.Auth = Auth;

// ============================================
// MOCK DATA
// ============================================
const Data = {
    reportCategories: [
        'Security Breach',
        'Equipment Issue',
        'Safety Violation',
        'Access Control',
        'Maintenance',
        'Surveillance',
        'Emergency Response',
        'Protocol Violation',
        'Hazardous Materials',
        'Theft / Loss',
        'Workplace Safety',
        'Other',
    ],

    departments: [
        'Manufacturing',
        'IT',
        'Facilities',
        'Human Resources',
        'Quality Assurance',
        'Engineering',
        'Administration',
    ],

    severityLevels: ['low', 'medium', 'high', 'critical'],

    reports: [
        {
            id: 'RPT-001',
            subject: 'Unauthorized Access Attempt - Server Room',
            category: 'Security Breach',
            status: 'pending',
            severity: 'critical',
            submittedBy: 'security',
            submittedAt: '2024-01-15T09:30:00Z',
            description:
                'Unknown individual attempted to access server room using stolen keycard. CCTV footage shows suspicious activity.',
            location: 'Server Room - Floor 2',
            assignedTo: 'security',
        },
        {
            id: 'RPT-002',
            subject: 'Fire Alarm Malfunction - Building A',
            category: 'Equipment Issue',
            status: 'in_progress',
            severity: 'high',
            submittedBy: 'gastaff',
            submittedAt: '2024-01-15T14:20:00Z',
            description:
                'Fire alarm system in Building A triggered false alarm. Maintenance team investigating sensor malfunction.',
            location: 'Building A - Main Entrance',
            assignedTo: 'gastaff',
        },
        {
            id: 'RPT-003',
            subject: 'Safety Protocol Violation - No PPE',
            category: 'Safety Violation',
            status: 'resolved',
            severity: 'medium',
            submittedBy: 'department',
            submittedAt: '2024-01-14T11:15:00Z',
            description:
                'Contractor observed working in restricted area without proper PPE. Safety briefing conducted and warning issued.',
            location: 'Production Area - Section B',
            assignedTo: 'department',
        },
        {
            id: 'RPT-004',
            subject: 'CCTV Camera Offline - Parking Lot',
            category: 'Equipment Issue',
            status: 'pending',
            severity: 'medium',
            submittedBy: 'security',
            submittedAt: '2024-01-15T16:45:00Z',
            description:
                'CCTV camera #3 in parking lot not responding. Requires technical inspection and possible replacement.',
            location: 'Parking Lot - North Section',
            assignedTo: 'security',
        },
        {
            id: 'RPT-005',
            subject: 'Tailgating at Main Gate',
            category: 'Security Breach',
            status: 'pending',
            severity: 'high',
            submittedBy: 'security',
            submittedAt: '2024-01-15T18:30:00Z',
            description:
                'Multiple individuals observed tailgating through main gate after hours. Security patrol dispatched.',
            location: 'Main Gate - Entrance',
            assignedTo: 'security',
        },
        {
            id: 'RPT-006',
            subject: 'Emergency Exit Blocked',
            category: 'Safety Violation',
            status: 'critical',
            severity: 'critical',
            submittedBy: 'security',
            submittedAt: '2024-01-15T20:10:00Z',
            description: 'Emergency exit in warehouse found blocked by storage boxes. Immediate clearance required.',
            location: 'Warehouse - Emergency Exit A',
            assignedTo: 'department',
        },
        {
            id: 'RPT-007',
            subject: 'Access Control System Update Required',
            category: 'Equipment Issue',
            remarks: 'Replacement scheduled for next week.',
            status: 'approved',
            submittedBy: 'security_officer_2',
            submittedAt: '2024-12-14T09:15:00',
        },
        {
            id: 'SR-2024-003',
            subject: 'CCTV Camera Malfunction - Parking Area',
            category: 'Surveillance',
            location: 'Parking Lot A',
            severity: 'medium',
            department: 'IT',
            details: 'Camera #14 and #15 in Parking Lot A are not recording. Issue detected during morning review.',
            actionsTaken: 'IT team notified. Temporary mobile camera deployed.',
            remarks: 'Hardware replacement may be required.',
            status: 'in_progress',
            submittedBy: 'security_officer_1',
            submittedAt: '2024-12-13T07:00:00',
            timeline: 7,
            timelineEnd: '2024-12-20T07:00:00',
        },
        {
            id: 'SR-2024-004',
            subject: 'Chemical Spill Near Warehouse',
            category: 'Hazardous Materials',
            location: 'Warehouse D - Loading Bay',
            severity: 'critical',
            department: 'Manufacturing',
            details:
                'Minor chemical spill detected at the loading bay. Chemical identified as cleaning solvent. Area cordoned off immediately.',
            actionsTaken: 'Spill containment team deployed. Area evacuated. Cleanup initiated per protocol.',
            remarks: 'Environmental safety team inspection required.',
            status: 'for_checking',
            submittedBy: 'security_officer_3',
            submittedAt: '2024-12-12T11:45:00',
        },
        {
            id: 'SR-2024-005',
            subject: 'Emergency Exit Blocked - Building A',
            category: 'Fire Safety',
            location: 'Building A - Ground Floor',
            severity: 'high',
            department: 'Facilities',
            details:
                'Emergency exit on the ground floor of Building A found blocked by stored equipment during routine patrol.',
            actionsTaken: 'Obstruction removed immediately. Department head notified.',
            remarks: 'Follow-up inspection scheduled.',
            status: 'done',
            submittedBy: 'security_officer_2',
            submittedAt: '2024-12-11T16:20:00Z',
        },
        {
            id: 'SR-2024-006',
            subject: 'Visitor Badge System Offline',
            category: 'Access Control',
            location: 'Main Reception',
            severity: 'medium',
            department: 'IT',
            details: 'Visitor badge system at main reception not responding to card scans. Backup system activated.',
            actionsTaken: 'IT team dispatched for troubleshooting. Temporary manual check-in process implemented.',
            remarks: 'System replacement quote requested from vendor.',
            status: 'pending',
            submittedBy: 'gastaff',
            submittedAt: '2024-12-10T14:30:00Z',
        },
    ],

    users: [
        { id: '1', name: 'Juan Dela Cruz', username: 'jdelacruz', role: 'Security', department: '-', status: 'active' },
        { id: '2', name: 'Maria Santos', username: 'msantos', role: 'GA Staff', department: '-', status: 'active' },
        {
            id: '3',
            name: 'Pedro Reyes',
            username: 'preyes',
            role: 'Department',
            department: 'Manufacturing',
            status: 'active',
        },
        { id: '4', name: 'Ana Garcia', username: 'agarcia', role: 'Department', department: 'IT', status: 'inactive' },
        { id: '5', name: 'Carlos Tan', username: 'ctan', role: 'Security', department: '-', status: 'active' },
    ],

    getReports() {
        return this.reports;
    },

    getReportById(id) {
        return this.reports.find((r) => r.id === id);
    },

    addReport(report) {
        this.reports.unshift(report);
    },

    updateReportStatus(id, status) {
        const report = this.reports.find((r) => r.id === id);
        if (report) {
            report.status = status;
        }
    },

    getUsers() {
        return this.users;
    },
};

// ============================================
// UI HELPERS
// ============================================
const UI = {
    // Toggle password visibility
    togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            button.innerHTML = '<i class="bi bi-eye-slash" aria-hidden="true"></i>';
        } else {
            input.type = 'password';
            button.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';
        }
    },

    // Toggle notifications dropdown
    toggleNotifications() {
        const dropdown = document.getElementById('notifications-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden') && typeof Notifications !== 'undefined') {
                Notifications.refresh();
            }
        }
    },

    // Toggle modal
    toggleModal(modalId) {
        console.log('toggleModal called with:', modalId);
        const modal = document.getElementById(modalId);
        console.log('Modal element:', modal);
        if (modal) {
            console.log('Modal classes before:', modal.className);
            const willOpen = modal.classList.contains('hidden');
            modal.classList.toggle('hidden');
            if (willOpen) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            } else {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
            console.log('Modal classes after:', modal.className);
            console.log('Is hidden?:', modal.classList.contains('hidden'));
        } else {
            console.error('Modal not found:', modalId);
        }
    },

    // Add user function
    addUser() {
        // Get form values
        const name = document.querySelector('#add-user-modal input[placeholder="Enter full name"]').value;
        const username = document.querySelector('#add-user-modal input[placeholder="Enter username"]').value;
        const role = document.querySelector('#add-user-modal select').value;
        const department = document.querySelector('#add-user-modal select:last-of-type').value;

        if (!name || !username) {
            this.showAlert('Please fill in all required fields.');
            return;
        }

        // Add user to table (in a real app, this would save to backend)
        const tbody = document.getElementById('users-table');
        if (tbody) {
            const newRow = tbody.insertRow();
            newRow.innerHTML = `
        <td class="font-medium">${name}</td>
        <td class="font-mono text-xs">${username}</td>
        <td>${role}</td>
        <td class="text-muted-foreground">${department}</td>
        <td><span class="badge-approved">Active</span></td>
        <td>
          <div class="flex items-center gap-1">
            <button class="icon-btn" title="Edit">
              <i class="bi bi-pencil-square" aria-hidden="true"></i>
            </button>
            <button class="icon-btn danger" title="Delete">
              <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
          </div>
        </td>
      `;

            // Update stats
            const totalUsersEl = document.getElementById('stat-total-users');
            const activeUsersEl = document.getElementById('stat-active-users');
            if (totalUsersEl) totalUsersEl.textContent = parseInt(totalUsersEl.textContent) + 1;
            if (activeUsersEl) activeUsersEl.textContent = parseInt(activeUsersEl.textContent) + 1;

            // Clear form
            document.querySelector('#add-user-modal input[placeholder="Enter full name"]').value = '';
            document.querySelector('#add-user-modal input[placeholder="Enter username"]').value = '';

            this.showAlert('User added successfully!', 'success');
        }
    },

    // Show alert
    showAlert(message, type = 'error') {
        const alertBox = document.getElementById('alert-box');
        if (alertBox) {
            const bsType = type === 'error' ? 'danger' : type;
            alertBox.className = `alert alert-${bsType} alert-${type} mb-4`;
            alertBox.textContent = message;
            alertBox.classList.remove('hidden');

            setTimeout(() => {
                alertBox.classList.add('hidden');
            }, 5000);
        }
    },

    // Format date
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    },

    // Format date with time
    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    },

    // Status badge HTML
    getStatusBadge(status) {
        const statusConfig = {
            // Current DB statuses
            submitted_to_ga_manager: { label: 'Submitted to GA Manager', class: 'badge-pending' },
            sent_to_department: { label: 'Sent to Department', class: 'badge-in-progress' },
            under_department_fix: { label: 'Under Department Fix', class: 'badge-in-progress' },
            for_security_final_check: { label: 'For Security Final Check', class: 'badge-checking' },
            returned_to_department: { label: 'Returned to Department', class: 'badge-in-progress' },
            resolved: { label: 'Resolved', class: 'badge-done' },

            // Legacy/mock statuses (demo mode)
            pending: { label: 'Pending', class: 'badge-pending' },
            approved: { label: 'Approved', class: 'badge-approved' },
            in_progress: { label: 'In Progress', class: 'badge-in-progress' },
            done: { label: 'Done', class: 'badge-done' },
            for_checking: { label: 'For Checking', class: 'badge-checking' },
            closed: { label: 'Closed', class: 'badge-closed' },
            critical: { label: 'Critical', class: 'badge-critical' },
        };

        const config = statusConfig[status] || statusConfig['pending'];
        return `<span class="${config.class}">${config.label}</span>`;
    },

    // Severity badge HTML
    getSeverityBadge(severity) {
        const severityConfig = {
            low: { label: 'Low', class: 'badge-approved' },
            medium: { label: 'Medium', class: 'badge-pending' },
            high: { label: 'High', class: 'badge-in-progress' },
            critical: { label: 'Critical', class: 'badge-critical' },
        };

        const config = severityConfig[severity] || severityConfig['low'];
        return `<span class="${config.class}">${config.label}</span>`;
    },

    // Filter table
    filterTable(tableId, searchValue, columnIndex = -1) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        const lowerSearch = searchValue.toLowerCase();

        rows.forEach((row) => {
            let text = '';
            if (columnIndex >= 0) {
                const cell = row.cells[columnIndex];
                text = cell ? cell.textContent.toLowerCase() : '';
            } else {
                text = row.textContent.toLowerCase();
            }

            row.style.display = text.includes(lowerSearch) ? '' : 'none';
        });
    },
};

// Make available to inline onclick handlers (e.g. notifications bell)
window.UI = UI;

// ============================================
// NOTIFICATIONS (Server Mode)
// ============================================
const Notifications = {
    _pollMs: 30000,
    _pollTimer: null,
    _inFlight: false,

    init() {
        this.bell = document.getElementById('notifications-bell');
        this.dropdown = document.getElementById('notifications-dropdown');
        this.badge = document.getElementById('notification-badge');
        this.list = document.getElementById('notifications-list');
        this.markAllBtn = document.getElementById('notifications-mark-all');

        if (!this.bell || !this.dropdown || !this.list) return;

        if (this.markAllBtn) {
            this.markAllBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.markAllRead();
            });
        }

        this.refresh();
        this._pollTimer = setInterval(() => this.refresh(), this._pollMs);
    },

    _csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    },

    _setBadge(count) {
        if (!this.badge) return;
        const n = Number(count || 0);
        if (n > 0) {
            this.badge.style.display = 'flex';
            this.badge.textContent = n > 99 ? '99+' : String(n);
        } else {
            this.badge.style.display = 'none';
            this.badge.textContent = '0';
        }
    },

    _timeAgo(dateString) {
        if (!dateString) return '';
        const d = new Date(dateString.replace(' ', 'T'));
        const now = new Date();
        const diffMs = now.getTime() - d.getTime();
        if (!isFinite(diffMs)) return '';

        const sec = Math.floor(diffMs / 1000);
        if (sec < 60) return sec + ' sec ago';
        const min = Math.floor(sec / 60);
        if (min < 60) return min + ' min ago';
        const hr = Math.floor(min / 60);
        if (hr < 24) return hr + ' hour' + (hr === 1 ? '' : 's') + ' ago';
        const day = Math.floor(hr / 24);
        return day + ' day' + (day === 1 ? '' : 's') + ' ago';
    },

    _render(items) {
        if (!this.list) return;
        const arr = Array.isArray(items) ? items : [];
        if (arr.length === 0) {
            this.list.innerHTML =
                '<div class="notification-item">' +
                '<div class="notification-content">' +
                '<p class="notification-desc">No notifications</p>' +
                '</div>' +
                '</div>';
            return;
        }

        this.list.innerHTML = arr
            .map((n) => {
                const unread = Number(n.is_read || 0) === 0;
                const reportNo = n.report_no ? String(n.report_no) : '';
                const msg = n.message ? String(n.message) : '';
                const when = this._timeAgo(n.created_at);
                const dot = unread ? '<div class="notification-dot"></div>' : '';
                const unreadClass = unread ? ' unread' : '';
                const safeId = Number(n.id || 0);

                return (
                    '<div class="notification-item' +
                    unreadClass +
                    '" data-id="' +
                    safeId +
                    '" data-report="' +
                    this._escapeAttr(reportNo) +
                    '">' +
                    dot +
                    '<div class="notification-content">' +
                    '<p class="notification-title">' +
                    this._escapeHtml(msg) +
                    '</p>' +
                    (reportNo ? '<p class="notification-desc">' + this._escapeHtml(reportNo) + '</p>' : '') +
                    (when ? '<span class="notification-time">' + this._escapeHtml(when) + '</span>' : '') +
                    '</div>' +
                    '</div>'
                );
            })
            .join('');

        // Click handler: mark read, then open report modal (if report_no exists)
        this.list.querySelectorAll('.notification-item').forEach((el) => {
            el.addEventListener('click', async () => {
                const id = Number(el.getAttribute('data-id') || 0);
                const reportNo = el.getAttribute('data-report') || '';
                if (id > 0) await this.markRead(id);
                if (reportNo) {
                    if (this.dropdown) this.dropdown.classList.add('hidden');
                    if (typeof ReportModal !== 'undefined' && ReportModal.overlay) {
                        ReportModal.open(reportNo);
                    }
                }
            });
        });
    },

    _escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    },

    _escapeAttr(str) {
        return this._escapeHtml(str).replaceAll('`', '');
    },

    async refresh() {
        if (this._inFlight) return;
        if (!this.bell || !this.list) return;
        this._inFlight = true;

        try {
            const res = await fetch(appUrl('api/notifications.php?limit=20'), {
                method: 'GET',
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            this._setBadge(data.unread_count || 0);
            this._render(data.items || []);
        } catch (e) {
            // Ignore; avoid breaking UI
        } finally {
            this._inFlight = false;
        }
    },

    async markRead(id) {
        try {
            await fetch(appUrl('api/notifications.php'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this._csrfToken(),
                },
                body: JSON.stringify({ action: 'mark_read', id: Number(id) }),
            });
        } catch (e) {
            // ignore
        }
        await this.refresh();
    },

    async markAllRead() {
        try {
            await fetch(appUrl('api/notifications.php'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this._csrfToken(),
                },
                body: JSON.stringify({ action: 'mark_all_read' }),
            });
        } catch (e) {
            // ignore
        }
        await this.refresh();
    },
};

// ============================================
// PAGE-SPECIFIC FUNCTIONS
// ============================================

// Login Page
const LoginPage = {
    init() {
        // Let PHP handle login validation - no JavaScript interference
        const form = document.getElementById('login-form');
        if (form) {
            // Remove any existing event listeners and let form submit normally
            form.addEventListener('submit', (e) => {
                // Allow normal form submission to PHP
                // No preventDefault() - let PHP handle validation
            });
        }
    },
};

// Dashboard Page
const DashboardPage = {
    init() {
        if (window.NIDEC_SERVER_MODE) return;
        // Prevent re-init loops
        if (this._initialized) return;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init(), { once: true });
            return;
        }

        // Only run on the dashboard page
        if (!document.getElementById('stat-total')) return;

        this._initialized = true;
        this.initOnce();
    },

    initOnce() {
        const reports = Data.getReports();

        // Update stats
        const statTotal = document.getElementById('stat-total');
        const statPending = document.getElementById('stat-pending');
        const statInProgress = document.getElementById('stat-in-progress');
        const statResolved = document.getElementById('stat-resolved');
        const statCritical = document.getElementById('stat-critical');

        if (statTotal) statTotal.textContent = reports.length;
        if (statPending) statPending.textContent = reports.filter((r) => r.status === 'pending').length;
        if (statInProgress) statInProgress.textContent = reports.filter((r) => r.status === 'in_progress').length;
        if (statResolved)
            statResolved.textContent = reports.filter((r) => r.status === 'done' || r.status === 'closed').length;
        if (statCritical) statCritical.textContent = reports.filter((r) => r.severity === 'critical').length;

        // Render recent reports table
        const tbody = document.getElementById('recent-reports');
        if (tbody) {
            const recentReports = reports.slice(0, 5);
            console.log('Dashboard: Found tbody element, populating with', recentReports.length, 'reports');

            if (recentReports.length === 0) {
                tbody.innerHTML =
                    '<tr><td colspan="4" class="text-center text-muted-foreground">No recent reports found</td></tr>';
            } else {
                let html = '';
                for (let i = 0; i < recentReports.length; i++) {
                    const report = recentReports[i];
                    html += '<tr class="clickable-row" onclick="ReportModal.open(\'' + report.id + '\')">';
                    html += '<td class="font-mono text-xs">' + report.id + '</td>';
                    html += '<td class="max-w-[200px] truncate">' + report.subject + '</td>';
                    html += '<td class="text-muted-foreground">' + report.category + '</td>';
                    html += '<td>' + UI.getStatusBadge(report.status) + '</td>';
                    html += '</tr>';
                }
                tbody.innerHTML = html;
                console.log('Dashboard: Table populated with', recentReports.length, 'reports');
            }
        } else {
            console.error('Dashboard: tbody element not found!');
        }

        // Render Pending Approval Panel for GA Manager
        const pendingApprovalList = document.getElementById('pending-approval-list');
        if (pendingApprovalList) {
            this.renderPendingApprovalPanel(pendingApprovalList, reports);
        }

        // Update category progress bars
        const total = reports.length;
        const categories = ['Access Control', 'Fire Safety', 'Surveillance', 'Hazardous Materials'];
        categories.forEach((cat) => {
            const count = reports.filter((r) => r.category === cat).length;
            const pct = total > 0 ? (count / total) * 100 : 0;
            const bar = document.getElementById(`progress-${cat.toLowerCase().replace(/\s+/g, '-')}`);
            const countEl = document.getElementById(`count-${cat.toLowerCase().replace(/\s+/g, '-')}`);
            if (bar) bar.style.width = `${pct}%`;
            if (countEl) countEl.textContent = count;
        });
    },

    renderPendingApprovalPanel(container, reports) {
        // Filter reports waiting for GA Manager approval (status: 'approved' - already reviewed by GA Staff)
        const pendingApproval = reports.filter((r) => r.status === 'approved');

        if (pendingApproval.length === 0) {
            container.innerHTML = `
        <div class="text-center py-6 text-muted-foreground">
          <i class="bi bi-check-circle mx-auto mb-2 opacity-30" style="font-size: 40px;" aria-hidden="true"></i>
          <p class="text-sm">No reports pending approval</p>
        </div>
      `;
            return;
        }

        container.innerHTML = pendingApproval
            .map(
                (report) => `
      <div class="border rounded-lg p-4 bg-background hover:bg-accent/50 transition-colors">
        <div class="flex items-start justify-between mb-2">
          <div>
            <h4 class="font-medium text-foreground text-sm mb-1">${report.subject}</h4>
            <p class="text-xs text-muted-foreground font-mono">${report.id}</p>
          </div>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${
              (report.severity || 'low') === 'critical'
                  ? 'bg-destructive/10 text-destructive'
                  : (report.severity || 'low') === 'high'
                    ? 'bg-warning/10 text-warning'
                    : 'bg-muted text-muted-foreground'
          }">${(report.severity || 'low').charAt(0).toUpperCase() + (report.severity || 'low').slice(1)}</span>
        </div>
        
        <div class="grid grid-cols-2 gap-2 text-xs mb-3">
          <div class="mt-3">
            <h1 class="text-2xl font-bold text-foreground mb-0.80">Dashboard</h1>
            <p class="text-sm text-muted-foreground">
                Welcome back, <?php echo htmlspecialchars($user['username']); ?>. Here's your security overview.
            </p>
        </div>
        </div>
        
        <div class="text-xs mb-3">
          <span class="text-muted-foreground">Reviewed by GA Staff:</span>
          <p class="font-medium text-foreground">${report.reviewedBy || 'Security Officer'}</p>
        </div>
        
        <div class="flex items-center gap-2">
          <button onclick="DashboardPage.approveReport('${report.id}')" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-md text-xs font-medium bg-[#006341] hover:bg-[#005233] text-white transition-colors">
            <i class="bi bi-check-lg" aria-hidden="true"></i>
            Approve
          </button>
          <button onclick="DashboardPage.rejectReport('${report.id}')" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-md text-xs font-medium bg-[#dc2626] hover:bg-[#b91c1c] text-white transition-colors">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
            Reject
          </button>
        </div>
      </div>
    `,
            )
            .join('');
    },

    approveReport(id) {
        Data.updateReportStatus(id, 'in_progress');
        this.initOnce();
    },

    rejectReport(id) {
        Data.updateReportStatus(id, 'pending');
        this.initOnce();
    },
};

// Reports Page
const ReportsPage = {
    init() {
        // Check if we're on reports page
        if (!document.getElementById('search-input')) return;

        const searchInput = document.getElementById('search-input');
        const statusFilter = document.getElementById('status-filter');

        const filterTable = () => {
            const search = searchInput ? searchInput.value.toLowerCase() : '';
            const status = statusFilter ? statusFilter.value : 'all';
            const rows = document.querySelectorAll('#reports-table tbody tr');

            rows.forEach((row) => {
                const text = row.textContent.toLowerCase();
                const rowStatus = row.dataset.status;

                const matchesSearch = text.includes(search);
                const matchesStatus = status === 'all' || rowStatus === status;

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        };

        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (statusFilter) statusFilter.addEventListener('change', filterTable);
    },
};

// Submit Report Page
const SubmitReportPage = {
    init() {
        // Check if we're on submit report page
        if (!document.getElementById('submit-form')) return;

        const form = document.getElementById('submit-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();

                const newReport = {
                    id: `SR-2024-${String(Data.reports.length + 1).padStart(3, '0')}`,
                    subject: document.getElementById('subject').value,
                    category: document.getElementById('category').value,
                    location: document.getElementById('location').value,
                    severity: document.getElementById('severity').value,
                    department: document.getElementById('department').value,
                    details: document.getElementById('details').value,
                    actionsTaken: document.getElementById('actions-taken').value,
                    remarks: document.getElementById('remarks').value,
                    status: 'pending',
                    submittedBy: Auth.getUser()?.username || 'anonymous',
                    submittedAt: new Date().toISOString(),
                };

                Data.addReport(newReport);

                // Show success message
                form.classList.add('hidden');
                document.getElementById('success-message').classList.remove('hidden');

                setTimeout(() => {
                    window.location.href = 'reports.php';
                }, 3000);
            });
        }
    },
};

// GA Staff Review Page
const GAStaffReviewPage = {
    approved: [],

    init() {
        this.approved = JSON.parse(sessionStorage.getItem('ga_approved') || '[]');
        this.render();
    },

    approve(id) {
        this.approved.push(id);
        sessionStorage.setItem('ga_approved', JSON.stringify(this.approved));
        Data.updateReportStatus(id, 'approved');
        this.render();
    },

    render() {
        const container = document.getElementById('pending-reports');
        if (!container) return;

        const pendingReports = Data.getReports().filter((r) => r.status === 'pending');

        if (pendingReports.length === 0) {
            container.innerHTML = `
        <div class="text-center py-12 text-muted-foreground">
          <i class="bi bi-check-circle mx-auto mb-3 opacity-30" style="font-size: 48px;" aria-hidden="true"></i>
          <p>No pending reports to review.</p>
        </div>
      `;
            return;
        }

        container.innerHTML = pendingReports
            .map((report) => {
                const isApproved = this.approved.includes(report.id);
                return `
        <div class="report-card" data-id="${report.id}">
          <div class="report-card-header">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs text-muted-foreground">${report.id}</span>
                ${UI.getSeverityBadge(report.severity)}
                ${isApproved ? UI.getStatusBadge('approved') : UI.getStatusBadge(report.status)}
              </div>
              <h3 class="font-semibold text-foreground">${report.subject}</h3>
            </div>
            ${
                isApproved
                    ? `
              <div class="flex items-center gap-1.5 text-primary text-sm font-medium">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                Sent to GA Manager
              </div>
            `
                    : `
              <button onclick="GAStaffReviewPage.approve('${report.id}')" class="btn btn-primary btn-sm">
                <i class="bi bi-send" aria-hidden="true"></i>
                Approve to GA Manager
              </button>
            `
            }
          </div>
          <div class="report-meta">
            <div class="report-meta-item">
              <p>Category</p>
              <p>${report.category}</p>
            </div>
            <div class="report-meta-item">
              <p>Location</p>
              <p>${report.location}</p>
            </div>
            <div class="report-meta-item">
              <p>Department</p>
              <p>${report.department}</p>
            </div>
            <div class="report-meta-item">
              <p>Submitted</p>
              <p>${UI.formatDate(report.submittedAt)}</p>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t">
            <p class="text-sm text-muted-foreground">${report.details}</p>
          </div>
        </div>
      `;
            })
            .join('');
    },
};

// GA Manager Approval Page
const GAManagerApprovalPage = {
    approved: [],

    init() {
        this.approved = JSON.parse(sessionStorage.getItem('ga_manager_approved') || '[]');
        this.render();
    },

    approve(id) {
        this.approved.push(id);
        sessionStorage.setItem('ga_manager_approved', JSON.stringify(this.approved));
        this.render();
    },

    render() {
        const container = document.getElementById('approval-reports');
        if (!container) return;

        const reports = Data.getReports().filter((r) => r.status === 'approved' || r.status === 'pending');

        container.innerHTML = reports
            .map((report) => {
                const isApproved = this.approved.includes(report.id);
                return `
        <div class="report-card">
          <div class="report-card-header">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs text-muted-foreground">${report.id}</span>
                ${UI.getSeverityBadge(report.severity)}
              </div>
              <h3 class="font-semibold text-foreground">${report.subject}</h3>
            </div>
            ${
                isApproved
                    ? `
              <div class="flex items-center gap-1.5 text-primary text-sm font-medium">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                Sent to Department
              </div>
            `
                    : `
              <button onclick="GAManagerApprovalPage.approve('${report.id}')" class="btn btn-primary btn-sm">
                <i class="bi bi-send" aria-hidden="true"></i>
                Approve to Department
              </button>
            `
            }
          </div>
          <div class="report-meta">
            <div class="report-meta-item">
              <p>Category</p>
              <p>${report.category}</p>
            </div>
            <div class="report-meta-item">
              <p>Location</p>
              <p>${report.location}</p>
            </div>
            <div class="report-meta-item">
              <p>Department</p>
              <p>${report.department}</p>
            </div>
            <div class="report-meta-item">
              <p>Submitted</p>
              <p>${UI.formatDate(report.submittedAt)}</p>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t">
            <p class="text-sm text-muted-foreground">${report.details}</p>
          </div>
        </div>
      `;
            })
            .join('');
    },
};

// Department Action Page
const DepartmentActionPage = {
    actions: {},

    init() {
        this.actions = JSON.parse(sessionStorage.getItem('dept_actions') || '{}');
        this.render();
    },

    markDone(id) {
        this.actions[id] = { type: 'done' };
        sessionStorage.setItem('dept_actions', JSON.stringify(this.actions));
        Data.updateReportStatus(id, 'done');
        this.render();
    },

    setTimeline(id, days) {
        this.actions[id] = { type: 'timeline', days: days };
        sessionStorage.setItem('dept_actions', JSON.stringify(this.actions));
        Data.updateReportStatus(id, 'in_progress');
        this.render();
    },

    render() {
        const container = document.getElementById('action-reports');
        if (!container) return;

        const reports = Data.getReports().filter((r) => r.status === 'approved' || r.status === 'in_progress');

        container.innerHTML = reports
            .map((report) => {
                const action = this.actions[report.id];
                return `
        <div class="report-card">
          <div class="report-card-header">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs text-muted-foreground">${report.id}</span>
                ${UI.getSeverityBadge(report.severity)}
              </div>
              <h3 class="font-semibold text-foreground">${report.subject}</h3>
            </div>
            ${
                !action
                    ? `
              <div class="flex items-center gap-2">
                <button onclick="DepartmentActionPage.markDone('${report.id}')" class="btn btn-primary btn-sm">
                  <i class="bi bi-check-circle" aria-hidden="true"></i>
                  Done
                </button>
                <select onchange="DepartmentActionPage.setTimeline('${report.id}', this.value)" class="w-auto">
                  <option value="" disabled selected>Set Timeline</option>
                  <option value="3">3 Days</option>
                  <option value="5">5 Days</option>
                  <option value="7">7 Days</option>
                  <option value="14">14 Days</option>
                  <option value="30">30 Days</option>
                </select>
              </div>
            `
                    : action.type === 'done'
                      ? `
              <div class="flex items-center gap-1.5 text-primary text-sm font-medium">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                Marked as Done
              </div>
            `
                      : `
              <div class="flex items-center gap-1.5 text-warning text-sm font-medium">
                <i class="bi bi-clock" aria-hidden="true"></i>
                Timeline: ${action.days} days
              </div>
            `
            }
          </div>
          <div class="report-meta">
            <div class="report-meta-item">
              <p>Category</p>
              <p>${report.category}</p>
            </div>
            <div class="report-meta-item">
              <p>Location</p>
              <p>${report.location}</p>
            </div>
            <div class="report-meta-item">
              <p>Department</p>
              <p>${report.department}</p>
            </div>
            <div class="report-meta-item">
              <p>Submitted</p>
              <p>${UI.formatDate(report.submittedAt)}</p>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t">
            <p class="text-sm text-muted-foreground">${report.details}</p>
          </div>
        </div>
      `;
            })
            .join('');
    },
};

// Final Checking Page
const FinalCheckingPage = {
    confirmed: [],

    init() {
        this.confirmed = JSON.parse(sessionStorage.getItem('final_confirmed') || '[]');
        this.render();
    },

    confirm(id) {
        this.confirmed.push(id);
        sessionStorage.setItem('final_confirmed', JSON.stringify(this.confirmed));
        Data.updateReportStatus(id, 'closed');
        this.render();
    },

    render() {
        const container = document.getElementById('checking-reports');
        if (!container) return;

        const reports = Data.getReports().filter((r) => r.status === 'for_checking' || r.status === 'done');

        container.innerHTML = reports
            .map((report) => {
                const isConfirmed = this.confirmed.includes(report.id);
                return `
        <div class="report-card">
          <div class="report-card-header">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs text-muted-foreground">${report.id}</span>
                ${UI.getSeverityBadge(report.severity)}
                <span class="badge-checking">For Checking</span>
              </div>
              <h3 class="font-semibold text-foreground">${report.subject}</h3>
            </div>
            ${
                !isConfirmed
                    ? `
              <button onclick="FinalCheckingPage.confirm('${report.id}')" class="btn btn-primary btn-sm">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                Final Confirmation
              </button>
            `
                    : `
              <div class="flex items-center gap-1.5 text-primary text-sm font-medium">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                Confirmed & Closed
              </div>
            `
            }
          </div>
          <div class="report-meta">
            <div class="report-meta-item">
              <p>Category</p>
              <p>${report.category}</p>
            </div>
            <div class="report-meta-item">
              <p>Location</p>
              <p>${report.location}</p>
            </div>
            <div class="report-meta-item">
              <p>Department</p>
              <p>${report.department}</p>
            </div>
            <div class="report-meta-item">
              <p>Actions Taken</p>
              <p>${report.actionsTaken || '-'}</p>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t">
            <p class="text-sm text-muted-foreground">${report.details}</p>
          </div>
        </div>
      `;
            })
            .join('');
    },
};

// User Management Page
const UserManagementPage = {
    init() {
        const tbody = document.getElementById('users-table');
        if (tbody) {
            tbody.innerHTML = Data.getUsers()
                .map(
                    (user) => `
        <tr>
          <td class="font-medium">${user.name}</td>
          <td class="font-mono text-xs">${user.username}</td>
          <td>${user.role}</td>
          <td class="text-muted-foreground">${user.department}</td>
          <td>
            <span class="${user.status === 'active' ? 'badge-approved' : 'badge-closed'}">
              ${user.status === 'active' ? 'Active' : 'Inactive'}
            </span>
          </td>
          <td>
            <div class="flex items-center gap-1">
              <button class="icon-btn" title="Edit">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>
              </button>
              <button class="icon-btn danger" title="Delete">
                <i class="bi bi-trash3" aria-hidden="true"></i>
              </button>
            </div>
          </td>
        </tr>
      `,
                )
                .join('');
        }

        // Update stats
        const users = Data.getUsers();
        document.getElementById('stat-total-users').textContent = users.length;
        document.getElementById('stat-active-users').textContent = users.filter((u) => u.status === 'active').length;
        document.getElementById('stat-security-users').textContent = users.filter((u) => u.role === 'Security').length;
    },
};

// Statistics Page
const StatisticsPage = {
    init() {
        const reports = Data.getReports();
        const total = reports.length;

        // By Category
        const byCategory = {};
        reports.forEach((r) => {
            byCategory[r.category] = (byCategory[r.category] || 0) + 1;
        });

        const catContainer = document.getElementById('stats-category');
        if (catContainer) {
            catContainer.innerHTML = Object.entries(byCategory)
                .map(([cat, count]) => {
                    const pct = total > 0 ? (count / total) * 100 : 0;
                    return `
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span class="text-muted-foreground">${cat}</span>
              <span class="font-medium">${count}</span>
            </div>
            <div class="progress-bar">
              <div class="progress-bar-fill" style="width: ${pct}%"></div>
            </div>
          </div>
        `;
                })
                .join('');
        }

        // By Status
        const byStatus = {};
        reports.forEach((r) => {
            byStatus[r.status] = (byStatus[r.status] || 0) + 1;
        });

        const statusColors = {
            pending: 'bg-warning',
            approved: 'bg-primary',
            in_progress: 'bg-info',
            done: 'bg-primary',
            for_checking: 'bg-info',
            closed: 'bg-muted-foreground',
        };

        const statusContainer = document.getElementById('stats-status');
        if (statusContainer) {
            statusContainer.innerHTML = Object.entries(byStatus)
                .map(
                    ([status, count]) => `
        <div class="flex items-center gap-3">
          <div class="w-3 h-3 rounded-full ${statusColors[status] || 'bg-muted-foreground'}"></div>
          <span class="text-sm text-muted-foreground flex-1 capitalize">${status.replace('_', ' ')}</span>
          <span class="text-sm font-medium">${count}</span>
        </div>
      `,
                )
                .join('');
        }

        // By Severity
        const bySeverity = {};
        reports.forEach((r) => {
            bySeverity[r.severity] = (bySeverity[r.severity] || 0) + 1;
        });

        const sevContainer = document.getElementById('stats-severity');
        if (sevContainer) {
            sevContainer.innerHTML = Object.entries(bySeverity)
                .map(([sev, count]) => {
                    const pct = total > 0 ? (count / total) * 100 : 0;
                    const color = sev === 'critical' ? 'bg-destructive' : sev === 'high' ? 'bg-warning' : 'bg-primary';
                    return `
          <div>
            <div class="flex justify-between text-sm mb-1">
              <span class="text-muted-foreground capitalize">${sev}</span>
              <span class="font-medium">${count}</span>
            </div>
            <div class="progress-bar">
              <div class="progress-bar-fill ${color}" style="width: ${pct}%"></div>
            </div>
          </div>
        `;
                })
                .join('');
        }
    },
};

// Analytics Dashboard (Server Mode)
const AnalyticsDashboardPage = {
    root: null,
    apiUrl: '',
    lastPayload: null,
    resizeTimer: null,
    activeTab: 'metrics',
    isDeepAnalytics: false,
    deptSort: { key: 'total_reports', dir: 'desc' },
    detailsSort: { key: 'submitted_at', dir: 'desc' },
    detailsPage: 1,
    detailsPageSize: 10,

    init() {
        this.root = document.getElementById('analytics-dashboard');
        if (!this.root) return;
        this.isDeepAnalytics = this.root.getAttribute('data-deep-analytics') === '1';

        this.initTabs();
        if (this.isDeepAnalytics) this.initAdvancedInteractions();

        this.apiUrl = this.root.getAttribute('data-api-url') || appUrl('api/analytics.php');

        const applyBtn = document.getElementById('af-apply');
        const resetBtn = document.getElementById('af-reset');
        const trendSelect = document.getElementById('trend-mode');

        if (applyBtn) {
            applyBtn.addEventListener('click', () => this.loadAndRender());
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.setFilterInputs({
                    start_date: '',
                    end_date: '',
                    building: '',
                    department_id: '0',
                    severity: '',
                    status: '',
                });
                this.loadAndRender();
            });
        }

        if (trendSelect) {
            trendSelect.addEventListener('change', () => this.loadAndRender());
        }

        // Initial load
        this.loadAndRender();

        // Responsive redraw
        window.addEventListener('resize', () => {
            if (!this.lastPayload) return;
            clearTimeout(this.resizeTimer);
            this.resizeTimer = setTimeout(() => {
                // Only charts need responsive redraw, and only when visible.
                if (this.isChartTab(this.activeTab)) {
                    this.renderCharts(this.lastPayload);
                }
            }, 150);
        });
    },

    initTabs() {
        const tabsBar = document.getElementById('analytics-tabs');
        if (!tabsBar) return;

        const buttons = Array.from(tabsBar.querySelectorAll('.tab-btn[data-tab]'));
        if (!buttons.length) return;

        const showTab = (tabName, opts) => {
            const options = opts || {};
            const name = String(tabName || '').trim() || 'metrics';

            const panels = Array.from(this.root.querySelectorAll('.analytics-panel[data-tab-panel]'));
            panels.forEach((p) => {
                const pName = String(p.getAttribute('data-tab-panel') || '').trim();
                if (pName === name) p.classList.remove('hidden');
                else p.classList.add('hidden');
            });

            buttons.forEach((btn) => {
                const bName = String(btn.getAttribute('data-tab') || '').trim();
                const isActive = bName === name;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                btn.tabIndex = isActive ? 0 : -1;
                if (isActive && options.focus) btn.focus();
            });

            this.activeTab = name;
            if (this.lastPayload && this.isChartTab(name)) {
                // Canvases need a visible parent to get correct width.
                this.renderCharts(this.lastPayload);
            }
        };

        tabsBar.addEventListener('click', (e) => {
            const btn = e.target && e.target.closest ? e.target.closest('.tab-btn[data-tab]') : null;
            if (!btn) return;
            const name = btn.getAttribute('data-tab');
            showTab(name, { focus: false });
        });

        tabsBar.addEventListener('keydown', (e) => {
            const key = e && e.key ? e.key : '';
            if (key !== 'ArrowLeft' && key !== 'ArrowRight' && key !== 'Home' && key !== 'End') return;

            const current =
                document.activeElement && document.activeElement.closest
                    ? document.activeElement.closest('.tab-btn[data-tab]')
                    : null;
            const idx = current ? buttons.indexOf(current) : buttons.findIndex((b) => b.classList.contains('active'));

            if (idx < 0) return;

            e.preventDefault();

            let next = idx;
            if (key === 'ArrowLeft') next = (idx - 1 + buttons.length) % buttons.length;
            if (key === 'ArrowRight') next = (idx + 1) % buttons.length;
            if (key === 'Home') next = 0;
            if (key === 'End') next = buttons.length - 1;

            const name = buttons[next].getAttribute('data-tab');
            showTab(name, { focus: true });
        });

        // Ensure a consistent initial state.
        const initialBtn = buttons.find((b) => b.classList.contains('active')) || buttons[0];
        showTab(initialBtn.getAttribute('data-tab'), { focus: false });
    },

    initAdvancedInteractions() {
        document.querySelectorAll('[data-dept-sort]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = String(btn.getAttribute('data-dept-sort') || '').trim();
                if (!key) return;
                if (this.deptSort.key === key) {
                    this.deptSort.dir = this.deptSort.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.deptSort.key = key;
                    this.deptSort.dir = key === 'department' ? 'asc' : 'desc';
                }
                this.renderDepartmentPerformance(this.lastPayload && this.lastPayload.department_stats);
            });
        });

        document.querySelectorAll('[data-detail-sort]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = String(btn.getAttribute('data-detail-sort') || '').trim();
                if (!key) return;
                if (this.detailsSort.key === key) {
                    this.detailsSort.dir = this.detailsSort.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.detailsSort.key = key;
                    this.detailsSort.dir = key === 'report_no' ? 'asc' : 'desc';
                }
                this.detailsPage = 1;
                this.renderDetailedReports(this.lastPayload && this.lastPayload.detailed_rows);
            });
        });

        const byDeptToggle = document.getElementById('resolution-by-dept-toggle');
        if (byDeptToggle) {
            byDeptToggle.addEventListener('change', () => {
                const overall = document.getElementById('resolution-breakdown-overall');
                const perDept = document.getElementById('resolution-breakdown-department');
                if (!overall || !perDept) return;
                if (byDeptToggle.checked) {
                    overall.classList.add('hidden');
                    perDept.classList.remove('hidden');
                } else {
                    perDept.classList.add('hidden');
                    overall.classList.remove('hidden');
                }
            });
        }

        const prevBtn = document.getElementById('detailed-prev');
        const nextBtn = document.getElementById('detailed-next');
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (this.detailsPage <= 1) return;
                this.detailsPage -= 1;
                this.renderDetailedReports(this.lastPayload && this.lastPayload.detailed_rows);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this.detailsPage += 1;
                this.renderDetailedReports(this.lastPayload && this.lastPayload.detailed_rows);
            });
        }
    },

    isChartTab(tabName) {
        return ['trend', 'severity', 'department', 'timeline'].includes(String(tabName || ''));
    },

    cssHsl(varName, alpha) {
        const rootStyle = getComputedStyle(document.documentElement);
        const raw = String(rootStyle.getPropertyValue(varName) || '').trim();
        if (!raw) return alpha != null ? `rgba(0,0,0,${alpha})` : 'rgba(0,0,0,1)';
        if (alpha == null) return `hsl(${raw})`;
        return `hsl(${raw} / ${alpha})`;
    },

    showError(message) {
        const el = document.getElementById('analytics-error');
        if (!el) return;
        if (message) {
            el.textContent = String(message);
            el.classList.remove('hidden');
        } else {
            el.textContent = '';
            el.classList.add('hidden');
        }
    },

    getFilterValues() {
        const getVal = (id) => {
            const el = document.getElementById(id);
            return el ? String(el.value || '') : '';
        };

        return {
            start_date: getVal('af-start'),
            end_date: getVal('af-end'),
            building: getVal('af-building'),
            department_id: getVal('af-dept'),
            severity: getVal('af-severity'),
            status: getVal('af-status'),
        };
    },

    setFilterInputs(filters) {
        const setVal = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.value = value;
        };

        if (filters && typeof filters === 'object') {
            if (typeof filters.start_date === 'string') setVal('af-start', filters.start_date);
            if (typeof filters.end_date === 'string') setVal('af-end', filters.end_date);
            if (typeof filters.building === 'string') setVal('af-building', filters.building);
            if (filters.department_id != null) setVal('af-dept', String(filters.department_id));
            if (typeof filters.severity === 'string') setVal('af-severity', filters.severity);
            if (typeof filters.status === 'string') setVal('af-status', filters.status);
        }
    },

    buildRequestUrl() {
        const trendSelect = document.getElementById('trend-mode');
        const trend = trendSelect ? String(trendSelect.value || 'daily') : 'daily';

        const f = this.getFilterValues();
        const params = new URLSearchParams();

        if (f.start_date) params.set('start_date', f.start_date);
        if (f.end_date) params.set('end_date', f.end_date);
        if (f.building) params.set('building', f.building);
        if (f.department_id) params.set('department_id', f.department_id);
        if (f.severity) params.set('severity', f.severity);
        if (f.status) params.set('status', f.status);
        params.set('trend', trend);

        const sep = this.apiUrl.includes('?') ? '&' : '?';
        return this.apiUrl + sep + params.toString();
    },

    setDownloadLinks(payload) {
        const csv = document.getElementById('download-csv');
        const pdf = document.getElementById('download-pdf');
        const hint = document.getElementById('download-hint');

        if (!csv && !pdf) return;

        const f = this.getFilterValues();

        const build = (exportType) => {
            const params = new URLSearchParams();
            params.set('export', exportType);
            if (f.start_date) params.set('start_date', f.start_date);
            if (f.end_date) params.set('end_date', f.end_date);
            if (f.building) params.set('building', f.building);
            if (f.department_id) params.set('department_id', f.department_id);
            if (f.severity) params.set('severity', f.severity);
            if (f.status) params.set('status', f.status);
            const sep = this.apiUrl.includes('?') ? '&' : '?';
            return this.apiUrl + sep + params.toString();
        };

        if (csv) csv.href = build('xlsx');
        if (pdf) pdf.href = build('pdf');

        if (hint && payload && payload.filters) {
            const start = payload.filters.start_date || '';
            const end = payload.filters.end_date || '';
            hint.textContent = start && end ? `Range: ${start} to ${end}` : '';
        }
    },

    async loadAndRender() {
        this.showError('');

        this.drawAllPlaceholders('Loading…');
        const url = this.buildRequestUrl();

        try {
            const res = await fetch(url, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!res.ok) {
                throw new Error('Failed to load analytics (' + res.status + ')');
            }

            const payload = await res.json();
            if (payload && payload.error) {
                throw new Error(payload.error);
            }

            this.lastPayload = payload;

            // Normalize / reflect server-side defaults
            if (payload && payload.filters) {
                this.setFilterInputs({
                    start_date: payload.filters.start_date || '',
                    end_date: payload.filters.end_date || '',
                    building: payload.filters.building || '',
                    department_id: payload.filters.department_id,
                    severity: payload.filters.severity || '',
                    status: payload.filters.status || '',
                });
            }

            this.renderKpis(payload);
            this.renderOverdue(payload);
            this.renderCharts(payload);
            this.renderInsights(payload);
            this.setDownloadLinks(payload);
            if (this.isDeepAnalytics) this.renderAdvanced(payload);
        } catch (err) {
            this.showError(err && err.message ? err.message : 'Unable to load analytics.');
            this.drawAllPlaceholders('No data');
            this.renderOverdue({ overdue: { rows: [] } });
            this.renderKpis({ kpis: {} });
            this.clearInsights();
            if (this.isDeepAnalytics) this.renderAdvanced({});
        }
    },

    renderKpis(payload) {
        const k = payload && payload.kpis ? payload.kpis : {};

        const setText = (id, v) => {
            const el = document.getElementById(id);
            if (el) el.textContent = v;
        };

        const total = Number(k.total_reports ?? 0) || 0;
        const resolved = Number(k.resolved ?? 0) || 0;
        const open = Math.max(0, total - resolved);

        setText('kpi-total', String(total));
        setText('kpi-open', String(open));
        setText('kpi-resolved', String(resolved));
        setText('kpi-overdue', String(Number(k.overdue_reports ?? 0) || 0));

        const avg = k.avg_resolution_days == null ? 'N/A' : String(k.avg_resolution_days);
        setText('kpi-avg-days', avg);

        const sev = payload && payload.severity_distribution ? payload.severity_distribution : null;
        const sevVals = sev && Array.isArray(sev.values) ? sev.values : [];
        const highSev = (Number(sevVals[2]) || 0) + (Number(sevVals[3]) || 0);
        setText('kpi-high-sev', String(highSev));

        const rangeEl = document.getElementById('analytics-range');
        if (rangeEl) {
            const start =
                payload && payload.filters && payload.filters.start_date ? String(payload.filters.start_date) : '';
            const end = payload && payload.filters && payload.filters.end_date ? String(payload.filters.end_date) : '';
            const filterLine = this.buildFilterLine();
            rangeEl.textContent =
                start && end ? `Range: ${start} to ${end}${filterLine ? ' • ' + filterLine : ''}` : filterLine || '';
        }
    },

    setTextById(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    },

    setCaption(id, text) {
        const el = document.getElementById(id);
        if (!el) return;
        const t = String(text || '').trim();
        el.textContent = t;
        const card = el.closest ? el.closest('.insight-card') : null;
        if (card) card.classList.toggle('hidden', !t);
        else el.classList.toggle('hidden', !t);
    },

    buildFilterLine() {
        const parts = [];

        // Department
        const deptEl = document.getElementById('af-dept');
        if (deptEl && deptEl.tagName === 'SELECT') {
            const opt = deptEl.options && deptEl.selectedIndex >= 0 ? deptEl.options[deptEl.selectedIndex] : null;
            const label = opt ? String(opt.textContent || '').trim() : '';
            if (label) parts.push(`Department: ${label}`);
        } else if (deptEl && deptEl.tagName === 'INPUT') {
            parts.push('Department: Your Department');
        }

        // Severity
        const sevEl = document.getElementById('af-severity');
        if (sevEl && sevEl.tagName === 'SELECT') {
            const opt = sevEl.options && sevEl.selectedIndex >= 0 ? sevEl.options[sevEl.selectedIndex] : null;
            const label = opt ? String(opt.textContent || '').trim() : '';
            if (label && label !== 'All') parts.push(`Severity: ${label}`);
        }

        // Status
        const statusEl = document.getElementById('af-status');
        if (statusEl && statusEl.tagName === 'SELECT') {
            const opt =
                statusEl.options && statusEl.selectedIndex >= 0 ? statusEl.options[statusEl.selectedIndex] : null;
            const label = opt ? String(opt.textContent || '').trim() : '';
            if (label && label !== 'All') parts.push(`Status: ${label}`);
        }

        return parts.join(' • ');
    },

    clearInsights() {
        this.setTextById('subtitle-trend', '');
        this.setTextById('subtitle-severity', '');
        this.setTextById('subtitle-department', '');
        this.setTextById('subtitle-timeline', '');

        this.setCaption('caption-trend', '');
        this.setCaption('caption-severity', '');
        this.setCaption('caption-department', '');
        this.setCaption('caption-timeline', '');
    },

    renderInsights(payload) {
        if (!payload) return;

        const filtersLine = this.buildFilterLine();
        const start = payload.filters && payload.filters.start_date ? String(payload.filters.start_date) : '';
        const end = payload.filters && payload.filters.end_date ? String(payload.filters.end_date) : '';
        const rangeLine = start && end ? `${start} to ${end}` : '';

        const trendMode = payload.trend && payload.trend.mode ? String(payload.trend.mode) : '';
        const trendWindow =
            trendMode === 'daily'
                ? 'Daily • last 7 days'
                : trendMode === 'weekly'
                  ? 'Weekly • last 4 weeks'
                  : 'Monthly • last 12 months';
        this.setTextById('subtitle-trend', `${trendWindow}${filtersLine ? ' • ' + filtersLine : ''}`);

        const commonSubtitle = `${rangeLine}${filtersLine ? ' • ' + filtersLine : ''}`.trim();
        this.setTextById('subtitle-severity', commonSubtitle);
        this.setTextById('subtitle-department', commonSubtitle);
        this.setTextById('subtitle-timeline', commonSubtitle);

        this.setCaption('caption-trend', this.buildTrendCaption(payload.trend));
        this.setCaption('caption-severity', this.buildSeverityCaption(payload.severity_distribution));
        this.setCaption('caption-department', this.buildDepartmentCaption(payload.by_department));
        this.setCaption('caption-timeline', this.buildTimelineCaption(payload.timeline));
    },

    buildTrendCaption(trend) {
        const labels = trend && Array.isArray(trend.labels) ? trend.labels : [];
        const values = trend && Array.isArray(trend.values) ? trend.values : [];
        const n = Math.min(labels.length, values.length);
        if (!n) return '';

        let total = 0;
        let maxV = -1;
        let maxIdx = 0;
        for (let i = 0; i < n; i++) {
            const v = Number(values[i]) || 0;
            total += v;
            if (v > maxV) {
                maxV = v;
                maxIdx = i;
            }
        }

        const avg = total / n;
        const maxLabel = String(labels[maxIdx] ?? '');
        const mode = String(trend && trend.mode ? trend.mode : 'daily');
        const unit = mode === 'monthly' ? 'month' : mode === 'weekly' ? 'week' : 'day';
        return `Peak activity was ${maxV} report(s) on ${maxLabel}. Average volume is ${avg.toFixed(1)} per ${unit}.`;
    },

    buildSeverityCaption(sev) {
        const labels = sev && Array.isArray(sev.labels) ? sev.labels : [];
        const values = sev && Array.isArray(sev.values) ? sev.values : [];
        const n = Math.min(labels.length, values.length);
        if (!n) return '';

        const total = values.reduce((a, b) => a + (Number(b) || 0), 0);
        if (total <= 0) return '';

        let maxV = -1;
        let maxIdx = 0;
        for (let i = 0; i < n; i++) {
            const v = Number(values[i]) || 0;
            if (v > maxV) {
                maxV = v;
                maxIdx = i;
            }
        }

        const high = (Number(values[2]) || 0) + (Number(values[3]) || 0);
        const pct = Math.round((high / total) * 100);
        const topLabel = String(labels[maxIdx] ?? '');
        return `${topLabel} is the most common severity (${maxV} of ${total}). High/Critical represent ${pct}% of reports (${high} of ${total}).`;
    },

    buildDepartmentCaption(byDept) {
        const labels = byDept && Array.isArray(byDept.labels) ? byDept.labels : [];
        const values = byDept && Array.isArray(byDept.values) ? byDept.values : [];
        const n = Math.min(labels.length, values.length);
        if (!n) return '';

        let maxV = -1;
        let maxIdx = 0;
        let total = 0;
        for (let i = 0; i < n; i++) {
            const v = Number(values[i]) || 0;
            total += v;
            if (v > maxV) {
                maxV = v;
                maxIdx = i;
            }
        }
        if (total <= 0) return '';

        const topDept = String(labels[maxIdx] ?? '');
        const pct = Math.round((maxV / total) * 100);
        let second = 0;
        for (let i = 0; i < n; i++) {
            if (i === maxIdx) continue;
            const v = Number(values[i]) || 0;
            if (v > second) second = v;
        }
        const ratio = second > 0 ? maxV / second : null;
        const ratioText = ratio ? ` (~${ratio.toFixed(1)}× the next highest)` : '';
        return `Top department is ${topDept} with ${maxV} report(s) (${pct}% of total)${ratioText}.`;
    },

    buildTimelineCaption(tl) {
        const onTime = Number(tl && tl.fixed_on_time) || 0;
        const late = Number(tl && tl.fixed_late) || 0;
        const pending = Number(tl && tl.still_pending) || 0;
        const total = onTime + late + pending;
        if (total <= 0) return '';

        const rate = tl && tl.compliance_rate != null ? String(tl.compliance_rate) + '%' : 'N/A';
        return `Compliance is ${rate}. ${onTime} fixed on time, ${late} fixed late, and ${pending} still pending.`;
    },

    renderOverdue(payload) {
        const tbody = document.getElementById('overdue-body');
        if (!tbody) return;

        const rows = payload && payload.overdue && Array.isArray(payload.overdue.rows) ? payload.overdue.rows : [];
        if (!rows.length) {
            tbody.innerHTML =
                '<tr><td colspan="4" class="text-center text-muted-foreground">No overdue reports.</td></tr>';
            return;
        }

        tbody.innerHTML = rows
            .map((r) => {
                const reportNo = r.report_no || '—';
                const dept = r.department || '—';
                const due = r.fix_due_date || '—';
                const days = r.days_overdue == null ? '—' : String(r.days_overdue);
                return `
        <tr>
          <td class="font-mono text-xs">${this.escapeHtml(String(reportNo))}</td>
          <td class="text-muted-foreground">${this.escapeHtml(String(dept))}</td>
          <td class="text-muted-foreground">${this.escapeHtml(String(due))}</td>
          <td class="font-medium">${this.escapeHtml(String(days))}</td>
        </tr>
      `;
            })
            .join('');
    },

    renderAdvanced(payload) {
        const p = payload && typeof payload === 'object' ? payload : {};
        this.renderDepartmentPerformance(p.department_stats || []);
        this.renderResolutionBreakdown(p.resolution_breakdown || {});
        this.renderSlaDive(p.sla_stats || {});
        this.renderComparison(p.comparison_data || {});
        this.renderRecurring(p.recurring_issues || {});
        this.renderBuildingAnalysis(p.building_stats || []);
        this.renderDetailedReports(p.detailed_rows || []);
        this.renderAutoInsights(p);
    },

    sortRows(rows, key, dir) {
        const list = Array.isArray(rows) ? [...rows] : [];
        const mul = dir === 'asc' ? 1 : -1;
        list.sort((a, b) => {
            const av = a && a[key] != null ? a[key] : '';
            const bv = b && b[key] != null ? b[key] : '';
            const an = Number(av);
            const bn = Number(bv);
            const isNum = Number.isFinite(an) && Number.isFinite(bn) && av !== '' && bv !== '';
            if (isNum) return (an - bn) * mul;
            return String(av).localeCompare(String(bv)) * mul;
        });
        return list;
    },

    renderDepartmentPerformance(rows) {
        const body = document.getElementById('dept-performance-body');
        if (!body) return;

        const sorted = this.sortRows(rows || [], this.deptSort.key, this.deptSort.dir);
        if (!sorted.length) {
            body.innerHTML =
                '<tr><td colspan="6" class="text-center text-muted-foreground">No department performance data.</td></tr>';
            return;
        }

        const maxVolume = Math.max(1, ...sorted.map((r) => Number(r.total_reports) || 0));

        body.innerHTML = sorted
            .map((r) => {
                const total = Number(r.total_reports) || 0;
                const avg = r.avg_resolution_days == null ? 'N/A' : Number(r.avg_resolution_days).toFixed(2);
                const median = r.median_resolution_days == null ? 'N/A' : Number(r.median_resolution_days).toFixed(2);
                const sla = r.sla_compliance == null ? 'N/A' : Number(r.sla_compliance).toFixed(1) + '%';
                const overdue = Number(r.overdue_rate || 0).toFixed(1) + '%';
                const pct = Math.max(2, Math.round((total / maxVolume) * 100));
                return `
                    <tr>
                        <td class="font-medium">${this.escapeHtml(String(r.department || 'Unassigned'))}</td>
                        <td>
                            <div>${total}</div>
                            <div class="metric-inline-bar"><span style="width:${pct}%"></span></div>
                        </td>
                        <td>${avg}</td>
                        <td>${median}</td>
                        <td>${sla}</td>
                        <td>${overdue}</td>
                    </tr>
                `;
            })
            .join('');
    },

    renderResolutionBreakdown(data) {
        const overall = document.getElementById('resolution-breakdown-overall');
        const perDept = document.getElementById('resolution-breakdown-department');
        if (!overall || !perDept) return;

        const buckets = data && data.buckets ? data.buckets : {};
        const total = Number(data && data.total_resolved) || 0;
        const b = [
            { label: '0-24 Hours', value: Number(buckets['0_24_hours']) || 0 },
            { label: '1-3 Days', value: Number(buckets['1_3_days']) || 0 },
            { label: '3-7 Days', value: Number(buckets['3_7_days']) || 0 },
            { label: '7+ Days', value: Number(buckets['7_plus_days']) || 0 },
        ];

        const bar = b
            .map((x) => {
                const pct = total > 0 ? Math.round((x.value / total) * 100) : 0;
                return `<div class="mb-2"><div class="d-flex justify-content-between text-sm"><span>${x.label}</span><span>${x.value} (${pct}%)</span></div><div class="metric-inline-bar"><span style="width:${pct}%"></span></div></div>`;
            })
            .join('');
        overall.innerHTML = bar || '<div class="text-muted-foreground">No resolution breakdown data.</div>';

        const rows = Array.isArray(data && data.by_department) ? data.by_department : [];
        if (!rows.length) {
            perDept.innerHTML = '<div class="text-muted-foreground">No per-department resolution data.</div>';
            return;
        }

        perDept.innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>0-24h</th>
                            <th>1-3d</th>
                            <th>3-7d</th>
                            <th>7+d</th>
                            <th>Total Resolved</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows
                            .map(
                                (r) => `
                            <tr>
                                <td class="font-medium">${this.escapeHtml(String(r.department || 'Unassigned'))}</td>
                                <td>${Number(r['0_24_hours']) || 0}</td>
                                <td>${Number(r['1_3_days']) || 0}</td>
                                <td>${Number(r['3_7_days']) || 0}</td>
                                <td>${Number(r['7_plus_days']) || 0}</td>
                                <td>${Number(r.total_resolved) || 0}</td>
                            </tr>`,
                            )
                            .join('')}
                    </tbody>
                </table>
            </div>
        `;
    },

    renderSlaDive(data) {
        const overall = data && data.overall ? data.overall : {};
        this.setTextById(
            'sla-overall-rate',
            overall.compliance_rate == null ? 'N/A' : String(overall.compliance_rate) + '%',
        );
        this.setTextById('sla-overall-applicable', String(Number(overall.applicable) || 0));
        this.setTextById('sla-overall-violations', String(Number(overall.violations) || 0));

        const body = document.getElementById('sla-worst-body');
        if (!body) return;
        const rows = Array.isArray(data && data.worst_departments) ? data.worst_departments : [];
        if (!rows.length) {
            body.innerHTML =
                '<tr><td colspan="5" class="text-center text-muted-foreground">No SLA violation data.</td></tr>';
            return;
        }

        body.innerHTML = rows
            .map((r) => {
                const rate = r.compliance_rate == null ? 'N/A' : Number(r.compliance_rate).toFixed(1) + '%';
                return `
                    <tr>
                        <td class="font-medium">${this.escapeHtml(String(r.department || 'Unassigned'))}</td>
                        <td>${Number(r.sla_applicable) || 0}</td>
                        <td>${Number(r.on_time) || 0}</td>
                        <td>${Number(r.violations) || 0}</td>
                        <td>${rate}</td>
                    </tr>
                `;
            })
            .join('');
    },

    renderComparison(data) {
        const summary = document.getElementById('comparison-summary');
        const body = document.getElementById('comparison-dept-body');
        if (!summary || !body) return;

        const cur = data && data.current_period ? data.current_period : {};
        const prev = data && data.previous_period ? data.previous_period : {};
        const deltas = data && data.deltas ? data.deltas : {};

        const fmtDelta = (v) => {
            if (v == null) return 'N/A';
            const n = Number(v) || 0;
            const arrow = n >= 0 ? '▲' : '▼';
            const cls = n >= 0 ? 'delta-up' : 'delta-down';
            return `<span class="${cls}">${arrow} ${Math.abs(n).toFixed(1)}%</span>`;
        };

        summary.innerHTML = `
            <div class="row g-3">
                <div class="col-12 col-md-4"><div class="analytics-side-card p-3"><div class="analytics-side-title">Total Reports</div><div class="h4 mb-0">${Number(cur.total_reports) || 0}</div><div class="text-sm">Prev: ${Number(prev.total_reports) || 0} • ${fmtDelta(deltas.total_reports_pct)}</div></div></div>
                <div class="col-12 col-md-4"><div class="analytics-side-card p-3"><div class="analytics-side-title">Closed Reports</div><div class="h4 mb-0">${Number(cur.closed_reports) || 0}</div><div class="text-sm">Prev: ${Number(prev.closed_reports) || 0} • ${fmtDelta(deltas.closed_reports_pct)}</div></div></div>
                <div class="col-12 col-md-4"><div class="analytics-side-card p-3"><div class="analytics-side-title">Overdue Reports</div><div class="h4 mb-0">${Number(cur.overdue_reports) || 0}</div><div class="text-sm">Prev: ${Number(prev.overdue_reports) || 0} • ${fmtDelta(deltas.overdue_reports_pct)}</div></div></div>
            </div>
        `;

        const rows = Array.isArray(data && data.department_vs_department) ? data.department_vs_department : [];
        if (!rows.length) {
            body.innerHTML =
                '<tr><td colspan="5" class="text-center text-muted-foreground">No department comparison data.</td></tr>';
            return;
        }

        body.innerHTML = rows
            .map((r) => {
                const delta = Number(r.delta) || 0;
                const pct =
                    r.delta_pct == null
                        ? 'N/A'
                        : `${delta >= 0 ? '▲' : '▼'} ${Math.abs(Number(r.delta_pct)).toFixed(1)}%`;
                const cls = delta >= 0 ? 'delta-up' : 'delta-down';
                return `
                    <tr>
                        <td class="font-medium">${this.escapeHtml(String(r.department || 'Unassigned'))}</td>
                        <td>${Number(r.current_total) || 0}</td>
                        <td>${Number(r.previous_total) || 0}</td>
                        <td class="${cls}">${delta >= 0 ? '+' : ''}${delta}</td>
                        <td class="${cls}">${pct}</td>
                    </tr>
                `;
            })
            .join('');
    },

    renderRecurring(data) {
        this.setTextById('reopen-rate', `${Number(data && data.reopened_rate).toFixed(1) || '0.0'}%`);
        this.setTextById('reopen-events', String(Number(data && data.reopen_events) || 0));

        const body = document.getElementById('recurring-categories-body');
        if (!body) return;
        const rows = Array.isArray(data && data.top_categories) ? data.top_categories : [];
        if (!rows.length) {
            body.innerHTML =
                '<tr><td colspan="3" class="text-center text-muted-foreground">No recurring issue categories found.</td></tr>';
            return;
        }
        body.innerHTML = rows
            .map(
                (r) => `
                <tr>
                    <td class="font-medium">${this.escapeHtml(String(r.category || 'Uncategorized'))}</td>
                    <td>${Number(r.affected_reports) || 0}</td>
                    <td>${Number(r.reopen_events) || 0}</td>
                </tr>`,
            )
            .join('');
    },

    renderBuildingAnalysis(rows) {
        const body = document.getElementById('building-analysis-body');
        if (!body) return;
        const list = Array.isArray(rows) ? rows : [];
        if (!list.length) {
            body.innerHTML =
                '<tr><td colspan="4" class="text-center text-muted-foreground">No building-level data.</td></tr>';
            return;
        }

        body.innerHTML = list
            .map((r) => {
                const avg = r.avg_resolution_days == null ? 'N/A' : Number(r.avg_resolution_days).toFixed(2);
                const sla = r.sla_compliance == null ? 'N/A' : Number(r.sla_compliance).toFixed(1) + '%';
                return `
                    <tr>
                        <td class="font-medium">${this.escapeHtml(String(r.building || 'N/A'))}</td>
                        <td>${Number(r.total_reports) || 0}</td>
                        <td>${avg}</td>
                        <td>${sla}</td>
                    </tr>
                `;
            })
            .join('');
    },

    renderDetailedReports(rows) {
        const body = document.getElementById('detailed-reports-body');
        const label = document.getElementById('detailed-pagination-label');
        const prev = document.getElementById('detailed-prev');
        const next = document.getElementById('detailed-next');
        if (!body) return;

        const sorted = this.sortRows(rows || [], this.detailsSort.key, this.detailsSort.dir);
        if (!sorted.length) {
            body.innerHTML =
                '<tr><td colspan="6" class="text-center text-muted-foreground">No detailed reports for current filters.</td></tr>';
            if (label) label.textContent = 'Page 0';
            if (prev) prev.disabled = true;
            if (next) next.disabled = true;
            return;
        }

        const totalPages = Math.max(1, Math.ceil(sorted.length / this.detailsPageSize));
        if (this.detailsPage > totalPages) this.detailsPage = totalPages;
        if (this.detailsPage < 1) this.detailsPage = 1;

        const start = (this.detailsPage - 1) * this.detailsPageSize;
        const pageRows = sorted.slice(start, start + this.detailsPageSize);

        body.innerHTML = pageRows
            .map((r) => {
                const res = r.resolution_days == null ? 'N/A' : Number(r.resolution_days).toFixed(2);
                const slaRaw = String(r.sla_status || 'n/a').replace('_', '-');
                const slaCls = ['on-time', 'late', 'overdue', 'pending'].includes(slaRaw) ? slaRaw : 'n-a';
                const rowCls = slaRaw === 'overdue' ? 'row-overdue' : '';
                return `
                    <tr class="${rowCls}">
                        <td class="font-mono text-xs">${this.escapeHtml(String(r.report_no || ''))}</td>
                        <td>${this.escapeHtml(String(r.department || ''))}</td>
                        <td>${this.escapeHtml(String(r.severity || ''))}</td>
                        <td>${this.escapeHtml(String(r.status || ''))}</td>
                        <td>${res}</td>
                        <td><span class="sla-pill ${slaCls}">${this.escapeHtml(String(r.sla_status || 'n/a'))}</span></td>
                    </tr>
                `;
            })
            .join('');

        if (label) label.textContent = `Page ${this.detailsPage} of ${totalPages}`;
        if (prev) prev.disabled = this.detailsPage <= 1;
        if (next) next.disabled = this.detailsPage >= totalPages;
    },

    renderAutoInsights(payload) {
        const target = document.getElementById('caption-department');
        if (!target || !payload) return;
        const deptStats = Array.isArray(payload.department_stats) ? payload.department_stats : [];
        const cmp = payload.comparison_data && payload.comparison_data.deltas ? payload.comparison_data.deltas : null;
        if (!deptStats.length) return;

        const top = deptStats[0];
        const slowest = [...deptStats]
            .filter((d) => d && d.avg_resolution_days != null)
            .sort((a, b) => Number(b.avg_resolution_days) - Number(a.avg_resolution_days))[0];
        const pieces = [];
        if (top) {
            pieces.push(
                `${top.department} has the highest report volume (${top.total_reports}) with SLA ${top.sla_compliance == null ? 'N/A' : top.sla_compliance + '%'}.`,
            );
        }
        if (slowest) {
            pieces.push(
                `${slowest.department} has the longest average resolution time (${Number(slowest.avg_resolution_days).toFixed(2)} days).`,
            );
        }
        if (cmp && cmp.total_reports_pct != null) {
            const p = Number(cmp.total_reports_pct);
            pieces.push(
                `Report volume is ${p >= 0 ? 'up' : 'down'} ${Math.abs(p).toFixed(1)}% versus the previous period.`,
            );
        }
        if (pieces.length) this.setCaption('caption-department', pieces.join(' '));
    },

    escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    drawAllPlaceholders(message) {
        this.drawPlaceholder(document.getElementById('chart-trend'), message);
        this.drawPlaceholder(document.getElementById('chart-severity'), message);
        this.drawPlaceholder(document.getElementById('chart-department'), message);
        this.drawPlaceholder(document.getElementById('chart-timeline'), message);
    },

    font(sizePx, weight) {
        const w = weight || 500;
        return `${w} ${sizePx}px Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif`;
    },

    roundedRectPath(ctx, x, y, w, h, r) {
        const rr = Math.max(0, Math.min(r || 0, Math.min(w, h) / 2));
        ctx.beginPath();
        if (typeof ctx.roundRect === 'function') {
            ctx.roundRect(x, y, w, h, rr);
            return;
        }
        const x2 = x + w;
        const y2 = y + h;
        ctx.moveTo(x + rr, y);
        ctx.arcTo(x2, y, x2, y2, rr);
        ctx.arcTo(x2, y2, x, y2, rr);
        ctx.arcTo(x, y2, x, y, rr);
        ctx.arcTo(x, y, x2, y, rr);
        ctx.closePath();
    },

    fillRoundedRect(ctx, x, y, w, h, r) {
        this.roundedRectPath(ctx, x, y, w, h, r);
        ctx.fill();
    },

    smoothLinePath(ctx, pts, tension) {
        const t = tension == null ? 0.35 : tension;
        const n = Array.isArray(pts) ? pts.length : 0;
        if (n <= 0) return;
        if (n < 3) {
            ctx.beginPath();
            ctx.moveTo(pts[0].x, pts[0].y);
            for (let i = 1; i < n; i++) ctx.lineTo(pts[i].x, pts[i].y);
            return;
        }

        const cp = (p0, p1, p2, p3) => {
            // Catmull-Rom to Bezier control points
            const d1 = { x: (p2.x - p0.x) * t, y: (p2.y - p0.y) * t };
            const d2 = { x: (p3.x - p1.x) * t, y: (p3.y - p1.y) * t };
            return {
                c1: { x: p1.x + d1.x / 3, y: p1.y + d1.y / 3 },
                c2: { x: p2.x - d2.x / 3, y: p2.y - d2.y / 3 },
            };
        };

        ctx.beginPath();
        ctx.moveTo(pts[0].x, pts[0].y);
        for (let i = 0; i < n - 1; i++) {
            const p0 = pts[Math.max(0, i - 1)];
            const p1 = pts[i];
            const p2 = pts[i + 1];
            const p3 = pts[Math.min(n - 1, i + 2)];
            const { c1, c2 } = cp(p0, p1, p2, p3);
            ctx.bezierCurveTo(c1.x, c1.y, c2.x, c2.y, p2.x, p2.y);
        }
    },

    drawValuePill(ctx, x, y, text) {
        const t = String(text);
        ctx.save();
        ctx.font = this.font(11, 700);
        const padX = 8;
        const padY = 5;
        const m = ctx.measureText(t);
        const w = Math.ceil(m.width + padX * 2);
        const h = 22;
        const px = Math.round(x);
        const py = Math.round(y - h / 2);

        ctx.shadowColor = 'rgba(0,0,0,0.10)';
        ctx.shadowBlur = 10;
        ctx.shadowOffsetY = 4;
        ctx.fillStyle = this.cssHsl('--card', 0.98);
        this.fillRoundedRect(ctx, px, py, w, h, 999);

        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
        ctx.strokeStyle = this.cssHsl('--border', 0.9);
        ctx.lineWidth = 1;
        this.roundedRectPath(ctx, px, py, w, h, 999);
        ctx.stroke();

        ctx.fillStyle = this.cssHsl('--foreground');
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        ctx.fillText(t, px + padX, py + h / 2);
        ctx.restore();
        return { w, h };
    },

    setupCanvas(canvas) {
        if (!canvas) return null;
        const ctx = canvas.getContext('2d');
        if (!ctx) return null;

        const parent = canvas.parentElement;
        const cssWidth = parent ? parent.clientWidth : canvas.clientWidth;
        // Cache the intended CSS height once. canvas.height reflects to the DOM attribute,
        // so reading getAttribute('height') after scaling for DPR would keep increasing.
        if (!canvas.dataset.cssHeight) {
            const attr = String(canvas.getAttribute('height') || '').trim();
            const fromAttr = parseInt(attr, 10);
            if (Number.isFinite(fromAttr) && fromAttr > 0) canvas.dataset.cssHeight = String(fromAttr);
            else if (canvas.clientHeight && canvas.clientHeight > 0)
                canvas.dataset.cssHeight = String(canvas.clientHeight);
            else canvas.dataset.cssHeight = '240';
        }
        const cssHeight = parseInt(canvas.dataset.cssHeight, 10);
        if (!cssWidth || !cssHeight) return null;

        const dpr = window.devicePixelRatio || 1;
        canvas.style.width = cssWidth + 'px';
        canvas.style.height = cssHeight + 'px';
        canvas.width = Math.floor(cssWidth * dpr);
        canvas.height = Math.floor(cssHeight * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        return { ctx, w: cssWidth, h: cssHeight };
    },

    clear(canvas) {
        const s = this.setupCanvas(canvas);
        if (!s) return null;
        s.ctx.clearRect(0, 0, s.w, s.h);
        return s;
    },

    drawPlaceholder(canvas, message) {
        const s = this.clear(canvas);
        if (!s) return;
        const { ctx, w, h } = s;
        ctx.fillStyle = this.cssHsl('--muted-foreground');
        ctx.font = '12px Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(String(message || ''), w / 2, h / 2);
    },

    renderCharts(payload) {
        if (!payload) return;
        this.drawTrend(payload.trend);
        this.drawSeverity(payload.severity_distribution);
        this.drawDepartment(payload.by_department);
        this.drawTimeline(payload.timeline);
    },

    drawTrend(trend) {
        const canvas = document.getElementById('chart-trend');
        const s = this.clear(canvas);
        if (!s) return;
        const { ctx, w, h } = s;

        const labels = trend && Array.isArray(trend.labels) ? trend.labels : [];
        const values = trend && Array.isArray(trend.values) ? trend.values : [];
        if (!labels.length || !values.length) {
            this.drawPlaceholder(canvas, 'No data');
            return;
        }

        const padding = { l: 48, r: 18, t: 16, b: 34 };
        const chartW = w - padding.l - padding.r;
        const chartH = h - padding.t - padding.b;

        const maxV = Math.max(1, ...values.map((v) => Number(v) || 0));
        const yMax = Math.ceil(maxV * 1.15);

        // Chart area backdrop (subtle + premium)
        ctx.save();
        ctx.shadowColor = 'rgba(0,0,0,0.08)';
        ctx.shadowBlur = 18;
        ctx.shadowOffsetY = 6;
        const bg = ctx.createLinearGradient(0, padding.t, 0, padding.t + chartH);
        bg.addColorStop(0, this.cssHsl('--muted', 0.25));
        bg.addColorStop(1, this.cssHsl('--muted', 0.12));
        ctx.fillStyle = bg;
        this.fillRoundedRect(ctx, padding.l, padding.t, chartW, chartH, 10);
        ctx.restore();

        // Grid
        ctx.save();
        ctx.strokeStyle = this.cssHsl('--border', 0.75);
        ctx.lineWidth = 1;
        ctx.setLineDash([4, 4]);
        for (let i = 0; i <= 4; i++) {
            const y = padding.t + (chartH * i) / 4;
            ctx.beginPath();
            ctx.moveTo(padding.l, y);
            ctx.lineTo(w - padding.r, y);
            ctx.stroke();
        }
        ctx.restore();

        // Y labels
        ctx.fillStyle = this.cssHsl('--muted-foreground');
        ctx.font = this.font(11, 500);
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        for (let i = 0; i <= 4; i++) {
            const v = Math.round(yMax * (1 - i / 4));
            const y = padding.t + (chartH * i) / 4;
            ctx.fillText(String(v), padding.l - 6, y);
        }

        const n = Math.min(labels.length, values.length);
        const xStep = n > 1 ? chartW / (n - 1) : chartW;
        const xAt = (i) => padding.l + xStep * i;
        const yAt = (v) => padding.t + chartH * (1 - v / yMax);

        const pts = [];
        for (let i = 0; i < n; i++) {
            const v = Number(values[i]) || 0;
            pts.push({ x: xAt(i), y: yAt(v), v });
        }

        // Line + area fill
        const strokeGrad = ctx.createLinearGradient(0, padding.t, 0, padding.t + chartH);
        strokeGrad.addColorStop(0, this.cssHsl('--primary'));
        strokeGrad.addColorStop(1, this.cssHsl('--primary-dark'));

        const fillGrad = ctx.createLinearGradient(0, padding.t, 0, padding.t + chartH);
        fillGrad.addColorStop(0, this.cssHsl('--primary', 0.2));
        fillGrad.addColorStop(1, this.cssHsl('--primary', 0.0));

        // Area (smoothed)
        ctx.save();
        this.smoothLinePath(ctx, pts);
        ctx.lineTo(pts[pts.length - 1].x, padding.t + chartH);
        ctx.lineTo(pts[0].x, padding.t + chartH);
        ctx.closePath();
        ctx.fillStyle = fillGrad;
        ctx.fill();
        ctx.restore();

        // Stroke (draw again for crisp line)
        ctx.save();
        // soft glow under the line
        ctx.shadowColor = this.cssHsl('--primary', 0.35);
        ctx.shadowBlur = 18;
        ctx.shadowOffsetY = 6;
        ctx.strokeStyle = this.cssHsl('--primary', 0.25);
        ctx.lineWidth = 6;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        this.smoothLinePath(ctx, pts);
        ctx.stroke();

        // main line
        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
        ctx.strokeStyle = strokeGrad;
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        this.smoothLinePath(ctx, pts);
        ctx.stroke();
        ctx.restore();

        // Points
        for (let i = 0; i < n; i++) {
            const x = pts[i].x;
            const y = pts[i].y;

            // outer ring
            ctx.beginPath();
            ctx.arc(x, y, 4.2, 0, Math.PI * 2);
            ctx.fillStyle = this.cssHsl('--card');
            ctx.fill();

            // inner dot
            ctx.beginPath();
            ctx.arc(x, y, 2.7, 0, Math.PI * 2);
            ctx.fillStyle = this.cssHsl('--primary');
            ctx.fill();
        }

        // Emphasize latest point
        const last = pts[pts.length - 1];
        ctx.save();
        ctx.beginPath();
        ctx.arc(last.x, last.y, 7.5, 0, Math.PI * 2);
        ctx.fillStyle = this.cssHsl('--primary', 0.16);
        ctx.fill();
        ctx.beginPath();
        ctx.arc(last.x, last.y, 3.5, 0, Math.PI * 2);
        ctx.fillStyle = this.cssHsl('--primary');
        ctx.fill();
        ctx.restore();

        // X labels
        ctx.fillStyle = this.cssHsl('--muted-foreground');
        ctx.font = this.font(11, 500);
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';

        // Reduce label density for long series
        const step = n > 8 ? Math.ceil(n / 7) : 1;
        for (let i = 0; i < n; i += step) {
            ctx.fillText(String(labels[i] ?? ''), xAt(i), padding.t + chartH + 8);
        }
    },

    drawSeverity(sev) {
        const canvas = document.getElementById('chart-severity');
        const s = this.clear(canvas);
        if (!s) return;
        const { ctx, w, h } = s;

        const labels = sev && Array.isArray(sev.labels) ? sev.labels : [];
        const values = sev && Array.isArray(sev.values) ? sev.values : [];
        if (!labels.length || !values.length) {
            this.drawPlaceholder(canvas, 'No data');
            return;
        }

        const total = values.reduce((a, b) => a + (Number(b) || 0), 0);
        if (total <= 0) {
            this.drawPlaceholder(canvas, 'No data');
            this.renderSeverityLegend(labels, values);
            return;
        }

        const colors = [
            this.cssHsl('--success'),
            this.cssHsl('--warning'),
            this.cssHsl('--info'),
            this.cssHsl('--destructive'),
        ];

        const cx = w / 2;
        const cy = h / 2;
        const r = Math.min(w, h) * 0.35;
        let start = -Math.PI / 2;

        // Slices with depth + subtle separation
        ctx.save();
        ctx.lineWidth = 2.5;
        ctx.strokeStyle = this.cssHsl('--card');
        for (let i = 0; i < values.length; i++) {
            const v = Number(values[i]) || 0;
            const angle = (v / total) * Math.PI * 2;

            // Base fill
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, start, start + angle);
            ctx.closePath();
            const base = colors[i % colors.length];
            ctx.fillStyle = base;
            ctx.fill();

            // Highlight overlay (gives premium depth)
            ctx.save();
            ctx.clip();
            const hi = ctx.createRadialGradient(cx, cy, r * 0.15, cx, cy, r);
            hi.addColorStop(0, 'rgba(255,255,255,0.38)');
            hi.addColorStop(0.55, 'rgba(255,255,255,0.10)');
            hi.addColorStop(1, 'rgba(0,0,0,0.10)');
            ctx.fillStyle = hi;
            ctx.beginPath();
            ctx.arc(cx, cy, r, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();

            ctx.stroke();
            start += angle;
        }
        ctx.restore();

        // Donut hole with subtle inner shadow
        ctx.beginPath();
        ctx.arc(cx, cy, r * 0.55, 0, Math.PI * 2);
        ctx.fillStyle = this.cssHsl('--card');
        ctx.fill();

        ctx.save();
        ctx.globalCompositeOperation = 'source-atop';
        const inner = ctx.createRadialGradient(cx, cy, r * 0.2, cx, cy, r * 0.62);
        inner.addColorStop(0, 'rgba(0,0,0,0.00)');
        inner.addColorStop(1, 'rgba(0,0,0,0.10)');
        ctx.fillStyle = inner;
        ctx.beginPath();
        ctx.arc(cx, cy, r * 0.62, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();

        // Total label
        ctx.fillStyle = this.cssHsl('--foreground');
        ctx.font = this.font(16, 700);
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(String(total), cx, cy - 8);
        ctx.fillStyle = this.cssHsl('--muted-foreground');
        ctx.font = this.font(11, 500);
        ctx.fillText('Total', cx, cy + 10);

        this.renderSeverityLegend(labels, values, colors);
    },

    renderSeverityLegend(labels, values, colors) {
        const el = document.getElementById('severity-legend');
        if (!el) return;

        const cols = colors || [
            this.cssHsl('--success'),
            this.cssHsl('--warning'),
            this.cssHsl('--info'),
            this.cssHsl('--destructive'),
        ];

        el.innerHTML = labels
            .map((label, i) => {
                const v = Number(values[i]) || 0;
                const c = cols[i % cols.length];
                return `
        <div class="legend-item">
          <span class="legend-swatch" style="background:${c}"></span>
          <span class="legend-label">${this.escapeHtml(String(label))}</span>
          <span class="legend-value">${v}</span>
        </div>
      `;
            })
            .join('');
    },

    drawDepartment(byDept) {
        const canvas = document.getElementById('chart-department');
        const s = this.clear(canvas);
        if (!s) return;
        const { ctx, w, h } = s;

        const labels = byDept && Array.isArray(byDept.labels) ? byDept.labels : [];
        const values = byDept && Array.isArray(byDept.values) ? byDept.values : [];
        if (!labels.length || !values.length) {
            this.drawPlaceholder(canvas, 'No data');
            return;
        }

        const n = Math.min(labels.length, values.length);
        const maxV = Math.max(1, ...values.slice(0, n).map((v) => Number(v) || 0));
        const padding = { l: 110, r: 18, t: 14, b: 14 };
        const chartW = w - padding.l - padding.r;
        const chartH = h - padding.t - padding.b;
        const rowH = chartH / n;
        const barH = Math.max(10, Math.min(22, rowH * 0.55));

        ctx.font = this.font(11, 500);
        ctx.textBaseline = 'middle';

        const barGrad = ctx.createLinearGradient(padding.l, 0, padding.l + chartW, 0);
        barGrad.addColorStop(0, this.cssHsl('--primary'));
        barGrad.addColorStop(0.55, this.cssHsl('--primary-dark'));
        barGrad.addColorStop(1, this.cssHsl('--primary'));

        for (let i = 0; i < n; i++) {
            const y = padding.t + rowH * i + rowH / 2;
            const label = String(labels[i] ?? '');
            const v = Number(values[i]) || 0;
            const bw = (v / maxV) * chartW;

            // Label
            ctx.fillStyle = this.cssHsl('--muted-foreground');
            ctx.textAlign = 'right';
            ctx.fillText(label.length > 18 ? label.slice(0, 17) + '…' : label, padding.l - 8, y);

            // Track
            ctx.fillStyle = this.cssHsl('--muted', 0.55);
            this.fillRoundedRect(ctx, padding.l, y - barH / 2, chartW, barH, 10);

            // Bar (premium: gradient + slight shadow)
            ctx.save();
            ctx.shadowColor = 'rgba(0,0,0,0.12)';
            ctx.shadowBlur = 12;
            ctx.shadowOffsetY = 4;
            ctx.fillStyle = barGrad;
            this.fillRoundedRect(ctx, padding.l, y - barH / 2, Math.max(2, bw), barH, 10);
            ctx.restore();

            // Subtle shine
            ctx.save();
            this.roundedRectPath(ctx, padding.l, y - barH / 2, Math.max(2, bw), barH, 10);
            ctx.clip();
            const shine = ctx.createLinearGradient(0, y - barH / 2, 0, y + barH / 2);
            shine.addColorStop(0, 'rgba(255,255,255,0.28)');
            shine.addColorStop(0.55, 'rgba(255,255,255,0.06)');
            shine.addColorStop(1, 'rgba(255,255,255,0.00)');
            ctx.fillStyle = shine;
            ctx.fillRect(padding.l, y - barH / 2, Math.max(2, bw), barH);
            ctx.restore();

            // Value pill for readability
            const pillX = padding.l + Math.min(chartW - 44, Math.max(6, bw) + 10);
            this.drawValuePill(ctx, pillX, y, v);
        }
    },

    drawTimeline(tl) {
        const canvas = document.getElementById('chart-timeline');
        const s = this.clear(canvas);
        if (!s) return;
        const { ctx, w, h } = s;

        const onTime = Number(tl && tl.fixed_on_time) || 0;
        const late = Number(tl && tl.fixed_late) || 0;
        const pending = Number(tl && tl.still_pending) || 0;

        const total = onTime + late + pending;
        if (total <= 0) {
            this.drawPlaceholder(canvas, 'No data');
            const rateEl = document.getElementById('timeline-rate');
            if (rateEl) rateEl.textContent = 'N/A';
            return;
        }

        const rateEl = document.getElementById('timeline-rate');
        if (rateEl) {
            const r = tl && tl.compliance_rate != null ? String(tl.compliance_rate) + '%' : 'N/A';
            rateEl.textContent = r;
        }

        const items = [
            { label: 'Fixed On Time', value: onTime, color: this.cssHsl('--success') },
            { label: 'Fixed Late', value: late, color: this.cssHsl('--destructive') },
            { label: 'Still Pending', value: pending, color: this.cssHsl('--warning') },
        ];

        const maxV = Math.max(1, ...items.map((i) => i.value));
        const padding = { l: 120, r: 18, t: 16, b: 16 };
        const chartW = w - padding.l - padding.r;
        const chartH = h - padding.t - padding.b;
        const rowH = chartH / items.length;
        const barH = Math.max(14, Math.min(26, rowH * 0.6));

        ctx.font = this.font(12, 500);
        ctx.textBaseline = 'middle';

        items.forEach((it, idx) => {
            const y = padding.t + rowH * idx + rowH / 2;
            const bw = (it.value / maxV) * chartW;

            // label
            ctx.fillStyle = this.cssHsl('--muted-foreground');
            ctx.textAlign = 'right';
            ctx.fillText(it.label, padding.l - 8, y);

            // track
            ctx.fillStyle = this.cssHsl('--muted', 0.55);
            this.fillRoundedRect(ctx, padding.l, y - barH / 2, chartW, barH, 10);

            // bar with soft shadow
            ctx.save();
            ctx.shadowColor = 'rgba(0,0,0,0.12)';
            ctx.shadowBlur = 12;
            ctx.shadowOffsetY = 4;
            ctx.fillStyle = it.color;
            this.fillRoundedRect(ctx, padding.l, y - barH / 2, Math.max(2, bw), barH, 10);
            ctx.restore();

            // Value pill
            const pillX = padding.l + Math.min(chartW - 44, Math.max(6, bw) + 10);
            this.drawValuePill(ctx, pillX, y, it.value);

            // (value drawn as pill)
        });
    },
};

// Report Modal Controller
const ReportModal = {
    overlay: null,
    modal: null,
    contentEl: null,
    subjectEl: null,
    downloadBtn: null,
    copyLinkBtn: null,
    viewPdfBtn: null,
    actionBtnsEl: null,
    notesAreaEl: null,
    notesInputEl: null,
    currentReportDbId: null,
    currentReportNo: null,
    currentSecurityType: 'internal', // ADD THIS LINE

    init() {
        console.log('[ReportModal] Initializing...');
        this.overlay = document.getElementById('report-modal-overlay');
        this.modal = document.getElementById('report-modal');
        this.contentEl = document.getElementById('modal-report-content');
        this.subjectEl = document.getElementById('modal-report-subject');
        this.downloadBtn = document.getElementById('modal-download-pdf');
        this.copyLinkBtn = document.getElementById('modal-copy-link');
        this.viewPdfBtn = document.getElementById('modal-view-pdf');
        this.actionBtnsEl = document.getElementById('modal-action-buttons');
        this.notesAreaEl = document.getElementById('modal-notes-area');
        this.notesInputEl = document.getElementById('modal-action-notes-input');

        console.log('[ReportModal] Elements found:', {
            overlay: !!this.overlay,
            modal: !!this.modal,
            contentEl: !!this.contentEl,
            subjectEl: !!this.subjectEl,
        });

        if (!this.overlay) {
            console.error('[ReportModal] Modal overlay not found in DOM!');
            return;
        }

        if (this.downloadBtn) {
            this.downloadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (!this.currentReportNo) return;

                const url = appUrl('api/report_pdf.php?id=' + encodeURIComponent(String(this.currentReportNo)));

                console.log('[ReportModal] Opening PDF in new tab: report_pdf.php');

                // Use window.open instead of triggerDownload to ensure the preview/download works
                window.open(url, '_blank');
            });
        }

        if (this.viewPdfBtn) {
            this.viewPdfBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (!this.currentReportNo) return;
                const url = appUrl(
                    'api/report_pdf.php?id=' + encodeURIComponent(String(this.currentReportNo)) + '&preview=1',
                );
                window.open(url, '_blank');
            });
        }

        if (this.copyLinkBtn) {
            this.copyLinkBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (!this.currentReportNo) return;
                const path = appUrl('view-report.php?id=' + encodeURIComponent(String(this.currentReportNo)));
                // Always produce a full absolute URL so it works when pasted into a new tab
                // or shared with another machine.
                const url = path.startsWith('http') ? path : window.location.origin + path;
                navigator.clipboard
                    .writeText(url)
                    .then(() => {
                        const origHtml = this.copyLinkBtn.innerHTML;
                        this.copyLinkBtn.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i>';
                        this.copyLinkBtn.title = 'Link copied!';
                        setTimeout(() => {
                            this.copyLinkBtn.innerHTML = origHtml;
                            this.copyLinkBtn.title = 'Copy shareable link';
                        }, 2000);
                    })
                    .catch(() => {
                        // Fallback: prompt so user can copy manually
                        window.prompt('Copy this link:', url);
                    });
            });
        }

        this.syncDownloadButton();

        // Close on overlay click
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.close();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });

        // Initialize clickable rows
        this.initClickableRows();
        console.log('[ReportModal] Initialization complete');
    },

    initClickableRows() {
        console.log('[ReportModal] initClickableRows: Event delegation already set up via inline onclick');
        // Dashboard rows now use inline onclick for reliability
        // Reports table uses event delegation
        const reportsTable = document.getElementById('reports-table');
        if (reportsTable) {
            reportsTable.addEventListener('click', (e) => {
                const row = e.target.closest('tbody tr.clickable-row');
                if (row) {
                    if (e.target.closest('button, a, .btn')) return;
                    const reportId = row.querySelector('td:first-child')?.textContent;
                    if (reportId) {
                        e.stopPropagation();
                        this.open(reportId.trim());
                    }
                }
            });
        }
    },

    async open(reportId) {
        if (!this.overlay) {
            console.error('[ReportModal] Overlay element not found!');
            return;
        }

        // Open immediately with loading state
        if (this.subjectEl) this.subjectEl.textContent = 'Loading...';
        const reportNoEl = document.getElementById('modal-report-no');
        if (reportNoEl) reportNoEl.textContent = reportId ? String(reportId).trim() : '';
        if (this.contentEl)
            this.contentEl.innerHTML = '<div class="text-sm text-muted-foreground">Loading report details...</div>';
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        try {
            let report = null;

            this.currentReportDbId = null;
            this.currentReportNo = reportId ? String(reportId).trim() : null;
            this.syncDownloadButton();

            // Prefer server data when available so we can print a real DB report.
            // If the API is unavailable (demo / mock), fall back to local mock data.
            const preferServer = !!window.NIDEC_SERVER_MODE || typeof window.NIDEC_SERVER_MODE === 'undefined';
            if (preferServer) {
                try {
                    const res = await fetch(appUrl('api/report.php?id=' + encodeURIComponent(reportId)), {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data && !data.error) {
                            report = data;

                            // 1. Get the field where the "External" label actually lives
                            const submittedByStr = String(report.submittedBy || '').toLowerCase();

                            // 2. CHECK: Does the name contain "(external)"?
                            // Use the securityType field returned directly from the API.
                            // Falls back to 'internal' if the field is missing.
                            const rawSecType = String(report.securityType || '')
                                .toLowerCase()
                                .trim();
                            this.currentSecurityType = rawSecType === 'external' ? 'external' : 'internal';

                            console.log('[ReportModal] Detection Success!');
                            console.log(' - securityType from API:', report.securityType);
                            console.log(' - Template assigned:', this.currentSecurityType);
                        }
                    }
                } catch (e) {
                    // ignore and fall back
                }
            }

            if (!report) {
                const reports = Data.getReports();
                report = reports.find((r) => r.id === reportId) || reports[0];
            }

            if (!report) throw new Error('Report not found');

            if (report && (typeof report.reportId === 'number' || typeof report.reportId === 'string')) {
                const n = parseInt(String(report.reportId), 10);
                this.currentReportDbId = Number.isFinite(n) && n > 0 ? n : null;
            }
            this.syncDownloadButton();

            if (this.subjectEl) this.subjectEl.textContent = report.subject || 'Report Details';
            const reportNoEl = document.getElementById('modal-report-no');
            if (reportNoEl) reportNoEl.textContent = this.currentReportNo || '';
            if (this.contentEl) this.contentEl.innerHTML = this.renderReportContent(report);
            this._lastReportStatus = report.status || '';
            this.renderActionFooter(report);
        } catch (err) {
            if (this.subjectEl) this.subjectEl.textContent = 'Report Details';
            const errReportNoEl = document.getElementById('modal-report-no');
            if (errReportNoEl) errReportNoEl.textContent = reportId ? String(reportId).trim() : '';
            if (this.contentEl)
                this.contentEl.innerHTML =
                    '<div class="alert alert-error">' +
                    (err && err.message ? err.message : 'Unable to load report.') +
                    '</div>';
            this.currentReportDbId = null;
            this.currentReportNo = reportId ? String(reportId).trim() : null;
            this.syncDownloadButton();
        }
    },

    syncDownloadButton() {
        if (!this.downloadBtn) return;
        const enabled = !!this.currentReportNo;
        this.downloadBtn.disabled = !enabled;
        this.downloadBtn.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        this.downloadBtn.title = enabled ? 'Download PDF (server-generated)' : 'Loading report…';

        if (this.viewPdfBtn) {
            this.viewPdfBtn.disabled = !enabled;
            this.viewPdfBtn.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        }
        if (this.copyLinkBtn) {
            this.copyLinkBtn.disabled = !enabled;
            this.copyLinkBtn.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        }
    },

    triggerDownload(url) {
        // Corporate browsers often block popups/print dialogs. This triggers a download
        // without opening a new tab by loading the URL in a hidden iframe.
        try {
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.setAttribute('aria-hidden', 'true');
            iframe.src = url;
            document.body.appendChild(iframe);
            window.setTimeout(() => {
                try {
                    iframe.remove();
                } catch (_) {
                    /* ignore */
                }
            }, 60000);
        } catch (_) {
            // Fallback: navigate (may open the PDF viewer or download depending on browser policy)
            window.location.href = url;
        }
    },

    close() {
        if (this.overlay) {
            this.overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        // Clear role action buttons and notes area
        if (this.actionBtnsEl) this.actionBtnsEl.innerHTML = '';
        if (this.notesAreaEl) this.notesAreaEl.style.display = 'none';
        if (this.notesInputEl) this.notesInputEl.value = '';
        const reportNoEl = document.getElementById('modal-report-no');
        if (reportNoEl) reportNoEl.textContent = '';
        this.currentReportDbId = null;
        this.currentReportNo = null;
        this.syncDownloadButton();
    },

    isOpen() {
        return this.overlay && this.overlay.classList.contains('active');
    },

    // ── Role-specific action footer ──────────────────────────────────────────

    /**
     * Inject role-based action buttons into #modal-action-buttons.
     * Buttons are shown only when the current user has a pending action for
     * the loaded report based on window.NIDEC_ROLE and report.status.
     */
    renderActionFooter(report) {
        if (!this.actionBtnsEl) return;
        this.actionBtnsEl.innerHTML = '';
        if (this.notesAreaEl) this.notesAreaEl.style.display = 'none';
        if (this.notesInputEl) this.notesInputEl.value = '';

        const role = (window.NIDEC_ROLE || '').trim();
        const status = (report.status || '').trim();
        const rno = (report.id || this.currentReportNo || '').trim();
        if (!role || !rno) return;

        const mk = (label, cls, action, targetPage, requiresNotes, confirmMsg) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm ' + cls;
            btn.textContent = label;
            btn.addEventListener('click', () => {
                this.doAction(rno, action, targetPage, requiresNotes, confirmMsg);
            });
            return btn;
        };

        // ── GA Staff ──────────────────────────────────────────────────────────
        if (role === 'ga_staff' && status === 'submitted_to_ga_staff') {
            this.actionBtnsEl.appendChild(
                mk(
                    'Return to Security',
                    'btn-outline-warning',
                    'return',
                    'ga-staff-review.php',
                    false,
                    'Return this report to Security?',
                ),
            );
            this.actionBtnsEl.appendChild(
                mk(
                    'Forward to Manager',
                    'btn-success',
                    'forward',
                    'ga-staff-review.php',
                    false,
                    'Forward this report to the GA Manager?',
                ),
            );
        }

        // ── GA Manager ────────────────────────────────────────────────────────
        if (role === 'ga_manager' && status === 'submitted_to_ga_manager') {
            this.actionBtnsEl.appendChild(
                mk('Reject', 'btn-outline-danger', 'reject', 'ga-manager-approval.php', true, null),
            );
            this.actionBtnsEl.appendChild(
                mk('Return to Security', 'btn-outline-warning', 'return', 'ga-manager-approval.php', true, null),
            );
            this.actionBtnsEl.appendChild(
                mk(
                    'Approve to Dept.',
                    'btn-success',
                    'approve',
                    'ga-manager-approval.php',
                    false,
                    'Approve this report and send to the responsible department?',
                ),
            );
        }

        // ── Security – final checking ─────────────────────────────────────────
        if (role === 'security' && status === 'for_security_final_check') {
            this.actionBtnsEl.appendChild(
                mk('Not Resolved', 'btn-outline-warning', 'not_resolved', 'final-checking.php', true, null),
            );
            this.actionBtnsEl.appendChild(
                mk('Confirm Resolved', 'btn-success', 'confirm_resolved', 'final-checking.php', true, null),
            );
        }

        // ── Department ────────────────────────────────────────────────────────
        if (
            role === 'department' &&
            (status === 'sent_to_department' ||
                status === 'under_department_fix' ||
                status === 'returned_to_department')
        ) {
            const goBtn = document.createElement('a');
            goBtn.className = 'btn btn-sm btn-success';
            goBtn.textContent = 'Open Action Page';
            goBtn.href = appUrl('department-action.php');
            this.actionBtnsEl.appendChild(goBtn);
        }
    },

    /**
     * Perform a quick action from the dashboard modal.
     * If requiresNotes=true the textarea is shown and the user must fill it
     * before the action can be submitted.
     */
    doAction(reportNo, action, targetPage, requiresNotes, confirmMsg) {
        if (requiresNotes) {
            // Show notes area and wait for user to click a dedicated submit button
            if (this.notesAreaEl) this.notesAreaEl.style.display = '';
            if (this.notesInputEl) this.notesInputEl.focus();

            // Replace action buttons with: [Cancel] [Submit with notes]
            if (this.actionBtnsEl) {
                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.className = 'btn btn-sm btn-outline-secondary';
                cancelBtn.textContent = 'Cancel';
                cancelBtn.addEventListener('click', () => {
                    // Re-render the original footer buttons
                    this.renderActionFooter({ status: this._lastReportStatus, id: reportNo });
                });

                const submitBtn = document.createElement('button');
                submitBtn.type = 'button';
                submitBtn.className = 'btn btn-sm btn-primary';
                submitBtn.textContent = 'Confirm & Submit';
                submitBtn.addEventListener('click', () => {
                    const notes = (this.notesInputEl ? this.notesInputEl.value : '').trim();
                    if (!notes) {
                        if (this.notesInputEl) {
                            this.notesInputEl.classList.add('is-invalid');
                            this.notesInputEl.focus();
                        }
                        return;
                    }
                    if (this.notesInputEl) this.notesInputEl.classList.remove('is-invalid');
                    this._submitAction(reportNo, action, targetPage, notes);
                });

                this.actionBtnsEl.innerHTML = '';
                this.actionBtnsEl.appendChild(cancelBtn);
                this.actionBtnsEl.appendChild(submitBtn);
            }
        } else {
            const msg = confirmMsg || 'Confirm: ' + action + ' this report?';
            if (!window.confirm(msg)) return;
            this._submitAction(reportNo, action, targetPage, '');
        }
    },

    /** Fill and submit the hidden POST form to the target action page. */
    _submitAction(reportNo, action, targetPage, notes) {
        const form = document.getElementById('modal-action-form');
        if (!form) return;
        const rnoIn = document.getElementById('modal-action-report-no');
        const actIn = document.getElementById('modal-action-name');
        const notIn = document.getElementById('modal-action-notes-hidden');
        const frIn = document.getElementById('modal-action-final-remarks-hidden');
        if (rnoIn) rnoIn.value = reportNo;
        if (actIn) actIn.value = action;
        if (notIn) notIn.value = notes;
        if (frIn) frIn.value = notes; // final_remarks for security final-checking
        form.action = appUrl(targetPage);
        form.submit();
    },

    // Store last report status for cancel re-render
    _lastReportStatus: '',

    getStatusLabel(status) {
        const labels = {
            submitted_to_ga_manager: 'Submitted to GA Manager',
            sent_to_department: 'Sent to Department',
            under_department_fix: 'Under Department Fix',
            for_security_final_check: 'For Security Final Check',
            returned_to_department: 'Returned to Department',
            resolved: 'Resolved',

            // legacy aliases (older rows / pre-migration)
            submitted: 'Submitted',
            department_action: 'Under Department Fix',
            security_final_checking: 'Security Final Checking',
            closed: 'Resolved',
            ga_staff_returned: 'Returned to Security',

            // legacy/mock statuses
            pending: 'Pending Review',
            approved: 'Approved by GA Staff',
            in_progress: 'In Progress',
            done: 'Done',
            for_checking: 'For Final Checking',
        };
        return labels[status] || status;
    },

    getSeverityLabel(severity) {
        const labels = {
            low: 'Low',
            medium: 'Medium',
            high: 'High',
            critical: 'Critical',
        };
        return labels[severity] || severity || 'Low';
    },

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    },

    renderReportContent(report) {
        const evidenceHtml = report.evidenceImageUrl
            ? `<div class="modal-image-container"><img src="${report.evidenceImageUrl}" alt="Evidence Image" /></div>`
            : `
        <div class="modal-image-container">
          <div class="modal-image-placeholder">
            <i class="bi bi-card-image modal-placeholder-icon" style="font-size: 48px;" aria-hidden="true"></i>
            <p>Uploaded Evidence Image</p>
            <p class="modal-helper-text">No image uploaded</p>
          </div>
        </div>
      `;

        const attachments = Array.isArray(report.attachments) ? report.attachments : [];
        const attachmentsHtml = attachments.length
            ? `
        <div class="modal-info-item mt-3">
          <div class="modal-info-label">Attachments</div>
          <div class="modal-attachment-grid">
            ${attachments
                .map((a) => {
                    const href = a.url || a.file_path || '#';
                    const name = a.file_name || 'Attachment';
                    return `<a class="btn btn-outline modal-attachment-link" href="${href}" target="_blank" rel="noopener">${name}</a>`;
                })
                .join('')}
          </div>
        </div>
      `
            : '';

        const timelineDays = report.timelineDays || report.timeline_days;
        const timelineStart = report.timelineStart || report.timeline_start;
        const timelineDue = report.timelineDue || report.timeline_due;
        const deptAction = report.deptAction || report.dept_action;
        const deptActionLabel =
            deptAction === 'done'
                ? 'DONE'
                : deptAction === 'timeline'
                  ? 'TIMELINE SET'
                  : deptAction
                    ? String(deptAction).toUpperCase()
                    : 'N/A';

        const deptEvidenceUrl =
            report.deptEvidenceImageUrl ||
            report.dept_evidence_image_url ||
            report.deptEvidenceImage ||
            report.dept_evidence_image;
        const deptEvidenceHtml = deptEvidenceUrl
            ? `<div class="modal-image-container"><img src="${deptEvidenceUrl}" alt="Fix Evidence Image" /></div>`
            : `
        <div class="modal-image-container">
          <div class="modal-image-placeholder">
            <i class="bi bi-card-image modal-placeholder-icon" style="font-size: 48px;" aria-hidden="true"></i>
            <p>Fix Evidence Image</p>
            <p class="modal-helper-text">No image uploaded</p>
          </div>
        </div>
      `;

        return `
      <!-- Report Information -->
      <div class="modal-section">
        <div class="modal-section-title">
          <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
          Report Information
        </div>
        <div class="modal-info-grid">
          <div class="modal-info-item">
            <div class="modal-info-label">Report ID</div>
            <div class="modal-info-value font-mono">${report.id}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Category</div>
            <div class="modal-info-value">${report.category || 'N/A'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Location</div>
            <div class="modal-info-value">${report.location || 'N/A'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Severity</div>
            <div class="modal-info-value">${this.getSeverityLabel(report.severity)}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Assigned Department</div>
            <div class="modal-info-value">${report.department || 'N/A'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Date Submitted</div>
            <div class="modal-info-value">${this.formatDate(report.submittedAt)}</div>
          </div>
        </div>
      </div>

      <!-- Security Input -->
      <div class="modal-section">
        <div class="modal-section-title">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
          Security Report Details
        </div>
        <div class="modal-info-item mb-3">
          <div class="modal-info-label">Full Report Details</div>
          <div class="modal-description">${report.details || 'No details provided'}</div>
        </div>
        <div class="modal-info-grid">
          <div class="modal-info-item">
            <div class="modal-info-label">Actions Taken by Security</div>
            <div class="modal-info-value">${report.actionsTaken || 'N/A'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Security Remarks</div>
            <div class="modal-info-value">${report.remarks || 'N/A'}</div>
          </div>
        </div>
        ${evidenceHtml}
        ${attachmentsHtml}
      </div>

      <!-- GA Staff Review -->
      <div class="modal-section">
        <div class="modal-section-title">
          <i class="bi bi-people" aria-hidden="true"></i>
          GA Staff Review
        </div>
        <div class="modal-info-grid">
          <div class="modal-info-item">
            <div class="modal-info-label">Reviewed By</div>
            <div class="modal-info-value">${report.reviewedBy || report.reviewed_by || 'Pending Review'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Approval Status</div>
            <div class="modal-info-value">${report.reviewedBy ? 'Reviewed' : 'Pending'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Date Reviewed</div>
            <div class="modal-info-value">${this.formatDate(report.reviewedAt || report.reviewed_at)}</div>
          </div>
        </div>
        ${report.gaStaffNotes ? `<div class="modal-info-item mt-3"><div class="modal-info-label">GA Staff Notes</div><div class="modal-description">${report.gaStaffNotes}</div></div>` : ''}
      </div>

      <!-- GA Manager Approval -->
      <div class="modal-section">
        <div class="modal-section-title">
          <i class="bi bi-person-check" aria-hidden="true"></i>
          GA Manager Approval
        </div>
        <div class="modal-info-grid">
          <div class="modal-info-item">
            <div class="modal-info-label">Approved By</div>
            <div class="modal-info-value">${report.approvedBy || report.approved_by || 'Pending Approval'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Approval Status</div>
            <div class="modal-info-value">${report.gaManagerDecision ? report.gaManagerDecision.charAt(0).toUpperCase() + report.gaManagerDecision.slice(1) : report.approvedBy ? 'Approved' : 'Pending'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Date Approved</div>
            <div class="modal-info-value">${this.formatDate(report.approvedAt || report.approved_at)}</div>
          </div>
        </div>
        ${report.gaManagerNotes ? `<div class="modal-info-item mt-3"><div class="modal-info-label">GA Manager Notes</div><div class="modal-description">${report.gaManagerNotes}</div></div>` : ''}
      </div>

      <!-- Department Action -->
      <div class="modal-section">
        <div class="modal-section-title">
          <i class="bi bi-building" aria-hidden="true"></i>
          Department Action
        </div>
        <div class="modal-info-grid">
          <div class="modal-info-item">
            <div class="modal-info-label">Timeline Set</div>
            <div class="modal-info-value">${timelineDays ? timelineDays + ' Days' : 'Not Set'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Timeline Start</div>
            <div class="modal-info-value">${this.formatDate(timelineStart)}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Timeline Due</div>
            <div class="modal-info-value">${this.formatDate(timelineDue)}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Department Action Taken</div>
            <div class="modal-info-value">${deptActionLabel}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Department Remarks</div>
            <div class="modal-info-value">${report.deptRemarks || report.dept_remarks || 'N/A'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Acted At</div>
            <div class="modal-info-value">${this.formatDate(report.deptActedAt || report.dept_acted_at)}</div>
          </div>
        </div>
        ${deptEvidenceHtml}
      </div>

      <!-- Security Final Checking -->
      <div class="modal-section">
        <div class="modal-section-title">
          <i class="bi bi-check-circle" aria-hidden="true"></i>
          Security Final Checking
        </div>
        <div class="modal-info-grid">
          <div class="modal-info-item">
            <div class="modal-info-label">Final Checked By</div>
            <div class="modal-info-value">${report.finalCheckedBy || report.final_checked_by || 'Not Checked'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Final Remarks</div>
            <div class="modal-info-value">${report.finalRemarks || report.final_remarks || 'N/A'}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Resolution Status</div>
            <div class="modal-info-value">${this.getStatusLabel(report.status)}</div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-label">Date Closed</div>
            <div class="modal-info-value">${this.formatDate(report.closedAt || report.closed_at || (report.status === 'resolved' || report.status === 'closed' ? report.updatedAt : null))}</div>
          </div>
        </div>
      </div>
    `;
    },
};

// ============================================
// SIDEBAR COLLAPSE (Desktop)
// ============================================
const SidebarCollapse = {
    storageKey: 'nidec_sidebar_collapsed',
    _btn: null,
    _mq: null,

    init() {
        this._btn = document.getElementById('sidebar-collapse-toggle');
        if (!this._btn) return;

        this._mq = window.matchMedia('(min-width: 768px)');

        this._btn.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggle();
        });

        const onMediaChange = () => {
            // Only apply the collapsed layout on desktop.
            if (!this._mq.matches) {
                document.body.classList.remove('sidebar-collapsed');
                document.documentElement.classList.remove('sidebar-collapsed');
                this._updateButton(false);
                return;
            }
            this.setCollapsed(this._readStored());
        };

        if (typeof this._mq.addEventListener === 'function') {
            this._mq.addEventListener('change', onMediaChange);
        } else if (typeof this._mq.addListener === 'function') {
            this._mq.addListener(onMediaChange);
        }

        onMediaChange();
    },

    isCollapsed() {
        return document.body.classList.contains('sidebar-collapsed');
    },

    toggle() {
        this.setCollapsed(!this.isCollapsed());
    },

    setCollapsed(collapsed) {
        const shouldCollapse = Boolean(collapsed);
        if (this._mq && !this._mq.matches) {
            document.body.classList.remove('sidebar-collapsed');
            document.documentElement.classList.remove('sidebar-collapsed');
            this._updateButton(false);
            return;
        }

        document.body.classList.toggle('sidebar-collapsed', shouldCollapse);
        document.documentElement.classList.toggle('sidebar-collapsed', shouldCollapse);
        this._writeStored(shouldCollapse);
        this._updateButton(shouldCollapse);
    },

    _readStored() {
        try {
            return localStorage.getItem(this.storageKey) === '1';
        } catch (e) {
            return false;
        }
    },

    _writeStored(collapsed) {
        try {
            localStorage.setItem(this.storageKey, collapsed ? '1' : '0');
        } catch (e) {
            // ignore
        }
    },

    _updateButton(collapsed) {
        if (!this._btn) return;

        const icon = this._btn.querySelector('i');
        if (icon) {
            icon.classList.remove('bi-chevron-left', 'bi-chevron-right');
            icon.classList.add(collapsed ? 'bi-chevron-right' : 'bi-chevron-left');
        }

        const label = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        this._btn.title = label;
        this._btn.setAttribute('aria-label', label);
        this._btn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
    },
};

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // UI is a helper object; it may not expose an init() method
    if (UI && typeof UI.init === 'function') UI.init();
    Auth.init();

    SidebarCollapse.init();

    // Initialize page-specific functions
    if (document.getElementById('login-form')) LoginPage.init();
    // Dashboard page doesn't have a root #dashboard element; use a stable stat id instead
    if (!window.NIDEC_SERVER_MODE && document.getElementById('stat-total')) DashboardPage.init();
    if (document.getElementById('search-input')) ReportsPage.init();
    if (!window.NIDEC_SERVER_MODE && document.getElementById('submit-report')) SubmitReportPage.init();
    if (!window.NIDEC_SERVER_MODE && document.getElementById('ga-staff-review')) GAStaffReviewPage.init();
    if (!window.NIDEC_SERVER_MODE && document.getElementById('ga-manager-approval')) GAManagerApprovalPage.init();
    if (!window.NIDEC_SERVER_MODE && document.getElementById('department-action')) DepartmentActionPage.init();
    if (!window.NIDEC_SERVER_MODE && document.getElementById('checking-reports')) FinalCheckingPage.init();
    if (!window.NIDEC_SERVER_MODE && document.getElementById('users-table')) UserManagementPage.init();
    if (!window.NIDEC_SERVER_MODE && document.getElementById('stats-category')) StatisticsPage.init();
    if (document.getElementById('analytics-dashboard')) AnalyticsDashboardPage.init();

    // Initialize Report Modal (for all pages with report tables)
    ReportModal.init();

    // Allow deep-link opening of a report modal (used by PDF report links).
    const openReportFromUrl = (() => {
        const params = new URLSearchParams(window.location.search);
        return params.get('open_report') || params.get('report_no') || '';
    })();
    if (openReportFromUrl) {
        setTimeout(() => {
            try {
                ReportModal.open(openReportFromUrl);
            } catch (e) {
                // ignore if modal is not available on the current page
            }
        }, 120);
    }

    // Notifications bell (server mode)
    Notifications.init();

    // Topnav is rendered by PHP session; avoid overriding from JS.

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('notifications-dropdown');
        const bell = document.getElementById('notifications-bell');
        if (dropdown && bell && !dropdown.contains(e.target) && !bell.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Defensive: ensure no lingering overlays/backdrops leave the page dimmed.
    // This can happen after navigation restores (bfcache) or if a modal was left open.
    try {
        document.body.style.overflow = '';
        document.body.classList.remove('modal-open-frozen');
        document.querySelectorAll('.modal-overlay.active').forEach((el) => el.classList.remove('active'));
        const dropdown = document.getElementById('notifications-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
        if (typeof ReportModal !== 'undefined' && typeof ReportModal.close === 'function') {
            ReportModal.close();
        }
    } catch (e) {
        // ignore
    }
});

// Handle back/forward cache restores where DOMContentLoaded may not re-run.
window.addEventListener('pageshow', () => {
    try {
        document.body.style.overflow = '';
        document.body.classList.remove('modal-open-frozen');
        document.querySelectorAll('.modal-overlay.active').forEach((el) => el.classList.remove('active'));
        const dropdown = document.getElementById('notifications-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
        if (typeof ReportModal !== 'undefined' && typeof ReportModal.close === 'function') {
            ReportModal.close();
        }
    } catch (e) {
        // ignore
    }
});
