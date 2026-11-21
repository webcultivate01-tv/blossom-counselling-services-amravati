<?php
/**
 * Shared admin screens for inquiries and appointments:
 * list (search / filter / paginate), detail, status change, notes, delete.
 */
abstract class LeadController extends Controller
{
    /** @var class-string<LeadModel> */
    protected string $model;
    protected string $key;      // singular route name: inquiry | appointment
    protected string $plural;   // list route name: inquiries | appointments
    protected string $title;    // "Inquiry Management"
    protected string $subtitle;
    protected string $noun;     // "inquiry" | "appointment" (for messages)

    private const FILTER_KEYS = ['q', 'status', 'sort', 'service', 'type', 'page'];

    public function index(): void
    {
        AuthController::requireLogin();

        $filters = $this->filters();
        $model = $this->model;
        $result = $model::paginate($filters, max(1, (int) ($_GET['page'] ?? 1)));

        $this->page('leads/index', [
            'pageTitle' => $this->title,
            'pageSubtitle' => $this->subtitle,
            'active' => $this->plural,
            'cfg' => $this->cfg(),
            'filters' => $filters,
            'result' => $result,
            'counts' => $model::counts(),
            'listQuery' => $this->listQuery($filters + ['page' => $result['page']]),
        ]);
    }

    public function show(): void
    {
        AuthController::requireLogin();

        $row = $this->findOrBack();
        $this->page('leads/show', [
            'pageTitle' => $this->title,
            'pageSubtitle' => $this->subtitle,
            'active' => $this->plural,
            'cfg' => $this->cfg(),
            'row' => $row,
        ]);
    }

    public function status(): void
    {
        $this->guardPost($this->listUrl());
        $model = $this->model;
        $row = $this->findOrBack('POST');

        $status = (string) ($_POST['status'] ?? '');
        if (!$model::isStatus($status)) {
            flash_set('error', 'Please choose a valid status.');
        } else {
            $model::setStatus((int) $row['id'], $status);
            flash_set('success', ucfirst($this->noun) . ' marked as ' . $model::STATUSES[$status] . '.');
        }
        $this->backAfterAction((int) $row['id']);
    }

    public function notes(): void
    {
        $this->guardPost($this->listUrl());
        $model = $this->model;
        $row = $this->findOrBack('POST');

        $model::setNotes((int) $row['id'], mb_substr(trim((string) ($_POST['admin_notes'] ?? '')), 0, 3000));
        flash_set('success', 'Notes saved.');
        $this->redirect('index.php?action=' . $this->key . '&id=' . (int) $row['id']);
    }

    public function delete(): void
    {
        $this->guardPost($this->listUrl());
        $model = $this->model;
        $row = $this->findOrBack('POST');

        $model::delete((int) $row['id']);
        flash_set('success', ucfirst($this->noun) . ' deleted.');
        $this->redirect($this->listUrl($_POST['qs'] ?? ''));
    }

    /* ---------------- helpers ---------------- */

    protected function cfg(): array
    {
        $model = $this->model;
        return [
            'key' => $this->key,
            'plural' => $this->plural,
            'noun' => $this->noun,
            'statuses' => $model::STATUSES,
        ];
    }

    protected function filters(): array
    {
        $model = $this->model;
        $status = (string) ($_GET['status'] ?? '');
        return [
            'q' => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100),
            'status' => $model::isStatus($status) ? $status : '',
            'sort' => ($_GET['sort'] ?? '') === 'old' ? 'old' : 'new',
            'service' => (string) ($_GET['service'] ?? ''),
        ];
    }

    /** Query string (without action) that preserves the current list filters. */
    protected function listQuery(array $filters): string
    {
        $keep = [];
        foreach (self::FILTER_KEYS as $k) {
            $v = $filters[$k] ?? '';
            if ($v !== '' && $v !== null && !($k === 'sort' && $v === 'new') && !($k === 'page' && (int) $v <= 1)) {
                $keep[$k] = $v;
            }
        }
        return http_build_query($keep);
    }

    protected function listUrl(string $qs = ''): string
    {
        // Re-parse so only known filter keys can ever be carried over.
        parse_str($qs, $parsed);
        $clean = [];
        foreach (self::FILTER_KEYS as $k) {
            if (isset($parsed[$k]) && is_string($parsed[$k])) {
                $clean[$k] = $parsed[$k];
            }
        }
        $q = http_build_query($clean);
        return 'index.php?action=' . $this->plural . ($q !== '' ? '&' . $q : '');
    }

    protected function findOrBack(string $source = 'GET'): array
    {
        $model = $this->model;
        $id = $this->intParam('id', $source);
        $row = $id > 0 ? $model::find($id) : false;

        if (!$row) {
            flash_set('error', ucfirst($this->noun) . ' not found.');
            $this->redirect($this->listUrl());
        }
        return $row;
    }

    private function backAfterAction(int $id): void
    {
        if (($_POST['return'] ?? '') === 'show') {
            $this->redirect('index.php?action=' . $this->key . '&id=' . $id);
        }
        $this->redirect($this->listUrl($_POST['qs'] ?? ''));
    }
}
