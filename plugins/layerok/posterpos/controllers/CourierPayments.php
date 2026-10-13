<?php namespace Layerok\PosterPos\Controllers;

use BackendAuth;
use BackendMenu;
use Backend\Classes\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Layerok\PosterPos\Classes\Couriers\CourierPayroll;
use Layerok\PosterPos\Classes\Delivery\OpenDeliveries;
use Layerok\PosterPos\Models\Courier;
use Layerok\PosterPos\Models\CourierExcludedReceipt;
use Layerok\PosterPos\Models\CourierTask;
use Layerok\PosterPos\Models\Spot;
use ValidationException;

/**
 * Courier pay per day: closed Poster receipts and tasks (Довоз / Закупка /
 * Магазин) are counted automatically, the admin types in the km, and closing the
 * day freezes the result. The arithmetic lives in CourierPayroll.
 */
class CourierPayments extends Controller
{
    public $requiredPermissions = ['layerok.posterpos.courier_payments'];

    private ?CourierPayroll $payroll = null;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Layerok.PosterPos', 'delivery', 'delivery-payments');
    }

    public function index()
    {
        $this->pageTitle = 'Расчёт курьеров';

        $this->addJs($this->asset('js/courier-payments.js'), ['defer' => true]);
        $this->addCss($this->asset('css/courier-payments.css'));

        $this->vars['today'] = OpenDeliveries::now()->toDateString();
        $this->vars['spots'] = Spot::where('published', true)->whereNotNull('poster_id')->orderBy('name')->get();
        $this->vars['taskTypes'] = CourierTask::TYPES;
        $this->vars['pricePerDelivery'] = CourierPayroll::PRICE_PER_DELIVERY;
    }

    public function onLoadDay()
    {
        $date = $this->date();

        try {
            $day = $this->payroll()->day($date);
        } catch (\Throwable $e) {
            Log::error('Courier payments failed to load', ['date' => $date, 'error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }

        $day['rows'] = $this->filterBySpot($day['rows'], post('spot_id'));

        $day['summary'] = [
            'base' => round(array_sum(array_column($day['rows'], 'base')), 2),
            'km_pay' => round(array_sum(array_column($day['rows'], 'km_pay')), 2),
            'total' => round(array_sum(array_column($day['rows'], 'total')), 2),
            'deliveries' => array_sum(array_column($day['rows'], 'deliveries')),
            'open_receipts' => array_sum(array_column($day['rows'], 'open_receipts')),
        ];

        return $day;
    }

    public function onSaveKm()
    {
        $date = $this->openDate();
        $km = post('km');

        if (!is_numeric($km) || (float) $km < 0) {
            throw new ValidationException(['km' => 'Километраж должен быть числом не меньше нуля.']);
        }

        $this->payroll()->saveKm($date, $this->courier()->id, (float) $km);

        return $this->onLoadDay();
    }

    public function onAddTask()
    {
        $date = $this->openDate();
        $courier = $this->courier();

        $time = trim((string) post('time'));
        $performedAt = preg_match('/^\d{1,2}:\d{2}$/', $time)
            ? Carbon::parse($date . ' ' . $time, OpenDeliveries::TIMEZONE)
            : $this->defaultTaskTime($date);

        $spot = post('spot_id') ? Spot::find(post('spot_id')) : null;

        $task = new CourierTask([
            'courier_id' => $courier->id,
            'spot_id' => $spot ? $spot->id : null,
            'type' => post('type'),
            'details' => trim((string) post('details')) ?: null,
            'comment' => trim((string) post('comment')) ?: null,
            'performed_at' => $performedAt->utc(),
            'source' => CourierTask::SOURCE_ADMIN,
            'created_by' => $this->userId(),
        ]);
        $task->save();

        return $this->onLoadDay();
    }

    public function onDeleteTask()
    {
        $task = CourierTask::findOrFail((int) post('task_id'));
        $this->guardOpen($task->localDate());

        $task->delete();

        return $this->onLoadDay();
    }

    public function onExcludeReceipt()
    {
        $date = $this->openDate();

        CourierExcludedReceipt::firstOrCreate(
            ['transaction_id' => (int) post('transaction_id')],
            [
                'date' => $date,
                'courier_id' => post('courier_id') ? (int) post('courier_id') : null,
                'excluded_by' => $this->userId(),
            ]
        );

        return $this->onLoadDay();
    }

    public function onRestoreReceipt()
    {
        $excluded = CourierExcludedReceipt::where('transaction_id', (int) post('transaction_id'))->first();

        if ($excluded) {
            $this->guardOpen((string) $excluded->date);
            $excluded->delete();
        }

        return $this->onLoadDay();
    }

    public function onCloseDay()
    {
        $date = $this->openDate();
        $paid = $this->payroll()->close($date, $this->userId());

        if (!$paid) {
            throw new ValidationException(['date' => 'За этот день нет ни доставок, ни километража — закрывать нечего.']);
        }

        return $this->onLoadDay();
    }

    public function onReopenDay()
    {
        $this->payroll()->reopen($this->date());

        return $this->onLoadDay();
    }

    /**
     * CSV of the day, one line per delivery plus a total line per courier.
     * Opens in Excel; the BOM keeps Cyrillic readable there.
     */
    public function export()
    {
        $date = $this->date(get('date'));
        $day = $this->payroll()->day($date);

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Дата', 'Курьер', 'Тип', 'Номер', 'Время', 'Адрес / что', 'Комментарий', 'Учтено'], ';');

        foreach ($day['rows'] as $row) {
            foreach ($row['items'] as $item) {
                fputcsv($out, [
                    $date,
                    $row['name'],
                    $item['type'],
                    $item['id'],
                    $item['time'],
                    $this->csvSafe($item['title']),
                    $this->csvSafe($item['note']),
                    $item['excluded'] ? 'нет' : 'да',
                ], ';');
            }

            fputcsv($out, [
                $date,
                $row['name'],
                'ИТОГО',
                $row['deliveries'] . ' доставок',
                '',
                $row['km'] . ' км × ' . $row['rate_per_km'] . ' ₴',
                $row['base'] . ' + ' . $row['km_pay'],
                $row['total'],
            ], ';');
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="couriers-' . $date . '.csv"',
        ]);
    }

    /**
     * Details and comments are free text that couriers will type in the app; a
     * value starting with = + - @ would run as a formula when Excel opens it.
     */
    private function csvSafe($value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    /**
     * The spot picker narrows the list to couriers who worked there. Their
     * totals still cover every spot, since pay is per courier per day.
     */
    private function filterBySpot(array $rows, $spotId): array
    {
        $spot = $spotId ? Spot::find($spotId) : null;

        if (!$spot) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) use ($spot) {
            foreach ($row['items'] as $item) {
                if (($item['spot_poster_id'] ?? null) === (int) $spot->poster_id) {
                    return true;
                }

                // A task without a spot (say, a purchase for the business as a
                // whole) shows up whichever spot is picked.
                if ($item['kind'] === 'task' && (!$item['spot_id'] || (int) $item['spot_id'] === (int) $spot->id)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * A task added for today gets the current time; one added for a past day
     * goes to noon, since the real time is unknown.
     */
    private function defaultTaskTime(string $date): Carbon
    {
        $now = OpenDeliveries::now();

        return $now->toDateString() === $date
            ? $now
            : Carbon::parse($date . ' 12:00', OpenDeliveries::TIMEZONE);
    }

    private function date($value = null): string
    {
        $value = $value ?? post('date');

        if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
            return OpenDeliveries::now()->toDateString();
        }

        return (string) $value;
    }

    /**
     * The posted date, refused if the day is already closed. Every handler that
     * changes money goes through here, not just the buttons in the page.
     */
    private function openDate(): string
    {
        $date = $this->date();
        $this->guardOpen($date);

        return $date;
    }

    private function guardOpen(string $date): void
    {
        if ($this->payroll()->isClosed($date)) {
            throw new ValidationException(['date' => 'День ' . date('d.m.Y', strtotime($date)) . ' уже закрыт. Сначала откройте его заново.']);
        }
    }

    private function courier(): Courier
    {
        return Courier::findOrFail((int) post('courier_id'));
    }

    private function userId(): ?int
    {
        $user = BackendAuth::getUser();

        return $user ? (int) $user->id : null;
    }

    private function payroll(): CourierPayroll
    {
        return $this->payroll ?: ($this->payroll = new CourierPayroll());
    }

    /**
     * Same cache-busting asset helper as DeliveryMap; see the note there on why
     * these live in the plugin's own assets directory.
     */
    private function asset(string $relative): string
    {
        $path = '/plugins/layerok/posterpos/assets/' . $relative;
        $file = base_path(ltrim($path, '/'));

        return $path . (file_exists($file) ? '?v=' . filemtime($file) : '');
    }
}
