<?php
// Variables are prepared by PrintReportController::show()
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars(
        'Incident Report ' . ($reportNo !== '' ? $reportNo : '#' . $reportId),
    ); ?></title>

    <!-- Base tokens (colors/typography) + print template -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_url('assets/css/style.css')); ?>" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_url('assets/css/print-report.css')); ?>" />
</head>
<body class="print-report-body">

<div class="screen-toolbar">
    <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print / Save as PDF</button>
    <button class="btn btn-outline btn-sm" type="button" onclick="window.close()">Close</button>
</div>

<div class="print-sheet memo-sheet" role="document" aria-label="Violation Report Memo">
    <header class="memo-header <?php echo $template === 'internal'
        ? 'memo-header--internal'
        : 'memo-header--external'; ?>">
        <div class="memo-header-row">
            <div class="memo-logo-wrap">
                <?php if ($logoUrl): ?>
                    <img class="memo-logo" src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Company Logo" />
                <?php endif; ?>
            </div>
            <div class="memo-header-text">
                <?php foreach ($headerLines as $line):

                    $cls = (string) ($line['class'] ?? '');
                    $txt = (string) ($line['text'] ?? '');
                    $parts = $line['parts'] ?? null;

                    if (is_array($parts)) {
                        $hasAny = false;
                        foreach ($parts as $p) {
                            if (trim((string) ($p['text'] ?? '')) !== '') {
                                $hasAny = true;
                                break;
                            }
                        }
                        if (!$hasAny) {
                            continue;
                        }
                    } else {
                        if (trim($txt) === '') {
                            continue;
                        }
                    }
                    ?>
                    <div class="<?php echo htmlspecialchars($cls); ?>"><?php if (is_array($parts)) {
    foreach ($parts as $p) {
        $pCls = trim((string) ($p['class'] ?? ''));
        $pTxt = (string) ($p['text'] ?? '');
        if ($pTxt === '') {
            continue;
        }
        if ($pCls !== '') {
            echo '<span class="' . htmlspecialchars($pCls) . '">' . htmlspecialchars($pTxt) . '</span>';
        } else {
            echo htmlspecialchars($pTxt);
        }
    }
} else {
    echo htmlspecialchars($txt);
} ?></div>
                <?php
                endforeach; ?>
            </div>
        </div>
    </header>

    <main class="memo-body">
        <div class="memo-fields">
            <div class="memo-field">
                <div class="memo-label">Date</div>
                <div class="memo-colon">:</div>
                <div class="memo-value"><?php echo htmlspecialchars($memoDate); ?></div>
            </div>
            <div class="memo-field">
                <div class="memo-label">To</div>
                <div class="memo-colon">:</div>
                <div class="memo-value">
                    <div class="memo-person"><?php echo htmlspecialchars($memoToName); ?></div>
                    <div class="memo-person-title"><?php echo htmlspecialchars($memoToTitle); ?></div>
                </div>
            </div>
            <div class="memo-field">
                <div class="memo-label">Thru</div>
                <div class="memo-colon">:</div>
                <div class="memo-value">
                    <?php if (empty($memoThru)): ?>
                        <div class="memo-person">—</div>
                    <?php else: ?>
                        <?php foreach ($memoThru as $p): ?>
                            <div class="memo-person"><?php echo htmlspecialchars((string) $p['name']); ?></div>
                            <?php if (trim((string) $p['title']) !== ''): ?>
                                <div class="memo-person-title"><?php echo htmlspecialchars(
                                    (string) $p['title'],
                                ); ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="memo-field memo-field-subject">
                <div class="memo-label">Subject</div>
                <div class="memo-colon">:</div>
                <div class="memo-value memo-subject"><?php echo htmlspecialchars($memoSubject); ?></div>
            </div>
        </div>

        <div class="memo-rule"></div>

        <div class="memo-section">
            <?php if (trim((string) $description) !== ''): ?>
                <div class="memo-section-title">Details:</div>
                <div class="memo-text"><?php echo nl2br(htmlspecialchars(trim((string) $description))); ?></div>
            <?php endif; ?>
        </div>

        <?php if (trim((string) $remarks) !== ''): ?>
            <div class="memo-section">
                <div class="memo-section-title">Remarks:</div>
                <div class="memo-text"><?php echo nl2br(htmlspecialchars(trim((string) $remarks))); ?></div>
            </div>
        <?php endif; ?>

        <?php if (trim((string) $actionTaken) !== ''): ?>
            <div class="memo-section">
                <div class="memo-section-title">Actions Taken:</div>
                <div class="memo-text"><?php echo nl2br(htmlspecialchars(trim((string) $actionTaken))); ?></div>
            </div>
        <?php endif; ?>

        <?php if (trim((string) $securityRemarks) !== ''): ?>
            <div class="memo-section">
                <div class="memo-section-title">Security Remarks:</div>
                <div class="memo-text"><?php echo nl2br(htmlspecialchars(trim((string) $securityRemarks))); ?></div>
            </div>
        <?php endif; ?>

        <?php if (trim((string) $gaStaffNotes) !== ''): ?>
            <div class="memo-section">
                <div class="memo-section-title">GA Staff Notes:</div>
                <div class="memo-text"><?php echo nl2br(htmlspecialchars(trim((string) $gaStaffNotes))); ?></div>
            </div>
        <?php endif; ?>

        <?php if (trim((string) $decisionLine) !== ''): ?>
            <div class="memo-section">
                <div class="memo-section-title">GA Manager Decision:</div>
                <div class="memo-text"><?php echo nl2br(htmlspecialchars((string) $decisionLine)); ?></div>
            </div>
        <?php endif; ?>

        <?php if (trim((string) $decNotes) !== ''): ?>
            <div class="memo-section">
                <div class="memo-section-title">GA Manager Notes:</div>
                <div class="memo-text"><?php echo nl2br(htmlspecialchars((string) $decNotes)); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($attachments)): ?>
            <div class="memo-section">
                <div class="memo-section-title">Reference:</div>
                <div class="memo-text">
                    <ol class="memo-ref-list">
                        <?php foreach ($attachments as $a):

                            $fn = (string) ($a['file_name'] ?? '');
                            $mime = (string) ($a['mime_type'] ?? '');
                            $uploadedAtFmt = (string) ($a['uploaded_at_fmt'] ?? '');
                            ?>
                            <li><?php
                            echo htmlspecialchars($fn !== '' ? $fn : 'Attachment');
                            if ($mime !== '') {
                                echo htmlspecialchars(' (' . $mime . ')');
                            }
                            if ($uploadedAtFmt !== '') {
                                echo htmlspecialchars(' — ' . $uploadedAtFmt);
                            }
                            ?></li>
                        <?php
                        endforeach; ?>
                    </ol>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($attachmentImageUrl): ?>
            <div class="memo-attached">See attached trip ticket for reference.</div>
            <div class="memo-attachment-box" aria-label="Attachment preview">
                <img class="memo-attachment-img" src="<?php echo htmlspecialchars(
                    $attachmentImageUrl,
                ); ?>" alt="Attachment" />
            </div>
        <?php endif; ?>

        <div class="memo-footer">
            <div class="memo-footer-note">For information and reference.</div>

            <div class="memo-prepared">
                <div class="memo-prepared-label">Prepared by:</div>
                <div class="memo-prepared-block">
                    <div class="memo-prepared-name"><?php echo htmlspecialchars($preparedByName); ?></div>
                    <div class="memo-prepared-title"><?php echo htmlspecialchars($preparedByTitle1); ?></div>
                    <div class="memo-prepared-title"><?php echo htmlspecialchars($preparedByTitle2); ?></div>
                </div>
                <div class="memo-prepared-date"><?php echo htmlspecialchars($memoFooterDate); ?></div>
            </div>
        </div>
    </main>
</div>

<script>
  window.addEventListener('load', () => {
    // Delay slightly to ensure layout is stable before printing
    setTimeout(() => {
      try { window.focus(); } catch (e) {}
      window.print();
    }, 250);
  });
</script>

</body>
</html>
