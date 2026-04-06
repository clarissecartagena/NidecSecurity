<?php
if (!function_exists('app_url')) {
    http_response_code(500);
    die('Missing config.');
}

$user = getUser();
$role = (string) ($user['role'] ?? '');
$deptId = (int) ($user['department_id'] ?? 0);
$userBuilding = normalize_building($user['entity'] ?? null);

$canSeeAll = in_array($role, ['ga_manager', 'ga_staff', 'security'], true);
$canChooseBuilding = in_array($role, ['ga_manager', 'ga_staff', 'department'], true);
$departmentsDb = fetch_departments();
$isDeepAnalytics = ($currentPage ?? '') === 'statistics.php';

$apiUrl = app_url('api/analytics.php');
?>

<style>
    /* 1. FILTER COLLAPSIBLE LOGIC */
    #filter-collapsible-content {
        max-height: 500px;
        transition: all 0.3s ease-in-out;
        overflow: hidden;
    }
    #filter-collapsible-content.collapsed {
        max-height: 0 !important;
        margin-top: 0 !important;
        opacity: 0;
        pointer-events: none;
    }
    #filter-chevron.rotated {
        transform: rotate(-180deg);
    }

    /* 2. TAB BAR TRACK (The rounded grey background) */
    .tabs-bar {
        display: flex !important;
        width: 100% !important;
        gap: 4px !important;
        background-color: #f1f5f9 !important; 
        padding: 6px !important;
        border-radius: 12px !important;
        margin-bottom: 24px !important;
        overflow: hidden !important; 
    }

    /* 3. TAB BUTTONS - BALANCED FOR SPACE & READABILITY */
    #analytics-tabs button.tab-btn {
        flex: 1 !important;
        background-color: transparent !important;
        color: #64748b !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 10px 4px !important; 
        font-size: 0.75rem !important; 
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.02em !important;
        border: none !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        white-space: nowrap !important;
        outline: none !important;
    }

    /* 4. ACTIVE TAB STATE - VIVID GREEN WITH PURE WHITE TEXT */
    #analytics-tabs button.tab-btn.active {
        background-color: #22c55e !important; 
        color: #ffffff !important;           
        font-weight: 800 !important;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3) !important;
    }

    /* 5. TAB HOVER STATE */
    #analytics-tabs button.tab-btn:hover:not(.active) {
        background-color: rgba(0, 0, 0, 0.05) !important;
        color: #1e293b !important;
    }

    /* 6. KPI CARDS - OPTIMIZED FOR 6-COLUMN ROW */
    .kpi-card {
        position: relative !important;
    }

    .kpi-value {
        position: absolute !important;
        top: 12px !important;
        right: 12px !important;
        font-size: 1.4rem !important; /* Prominent but fits 6-col row */
        font-weight: 800 !important;
        line-height: 1 !important;
        margin: 0 !important;
        text-align: right !important;
    }

      .kpi-label {
        font-size: 0.70rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        line-height: 1.2;
        display: block;
        max-width: 65%; /* Prevents text from overlapping the value */
        padding-top: 0.20rem;
    }


    #kpi-total, #kpi-open, #kpi-resolved, #kpi-overdue, #kpi-avg-days, #kpi-high-sev {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        line-height: 1 !important;
    }

    

    .kpi-sub {
        font-size: 0.6rem !important; 
        color: #64748b !important;
        margin-top: 1px;
        display: block;
    }

    /* 7. HEADER RANGE TEXT - REMOVED CAPSLOCK */
    #analytics-range {
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        color: #64748b !important;
        text-transform: none !important; /* Forces normal casing */
    }

    /* 8. RESPONSIVE FIX */
    @media (max-width: 1100px) {
        .tabs-bar { 
            overflow-x: auto !important; 
            flex-wrap: nowrap !important; 
            scrollbar-width: none; 
        }
        .tabs-bar::-webkit-scrollbar { display: none; }
        #analytics-tabs button.tab-btn { min-width: 140px !important; }
    }

    /* ══════════════════════════════════════════════════
       9. GRADIENT DESIGN — CARDS, PANELS & BUTTONS
    ══════════════════════════════════════════════════ */

    /* Page header pill */
    #analytics-dashboard > .mb-4 {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 55%, #eff6ff 100%);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        border: 1px solid #bbf7d0;
    }

    /* Tab bar — gradient track */
    .tabs-bar {
        background: linear-gradient(135deg, #e2e8f0 0%, #f1f5f9 55%, #e2e8f0 100%) !important;
        border: 1px solid #cbd5e1;
    }

    /* Filter panel — green-tinted gradient */
    .section-card.section-accent-primary {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 60%, #eff6ff 100%) !important;
        border: 1px solid #bbf7d0 !important;
    }

    /* ── KPI cards: gradient per colour ── */
    .kpi-card {
        border-radius: 12px !important;
        overflow: hidden;
        position: relative;
        min-height: 100px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }
    .kpi-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 22px rgba(0,0,0,0.13) !important;
    }

    /* 50% colour → white — light enough to read dark text, colour still obvious */
    .kpi-card-blue  { background: linear-gradient(135deg, #bfdbfe 0%, #dbeafe 55%, #f8fafc 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(59,130,246,0.30), 0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-amber { background: linear-gradient(135deg, #fde68a 0%, #fef3c7 55%, #fffdf5 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(217,119,6,0.28),  0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-green { background: linear-gradient(135deg, #86efac 0%, #dcfce7 55%, #f8fff9 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(22,163,74,0.28),   0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-red   { background: linear-gradient(135deg, #fca5a5 0%, #fee2e2 55%, #fff8f8 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(220,38,38,0.25),  0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-cyan  { background: linear-gradient(135deg, #67e8f9 0%, #cffafe 55%, #f0fcff 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(6,182,212,0.28),   0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-rose  { background: linear-gradient(135deg, #fda4af 0%, #ffe4e6 55%, #fff8f9 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(225,29,72,0.25),  0 1px 4px rgba(0,0,0,0.06) !important; }

    .kpi-card:hover {
        transform: translateY(-3px) !important;
    }
    .kpi-card-blue:hover  { box-shadow: 0 10px 28px rgba(59,130,246,0.40),  0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-amber:hover { box-shadow: 0 10px 28px rgba(217,119,6,0.38),   0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-green:hover { box-shadow: 0 10px 28px rgba(22,163,74,0.38),    0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-red:hover   { box-shadow: 0 10px 28px rgba(220,38,38,0.35),   0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-cyan:hover  { box-shadow: 0 10px 28px rgba(6,182,212,0.38),    0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-rose:hover  { box-shadow: 0 10px 28px rgba(225,29,72,0.35),   0 2px 6px rgba(0,0,0,0.08) !important; }

    /* Dark text on light-gradient cards */
    .kpi-card-blue  .kpi-label, .kpi-card-blue  .kpi-value, .kpi-card-blue  i  { color: #1e40af !important; }
    .kpi-card-amber .kpi-label, .kpi-card-amber .kpi-value, .kpi-card-amber i  { color: #92400e !important; }
    .kpi-card-green .kpi-label, .kpi-card-green .kpi-value, .kpi-card-green i  { color: #14532d !important; }
    .kpi-card-red   .kpi-label, .kpi-card-red   .kpi-value, .kpi-card-red   i  { color: #7f1d1d !important; }
    .kpi-card-cyan  .kpi-label, .kpi-card-cyan  .kpi-value, .kpi-card-cyan  i  { color: #164e63 !important; }
    .kpi-card-rose  .kpi-label, .kpi-card-rose  .kpi-value, .kpi-card-rose  i  { color: #881337 !important; }
    .kpi-card-blue  .kpi-sub, .kpi-card-amber .kpi-sub, .kpi-card-green .kpi-sub,
    .kpi-card-red   .kpi-sub, .kpi-card-cyan  .kpi-sub, .kpi-card-rose  .kpi-sub {
        color: #475569 !important;
    }
    .kpi-card-blue  .kpi-value, .kpi-card-amber .kpi-value, .kpi-card-green .kpi-value,
    .kpi-card-red   .kpi-value, .kpi-card-cyan  .kpi-value, .kpi-card-rose  .kpi-value {
        font-size: 2.1rem !important;
    }

    /* ── Chart section panels — NEUTRAL white container so colored cards pop ──
       Must kill ::before (top accent bar) and ::after (radial gradient overlay)
       because background: #fff alone cannot override pseudo-elements. */
    [data-tab-panel="trends"] > .section-card,
    [data-tab-panel="departmental"] > .section-card,
    [data-tab-panel="incident"] > .section-card,
    [data-tab-panel="records"] > .section-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 2px 16px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="trends"] > .section-card::before,
    [data-tab-panel="departmental"] > .section-card::before,
    [data-tab-panel="incident"] > .section-card::before,
    [data-tab-panel="records"] > .section-card::before,
    [data-tab-panel="trends"] > .section-card::after,
    [data-tab-panel="departmental"] > .section-card::after,
    [data-tab-panel="incident"] > .section-card::after,
    [data-tab-panel="records"] > .section-card::after {
        display: none !important;
    }

    /* ── Chart wrap: pure white card, strong shadow — floats above the neutral container ── */
    [data-tab-panel="trends"] .chart-wrap,
    [data-tab-panel="departmental"] .chart-wrap,
    [data-tab-panel="incident"] .chart-wrap,
    [data-tab-panel="records"] .chart-wrap {
        background: #ffffff !important;
        background-image: none !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 6px 24px rgba(0,0,0,0.11), 0 1px 4px rgba(0,0,0,0.06) !important;
    }

    /* ── Chart header: title flush against content, no excess gap ── */
    .analytics-chart-header {
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.75rem !important;
    }
    .analytics-chart-header h2 {
        margin-bottom: 0.1rem !important;
    }
    .analytics-chart-header .chart-subtitle {
        margin-bottom: 0 !important;
    }

    /* ── Side cards: colored per tab theme — NO border, shadow only for depth ── */
    [data-tab-panel="trends"] .analytics-side-card {
        background: linear-gradient(145deg, #bfdbfe 0%, #dbeafe 55%, #eff6ff 100%) !important;
        border: none !important;
        box-shadow: 0 6px 22px rgba(59,130,246,0.28), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="incident"] .analytics-side-card {
        background: linear-gradient(145deg, #fde68a 0%, #fef3c7 55%, #fffbeb 100%) !important;
        border: none !important;
        box-shadow: 0 6px 22px rgba(217,119,6,0.26), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="departmental"] .analytics-side-card {
        background: linear-gradient(145deg, #67e8f9 0%, #cffafe 55%, #f0f9ff 100%) !important;
        border: none !important;
        box-shadow: 0 6px 22px rgba(6,182,212,0.26), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="records"] .analytics-side-card {
        background: linear-gradient(145deg, #86efac 0%, #dcfce7 55%, #f0fdf4 100%) !important;
        border: none !important;
        box-shadow: 0 6px 22px rgba(22,163,74,0.26), 0 1px 4px rgba(0,0,0,0.07) !important;
    }

    /* ── Side card title badge ── */
    .analytics-side-title {
        display: inline-block;
        font-size: 0.7rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.07em !important;
        text-transform: uppercase !important;
        padding: 0.2rem 0.65rem !important;
        border-radius: 20px !important;
        margin-bottom: 0.55rem !important;
    }
    [data-tab-panel="trends"] .analytics-side-title      { background: #1d4ed8; color: #fff !important; }
    [data-tab-panel="incident"] .analytics-side-title   { background: #b45309; color: #fff !important; }
    [data-tab-panel="departmental"] .analytics-side-title { background: #0e7490; color: #fff !important; }
    [data-tab-panel="records"] .analytics-side-title   { background: #15803d; color: #fff !important; }

    /* Side info cards — base style (per-tab rules below override color/shadow) */
    .analytics-side-card {
        border-radius: 10px !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.10) !important;
    }
    /* Insight card — base: just shape + shadow, NO background override
       (per-tab rules below own the background so each tab gets its own color) */
    .insight-card {
        border-radius: 10px !important;
        border: none !important;
    }
    .insight-card::before {
        background-color: #22c55e !important; /* fallback if no tab match */
    }

    /* Per-tab insight card colors + matching left bar */
    [data-tab-panel="trends"] .insight-card {
        background: linear-gradient(145deg, #bfdbfe 0%, #dbeafe 55%, #eff6ff 100%) !important;
        box-shadow: 0 6px 20px rgba(59,130,246,0.22), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="trends"] .insight-card::before { background-color: #1d4ed8 !important; }

    [data-tab-panel="incident"] .insight-card {
        background: linear-gradient(145deg, #fde68a 0%, #fef3c7 55%, #fffbeb 100%) !important;
        box-shadow: 0 6px 20px rgba(217,119,6,0.22), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="incident"] .insight-card::before { background-color: #b45309 !important; }

    [data-tab-panel="departmental"] .insight-card {
        background: linear-gradient(145deg, #67e8f9 0%, #cffafe 55%, #f0f9ff 100%) !important;
        box-shadow: 0 6px 20px rgba(6,182,212,0.22), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="departmental"] .insight-card::before { background-color: #0e7490 !important; }

    [data-tab-panel="records"] .insight-card {
        background: linear-gradient(145deg, #86efac 0%, #dcfce7 55%, #f0fdf4 100%) !important;
        box-shadow: 0 6px 20px rgba(22,163,74,0.22), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="records"] .insight-card::before { background-color: #15803d !important; }

    /* Overdue table wrapper */
    .table-container.table-card {
        background: linear-gradient(160deg, #fff1f2 0%, #fff8f8 55%, #ffffff 100%) !important;
        border-radius: 12px !important;
        border: 1px solid #fecdd3 !important;
    }
    /* KPI section header strip */
    [data-tab-panel="metrics"] .d-flex.align-items-end.border-bottom {
        background: linear-gradient(90deg, #f0fdf4 0%, #ffffff 100%);
        border-radius: 8px 8px 0 0;
        padding: 0.5rem 0.75rem !important;
        margin-bottom: 0.75rem !important;
    }

    /* Overdue table header — light red, 50/50 tint */
    [data-tab-panel="metrics"] table thead tr th {
        background: linear-gradient(90deg, #fca5a5 0%, #fee2e2 100%) !important;
        color: #7f1d1d !important;
        border-bottom: 2px solid #f87171 !important;
        font-weight: 700 !important;
    }

    /* ── Refresh button — gradient green ── */
    #af-apply {
        background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important;
        box-shadow: 0 4px 14px rgba(34,197,94,0.40) !important;
        transition: all 0.2s ease !important;
        border: none !important;
    }
    #af-apply:hover {
        background: linear-gradient(135deg, #14532d 0%, #16a34a 100%) !important;
        box-shadow: 0 6px 20px rgba(34,197,94,0.50) !important;
        transform: translateY(-1px);
    }
    #af-apply:active { transform: translateY(0); }

    /* ── Download buttons ── */
    #download-csv, #download-pdf {
        border: 1.5px solid #22c55e !important;
        color: #15803d !important;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
        font-weight: 700 !important;
        border-radius: 8px !important;
        transition: all 0.2s ease !important;
        padding: 0.35rem 0.9rem !important;
        font-size: 0.78rem !important;
    }
    #download-csv:hover, #download-pdf:hover {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        color: #ffffff !important;
        border-color: #16a34a !important;
        box-shadow: 0 4px 14px rgba(34,197,94,0.35) !important;
        transform: translateY(-1px);
        text-decoration: none;
    }

    .metric-inline-bar {
      height: 6px;
      border-radius: 999px;
      background: rgba(148, 163, 184, 0.3);
      overflow: hidden;
      margin-top: 4px;
    }

    .metric-inline-bar > span {
      display: block;
      height: 100%;
      background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
    }

    .delta-up {
      color: #15803d;
      font-weight: 700;
    }

    .delta-down {
      color: #b91c1c;
      font-weight: 700;
    }

    .sla-pill {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 0.1rem 0.55rem;
      font-size: 0.72rem;
      font-weight: 700;
    }

    .sla-pill.on-time {
      background: #dcfce7;
      color: #166534;
    }

    .sla-pill.late,
    .sla-pill.overdue {
      background: #fee2e2;
      color: #991b1b;
    }

    .sla-pill.pending,
    .sla-pill.n-a {
      background: #e2e8f0;
      color: #334155;
    }

    .row-overdue {
      background: rgba(248, 113, 113, 0.12);
    }
</style>

<main class="main-content">
  <div class="animate-fade-in" id="analytics-dashboard" data-api-url="<?php echo htmlspecialchars(
      $apiUrl,
  ); ?>" data-deep-analytics="<?php echo $isDeepAnalytics ? '1' : '0'; ?>">

    <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
      <div>
        <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Executive Analytics Dashboard</h1>
        <p class="text-sm text-muted-foreground mb-0">System performance, risk profile, and trend tracking</p>
      </div>
      <div class="d-flex align-items-center gap-2 flex-shrink-0 align-self-end">
        <span class="text-xs text-muted-foreground" id="download-hint"></span>
        <a id="download-csv" href="#" class="btn btn-outline btn-sm d-flex align-items-center gap-1" title="Download Excel (XLSX)">
          <i class="bi bi-file-earmark-excel"></i> Excel
        </a>
        <a id="download-pdf" href="#" class="btn btn-outline btn-sm d-flex align-items-center gap-1" title="Download PDF">
          <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
      </div>
    </div>


    <div class="section-card section-accent-primary mb-3">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap cursor-pointer" id="toggle-filters-btn" style="user-select: none;">
        <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="3">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            <h3 class="font-semibold text-foreground uppercase mt-1" style="font-size: 0.9rem; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 0; margin-left: 10px;">Filter Analytics</h3>
        </div>
        <div class="d-flex align-items-center gap-2 text-muted-foreground transition-colors">
            <span class="text-xs font-bold uppercase" id="filter-status-text">Hide Filters</span>
            <svg id="filter-chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="transition-transform duration-200">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </div>
    </div>

    <div id="filter-collapsible-content" class="mt-4 transition-all duration-300 overflow-hidden">
        <form id="analytics-filters" class="row g-2 align-items-end" onsubmit="return false;">
            
            <div class="col-6 col-md-2">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Start Date</label>
                <input type="date" class="form-control form-control-sm border-0 shadow-sm" name="start_date" id="af-start" 
                       style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; height: 38px;" />
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">End Date</label>
                <input type="date" class="form-control form-control-sm border-0 shadow-sm" name="end_date" id="af-end" 
                       style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; height: 38px;" />
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Building</label>
                <?php if ($role === 'security'): ?>
                    <div class="analytics-readonly px-2 d-flex align-items-center" style="background-color: #e9ecef; border-radius: 6px; height: 38px;">
                        <span class="text-sm fw-bold text-muted-foreground"><?php echo htmlspecialchars(
                            $userBuilding ?: '—',
                        ); ?></span>
                    </div>
                    <input type="hidden" name="building" id="af-building" value="<?php echo htmlspecialchars(
                        $userBuilding ?: '',
                    ); ?>" />
                <?php elseif ($canChooseBuilding): ?>
                    <select name="building" id="af-building" class="form-select form-select-sm border-0 shadow-sm" 
                            style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; cursor: pointer; height: 38px;">
                        <option value="">All Buildings</option>
                        <option value="NCFL">NCFL</option>
                        <option value="NPFL">NPFL</option>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="building" id="af-building" value="" />
                <?php endif; ?>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Department</label>
                <?php if ($canSeeAll): ?>
                    <select name="department_id" id="af-dept" class="form-select form-select-sm border-0 shadow-sm" 
                            style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; cursor: pointer; height: 38px;">
                        <option value="0">All Departments</option>
                        <?php foreach ($departmentsDb as $d): ?>
                            <option value="<?php echo (int) $d['id']; ?>"><?php echo htmlspecialchars(
    $d['name'],
); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="analytics-readonly px-2 d-flex align-items-center" style="background-color: #e9ecef; border-radius: 6px; height: 38px;">
                        <span class="text-sm fw-bold text-muted-foreground">Your Dept Only</span>
                    </div>
                    <input type="hidden" name="department_id" id="af-dept" value="<?php echo (int) $deptId; ?>" />
                <?php endif; ?>
            </div>

            <div class="col-6 col-md-1">
              <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Severity</label>
              <select name="severity" id="af-severity" class="form-select form-select-sm border-0 shadow-sm"
                  style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; cursor: pointer; height: 38px;">
                <option value="">All</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>

            <div class="col-6 col-md-2">
              <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Status</label>
              <select name="status" id="af-status" class="form-select form-select-sm border-0 shadow-sm"
                  style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; cursor: pointer; height: 38px;">
                <option value="">All</option>
                <option value="open">Open</option>
                <option value="closed">Closed</option>
                <option value="overdue">Overdue</option>
              </select>
            </div>

            <div class="col-12 col-md-2">
                <button type="button" id="af-apply" class="btn w-100 fw-bold d-flex align-items-center justify-content-center gap-2" 
                        style="background-color: #28a745 !important; color: white !important; border: none; border-radius: 6px; height: 38px; transition: 0.2s;">
                    <i class="bi bi-arrow-clockwise" style="-webkit-text-stroke: 1px;"></i>
                    REFRESH ANALYTICS
                </button>
            </div>

            <div class="col-12 col-md-2">
              <button type="button" id="af-reset" class="btn btn-outline w-100 fw-bold d-flex align-items-center justify-content-center gap-2"
                  style="border-radius: 6px; height: 38px;">
                <i class="bi bi-x-circle"></i>
                RESET
              </button>
            </div>
        </form>
    </div>
</div>

        <div id="analytics-error" class="alert alert-danger hidden mb-3" role="alert"></div>

    <!-- Tabs -->
    <div class="tabs-bar" id="analytics-tabs" role="tablist" aria-label="Analytics Sections">
    <button type="button" class="tab-btn active" role="tab" aria-selected="true" data-tab-target="metrics">Executive Summary</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab-target="trends">Trends &amp; Timeline</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab-target="departmental">Departmental Insights</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab-target="incident">Incident Analysis</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab-target="records">Data Records</button>
    </div>

    <!-- SECTION 1 — KPI PERFORMANCE CARDS -->
    <section class="analytics-panel" data-tab-panel="metrics">
    <div class="d-flex align-items-end justify-content-between pb-2 mb-2 border-bottom">
        <div>
            <h2 class="text-lg font-bold text-foreground mb-0">Executive Key Performance Indicators</h2>
        </div>
        <div class="text-end">
            <p class="text-xs fw-bold text-muted-foreground mb-0 tracking-tight" id="analytics-range" style="text-transform: none !important;">
                Range: 2026-02-01 to 2026-03-02 • Department: All Departments
            </p>
        </div>
    </div>

    <div class="row g-2"> 
        <div class="col-6 col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-blue h-100 p-2 p-md-3 position-relative" role="button" onclick="showMetricDetails('Total Reports', 'kpi-total', 'Total incidents filed within the selected filters.')">
                <i class="bi bi-file-earmark-text mb-1"></i>
                <div class="kpi-label">Total <br> Reports</div>
                <div class="kpi-value" id="kpi-total">0</div>
                <div class="kpi-sub mt-1">Filtered within selected filters.</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-amber h-100 p-2 p-md-3 position-relative" role="button" onclick="showMetricDetails('Open Reports', 'kpi-open', 'Reports that are currently being processed or pending.')">
                <i class="bi bi-envelope-open mb-1"></i>
                <div class="kpi-label">Open <br> Reports</div>
                <div class="kpi-value" id="kpi-open">0</div>
                <div class="kpi-sub mt-1">Not yet fully resolved.</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-green h-100 p-2 p-md-3 position-relative" role="button" onclick="showMetricDetails('Resolved Reports', 'kpi-resolved', 'Reports successfully closed within this timeframe.')">
                <i class="bi bi-check-circle mb-1"></i>
                <div class="kpi-label">Resolved <br> Reports</div>
                <div class="kpi-value" id="kpi-resolved">0</div>
                <div class="kpi-sub mt-1">Closed within the range.</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-red h-100 p-2 p-md-3 position-relative" role="button" onclick="showMetricDetails('Overdue Reports', 'kpi-overdue', 'Reports that have exceeded their target resolution date.')">
                <i class="bi bi-exclamation-octagon mb-1"></i>
                <div class="kpi-label">Overdue <br> Reports</div>
                <div class="kpi-value" id="kpi-overdue">0</div>
                <div class="kpi-sub mt-1">Past due while under fix.</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-cyan h-100 p-2 p-md-3 position-relative" role="button" onclick="showMetricDetails('Avg Resolution Time', 'kpi-avg-days', 'The average number of days taken to resolve a report.')">
                <i class="bi bi-clock-history mb-1"></i>
                <div class="kpi-label">Average <br> Reso. Time</div>
                <div class="kpi-value" id="kpi-avg-days">N/A</div>
                <div class="kpi-sub mt-1">Average time of resolved reports.</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-rose h-100 p-2 p-md-3 position-relative" role="button" onclick="showMetricDetails('High/Critical Severity', 'kpi-high-sev', 'Incidents flagged with High or Critical priority levels.')">
                <i class="bi bi-shield-shaded mb-1"></i>
                <div class="kpi-label">High <br> Critical</div>
                <div class="kpi-value" id="kpi-high-sev">0</div>
                <div class="kpi-sub mt-1">High and Critical Reports</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12 col-xl-8">
            <div class="section-card section-accent-destructive h-100 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="text-sm fw-bold uppercase m-0">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Critical Overdue Alerts
                    </h3>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3" style="font-size: 0.65rem;">ACTION REQUIRED</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 text-muted small">ID</th>
                                <th class="border-0 text-muted small">DEPT</th>
                                <th class="border-0 text-muted small">DUE DATE</th>
                                <th class="border-0 text-muted small">DAYS OVERDUE</th>
                            </tr>
                        </thead>
                        <tbody id="overdue-body">
                            <tr><td colspan="4" class="text-center py-4 text-muted small">Loading critical alerts...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="analytics-side-stack h-100 d-flex flex-column gap-2">
                <div class="analytics-side-card p-3 flex-fill shadow-sm" style="background: linear-gradient(145deg, #fff1f2 0%, #ffffff 100%) !important; border-left: 4px solid #e11d48; border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="analytics-side-title text-white px-2 py-1 rounded" style="background: #881337; font-size: 0.6rem; letter-spacing: 0.05em;">QUALITY METRIC</div>
                        <i class="bi bi-arrow-repeat text-danger"></i>
                    </div>
                    <div class="kpi-label text-dark mb-1">Reopened Rate</div>
                    <div id="reopen-rate" class="h3 fw-bold mb-0 text-dark">0%</div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: 0%" id="reopen-progress"></div>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">Target: < 5%</small>
                </div>

                <div class="analytics-side-card p-3 flex-fill shadow-sm" style="background: linear-gradient(145deg, #f8fafc 0%, #ffffff 100%) !important; border-left: 4px solid #475569; border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="analytics-side-title text-white px-2 py-1 rounded" style="background: #334155; font-size: 0.6rem; letter-spacing: 0.05em;">TOTAL EVENTS</div>
                        <i class="bi bi-flag-fill text-secondary"></i>
                    </div>
                    <div class="kpi-label text-dark mb-1">Reopen Events</div>
                    <div id="reopen-events" class="h3 fw-bold mb-0 text-dark">0</div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">Total cases sent back</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
      <div class="col-12">
        <div class="section-card chart-card">
          <div class="analytics-chart-header">
            <div>
              <h2 class="text-lg font-bold text-foreground">Recurring / Reopened Issue Categories</h2>
              <p class="chart-subtitle">Category-level recurrence ranking for targeted remediation.</p>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Category</th>
                  <th>Affected Reports</th>
                  <th>Reopen Events</th>
                </tr>
              </thead>
              <tbody id="recurring-categories-body">
                <tr><td colspan="3" class="text-center text-muted-foreground">Loading…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    </section>

    <section class="analytics-panel hidden" data-tab-panel="trends">
      <div class="section-card section-accent-primary chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Trends &amp; Timeline</h2>
            <p class="chart-subtitle" id="subtitle-trend">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-8">
            <div class="chart-wrap h-100">
              <canvas id="chart-trend" height="320"></canvas>
            </div>
            <div class="insight-card hidden mt-3" id="insight-trend" role="status" aria-live="polite">
              <div class="insight-label">Insight</div>
              <p class="insight-text" id="caption-trend"></p>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <div class="analytics-side-stack">
              <div class="analytics-side-card">
                <div class="analytics-side-title">View</div>
                <select id="trend-mode" class="form-select form-select-sm w-100">
                  <option value="daily">Daily (Last 7 Days)</option>
                  <option value="weekly">Weekly (Last 4 Weeks)</option>
                  <option value="monthly">Monthly (Last 12 Months)</option>
                </select>
                <div class="text-sm text-muted-foreground mt-2">Switch time scale to compare patterns.</div>
              </div>

              <div class="analytics-side-card">
                <div class="analytics-side-title">SLA Overlay</div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" role="switch" id="trend-sla-overlay" checked>
                  <label class="form-check-label text-sm" for="trend-sla-overlay">Show SLA compliance overlay</label>
                </div>
                <span id="trend-sla-overlay-status" class="visually-hidden" aria-live="polite"></span>
                <div class="analytics-side-metric mt-2">
                  <div class="label">Compliance</div>
                  <div class="value" id="timeline-rate">N/A</div>
                </div>
              </div>

              <div class="analytics-side-card">
                <div class="analytics-side-title">SLA Snapshot</div>
                <div class="text-sm text-muted-foreground" id="subtitle-timeline">Loading…</div>
                <div class="d-flex justify-content-between"><span class="text-sm">Overall SLA</span><span id="sla-overall-rate" class="fw-bold">N/A</span></div>
                <div class="d-flex justify-content-between"><span class="text-sm">Applicable Cases</span><span id="sla-overall-applicable" class="fw-bold">0</span></div>
                <div class="d-flex justify-content-between"><span class="text-sm">Violations</span><span id="sla-overall-violations" class="fw-bold">0</span></div>
              </div>
            </div>
          </div>
        </div>
        <div class="insight-card hidden mt-3" id="insight-timeline" role="status" aria-live="polite">
          <div class="insight-label">Insight</div>
          <p class="insight-text" id="caption-timeline"></p>
        </div>
      </div>
    </section>

    <section class="analytics-panel hidden" data-tab-panel="departmental">
      <div class="section-card section-accent-info chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Departmental Insights</h2>
            <p class="chart-subtitle" id="subtitle-department">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-7">
            <div class="chart-wrap h-100">
              <canvas id="chart-department" height="320"></canvas>
            </div>
            <div class="insight-card hidden mt-3" id="insight-department" role="status" aria-live="polite">
              <div class="insight-label">Insight</div>
              <p class="insight-text" id="caption-department"></p>
            </div>
          </div>
          <div class="col-12 col-lg-5">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" id="dept-performance-table">
                <thead>
                  <tr>
                    <th><button type="button" class="btn btn-ghost btn-sm p-0" data-dept-sort="department">Department</button></th>
                    <th><button type="button" class="btn btn-ghost btn-sm p-0" data-dept-sort="total_reports">Total Reports</button></th>
                    <th><button type="button" class="btn btn-ghost btn-sm p-0" data-dept-sort="avg_resolution_days">Avg Resolution (days)</button></th>
                    <th><button type="button" class="btn btn-ghost btn-sm p-0" data-dept-sort="sla_compliance">SLA Compliance %</button></th>
                  </tr>
                </thead>
                <tbody id="dept-performance-body">
                  <tr><td colspan="4" class="text-center text-muted-foreground">Loading…</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div id="comparison-summary" class="mt-3 mb-3"></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Department</th>
                <th>This Period</th>
                <th>Previous Period</th>
                <th>Delta</th>
                <th>% Change</th>
              </tr>
            </thead>
            <tbody id="comparison-dept-body">
              <tr><td colspan="5" class="text-center text-muted-foreground">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="analytics-panel hidden" data-tab-panel="incident">
      <div class="section-card section-accent-warning chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Incident Analysis</h2>
            <p class="chart-subtitle" id="subtitle-severity">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch mb-3">
          <div class="col-12 col-lg-6">
            <div class="chart-wrap h-100">
              <canvas id="chart-severity" height="320"></canvas>
            </div>
            <div class="chart-legend mt-2" id="severity-legend"></div>
            <div class="insight-card hidden mt-3" id="insight-severity" role="status" aria-live="polite">
              <div class="insight-label">Insight</div>
              <p class="insight-text" id="caption-severity"></p>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="chart-wrap h-100 d-flex align-items-center justify-content-center">
              <canvas id="chart-resolution-type" height="320"></canvas>
            </div>
            <div id="resolution-breakdown-overall" class="mt-2"></div>
            <div id="resolution-breakdown-department" class="hidden"></div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Building</th>
                <th>Total Reports</th>
                <th>Avg Resolution (days)</th>
                <th>SLA Compliance %</th>
              </tr>
            </thead>
            <tbody id="building-analysis-body">
              <tr><td colspan="4" class="text-center text-muted-foreground">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="analytics-panel hidden" data-tab-panel="records">
      <div class="section-card chart-card">
        <div class="analytics-chart-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h2 class="text-lg font-bold text-foreground">Data Records</h2>
            <p class="chart-subtitle">Detailed report archive with advanced filtering from the controls above.</p>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('download-pdf')?.click()">Export to PDF</button>
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('download-csv')?.click()">Export to Excel</button>
            <div class="text-sm text-muted-foreground" id="detailed-pagination-label">Page 1</div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th><button type="button" class="btn btn-ghost btn-sm p-0" data-detail-sort="report_no">Report</button></th>
                <th><button type="button" class="btn btn-ghost btn-sm p-0" data-detail-sort="department">Department</button></th>
                <th><button type="button" class="btn btn-ghost btn-sm p-0" data-detail-sort="severity">Severity</button></th>
                <th><button type="button" class="btn btn-ghost btn-sm p-0" data-detail-sort="status">Status</button></th>
                <th><button type="button" class="btn btn-ghost btn-sm p-0" data-detail-sort="resolution_days">Resolution (days)</button></th>
                <th><button type="button" class="btn btn-ghost btn-sm p-0" data-detail-sort="sla_status">SLA</button></th>
              </tr>
            </thead>
            <tbody id="detailed-reports-body">
              <tr><td colspan="6" class="text-center text-muted-foreground">Loading…</td></tr>
            </tbody>
          </table>
        </div>
        <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-outline btn-sm" id="detailed-prev">Previous</button>
          <button type="button" class="btn btn-outline btn-sm" id="detailed-next">Next</button>
        </div>
      </div>
    </section>

  </div>
</main>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-filters-btn');
    const content = document.getElementById('filter-collapsible-content');
    const chevron = document.getElementById('filter-chevron');
    const statusText = document.getElementById('filter-status-text');

    if (toggleBtn && content) {
        // Function to toggle the filter state
        function toggleFilters(isManualAction = true) {
            const isCollapsed = content.classList.toggle('collapsed');
            chevron.classList.toggle('rotated', isCollapsed);
            statusText.textContent = isCollapsed ? 'Show Filters' : 'Hide Filters';
            
            if (isManualAction) {
                localStorage.setItem('analytics_filters_collapsed', isCollapsed);
            }
        }

        // Handle click event
        toggleBtn.addEventListener('click', () => toggleFilters(true));

        // Check localStorage to remember user's last preference
        const savedState = localStorage.getItem('analytics_filters_collapsed');
        if (savedState === 'true') {
            content.classList.add('collapsed');
            chevron.classList.add('rotated');
            statusText.textContent = 'Show Filters';
        }
    }
});
</script>
