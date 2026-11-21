<?php

/**
 * Reports: choose a report, filters and columns, preview it, then download
 * as Excel (.xlsx) or open the print-ready page to save as PDF.
 */
class ReportController extends Controller
{
    private const PREVIEW_ROWS = 15;

    public function index(): void
    {
        AuthController::requireLogin();

        $type = Report::type($_GET['type'] ?? '');
        $filters = Report::filters($type, $_GET);
        $cols = Report::pickColumns($type, $_GET['cols'] ?? null);
        $hasQuery = isset($_GET['preview']);

        $this->page('reports/index', [
            'pageTitle' => 'Reports',
            'pageSubtitle' => 'Download appointments, clients and inquiries as Excel or PDF.',
            'active' => 'reports',
            'type' => $type,
            'filters' => $filters,
            'cols' => $cols,
            'preview' => $hasQuery ? Report::build($type, $filters, $cols, self::PREVIEW_ROWS) : null,
            'previewLimit' => self::PREVIEW_ROWS,
        ]);
    }

    public function export(): void
    {
        AuthController::requireLogin();

        $type = Report::type($_GET['type'] ?? '');
        $filters = Report::filters($type, $_GET);
        $cols = Report::pickColumns($type, $_GET['cols'] ?? null);
        $format = ($_GET['format'] ?? '') === 'pdf' ? 'pdf' : 'xlsx';
        $report = Report::build($type, $filters, $cols);
        $generated = date('d M Y, h:i A');

        if ($format === 'pdf') {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
            $this->render('reports/print', ['report' => $report, 'generated' => $generated, 'autoPrint' => true]);
            return;
        }

        $meta = [APP_NAME . ' – ' . $report['title'], 'Generated: ' . $generated . ' · Records: ' . $report['count']];
        if ($report['filters']) {
            $meta[] = 'Filters: ' . implode(' | ', $report['filters']);
        }
        $kinds = array_map(static fn ($k) => in_array($k, ['money', 'count'], true) ? $k : 'text', $report['kinds']);
        $file = Xlsx::build($report['title'], $meta, $report['headers'], $kinds, $report['rows'], $report['totals']);

        $name = $type . '-report-' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($file));
        header('Cache-Control: no-store');
        echo $file;
    }
}
