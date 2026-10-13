<?php namespace Layerok\PosterPos\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Layerok\PosterPos\Classes\Delivery\DeliveryLocations;
use Layerok\PosterPos\Classes\Delivery\OpenDeliveries;
use Illuminate\Support\Facades\Log;
use Layerok\PosterPos\Models\Area;
use Layerok\PosterPos\Models\Courier;
use Layerok\PosterPos\Models\CourierState;
use Layerok\PosterPos\Models\OnlineOrder;
use Layerok\PosterPos\Models\Spot;

/**
 * Live delivery map. Open delivery receipts are read from Poster; the zones are
 * the same polygons the checkout uses to decide which spot serves an address.
 */
class DeliveryMap extends Controller
{
    /**
     * Show online orders the register has not accepted yet, alongside the open
     * receipts. Off for now; the whole path is kept so it is one flag to revive.
     *
     * Those orders are not transactions, so they come from
     * incomingOrders.getIncomingOrders instead of dash.getTransactions and carry
     * neither a total nor coordinates — see presentPendingOrder() for the detail.
     */
    private const SHOW_PENDING_ACCEPT = false;

    private ?DeliveryLocations $locations = null;

    private ?OpenDeliveries $deliveries = null;

    public $requiredPermissions = ['layerok.posterpos.delivery_map'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Layerok.PosterPos', 'delivery', 'delivery-map');
    }

    public function index()
    {
        $this->pageTitle = 'Карта заказов';

        $this->addJs('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');
        $this->addCss('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        $this->addJs($this->asset('js/delivery-map.js'), ['defer' => true]);
        $this->addCss($this->asset('css/delivery-map.css'));

        $this->vars['spots'] = Spot::where('published', true)->orderBy('name')->get();
        $this->vars['today'] = OpenDeliveries::now()->toDateString();
        // Set when arriving from the operator overview: a spot id, or "all". Without
        // it the first spot stays selected, as before.
        $this->vars['selectedSpot'] = get('spot');
        $this->vars['showPendingAccept'] = self::SHOW_PENDING_ACCEPT;
    }

    /**
     * Delivery zones for the map overlay. The zones cover the whole city rather
     * than one spot, so they are not filtered — only the orders are.
     */
    /**
     * Asset URL stamped with the file's modified time, so an edited stylesheet is
     * never served from the browser cache after a deploy.
     *
     * These live in the plugin's own assets directory on purpose: october:mirror
     * only symlinks plugins/[vendor]/[plugin]/assets into the public folder, so anything
     * under controllers/[name]/assets is a 404 on a mirrored deployment.
     */
    private function asset(string $relative): string
    {
        $path = '/plugins/layerok/posterpos/assets/' . $relative;
        $file = base_path(ltrim($path, '/'));

        return $path . (file_exists($file) ? '?v=' . filemtime($file) : '');
    }

    public function onLoadAreas()
    {
        return [
            'areas' => Area::all()->map(fn(Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
                'color' => $area->color,
                'coords' => $area->coords,
            ])->values(),
        ];
    }

    /**
     * Open delivery receipts for one spot and date, straight from Poster.
     *
     * Only receipts still open are returned, so a date in the past shows what was
     * left hanging rather than everything that was ever delivered.
     */
    public function onLoadOrders()
    {
        $spot = Spot::find(post('spot_id'));
        $date = post('date') ?: OpenDeliveries::now()->toDateString();
        $spotPosterId = $spot && $spot->poster_id ? (int) $spot->poster_id : null;

        try {
            $transactions = $this->deliveries()->openTransactions($date, $date, $spotPosterId);
            $pending = self::SHOW_PENDING_ACCEPT
                ? $this->deliveries()->unacceptedOrders($date, $date, $spotPosterId)
                : collect();
            $couriers = $this->deliveries()->courierNames();
        } catch (\Throwable $e) {
            Log::error('Delivery map failed to load orders', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'orders' => []];
        }

        $streets = $transactions
            ->map(fn($transaction) => $this->locations()->streetOf((object) (((object) $transaction)->delivery ?? null)))
            ->merge($pending->map(fn($order) => $this->locations()->streetFromLine(((object) $order)->address ?? '')));

        $coords = $this->locations()->streetCoordinates($streets);

        $current = $this->currentOrders();

        $orders = $transactions
            ->map(fn($transaction) => $this->presentOrder((object) $transaction, $couriers, $coords))
            ->map(fn(array $order) => $order + [
                // The courier app's «Везу сейчас»: the order on its way that this
                // courier is driving to right now.
                'is_current' => $order['processing_status'] === 40
                    && $order['courier_id']
                    && ($current[$order['id']] ?? null) === $order['courier_id'],
            ])
            ->merge($pending->map(fn($order) => $this->presentPendingOrder((object) $order, $coords) + ['is_current' => false]))
            ->values();

        return [
            'orders' => $orders,
            'couriers' => Courier::active()->whereNotNull('poster_user_id')->orderBy('name')
                ->get(['poster_user_id', 'name'])
                ->map(fn(Courier $c) => ['id' => (int) $c->poster_user_id, 'name' => $c->name])
                ->values(),
        ];
    }

    /**
     * An unaccepted order carries no delivery record and no total, so the amount
     * is taken from our own copy of the order where we have one.
     */
    private function presentPendingOrder(object $order, array $coords): array
    {
        $line = (string) ($order->address ?? '');
        $street = $this->locations()->streetFromLine($line);
        $point = $street !== null && isset($coords[$street])
            ? $coords[$street] + ['source' => 'street']
            : ['lat' => null, 'lng' => null, 'source' => null];

        $client = collect([$order->first_name ?? null, $order->last_name ?? null])
            ->filter(fn($part) => trim((string) $part) !== '')
            ->join(' ');

        $local = OnlineOrder::where('poster_id', $order->incoming_order_id ?? null)->first();

        return [
            'id' => (string) ($order->incoming_order_id ?? ''),
            'key' => 'incoming:' . ($order->incoming_order_id ?? ''),
            'pending_accept' => true,
            'client' => $client ?: '—',
            'phone' => $order->phone ?? '',
            'amount' => $local ? (int) round(((int) $local->total) / 100) : null,
            'address' => $line,
            'place' => '',
            'comment' => trim((string) ($order->comment ?? '')),
            'due' => !empty($order->created_at) ? date('H:i', strtotime($order->created_at)) : '',
            'lat' => $point['lat'],
            'location_source' => $point['source'] ?? null,
            'lng' => $point['lng'],
            'courier_id' => null,
            'courier' => null,
            'processing_status' => 0,
        ];
    }

    private function presentOrder(object $transaction, array $couriers, array $coords = []): array
    {
        $delivery = (object) ($transaction->delivery ?? []);
        $courierId = OpenDeliveries::courierIdOf($transaction);

        $point = $this->locations()->resolvePoint($delivery, $coords);

        return [
            'id' => (string) ($transaction->transaction_id ?? ''),
            'key' => 'transaction:' . ($transaction->transaction_id ?? ''),
            'pending_accept' => false,
            'client' => OpenDeliveries::clientName($transaction->client_firstname ?? null, $transaction->client_lastname ?? null),
            'phone' => $transaction->client_phone ?? '',
            'amount' => OpenDeliveries::amountOf($transaction),
            'address' => OpenDeliveries::addressOf($transaction),
            'place' => $delivery->comment ?? '',
            'comment' => trim((string) ($transaction->transaction_comment ?? '')),
            'due' => !empty($delivery->delivery_time) ? date('H:i', strtotime($delivery->delivery_time)) : '',
            'lat' => $point['lat'],
            'location_source' => $point['source'] ?? null,
            'lng' => $point['lng'],
            'courier_id' => $courierId,
            'courier' => OpenDeliveries::courierLabel($courierId, $couriers),
            'processing_status' => (int) ($transaction->processing_status ?? 0),
        ];
    }

    /**
     * Each courier's current order as the courier app last reported it:
     * transaction id => the courier's Poster employee id.
     */
    private function currentOrders(): array
    {
        return CourierState::with('courier')
            ->whereNotNull('current_transaction_id')
            ->get()
            ->filter(fn(CourierState $s) => $s->courier && $s->courier->poster_user_id)
            ->mapWithKeys(fn(CourierState $s) => [
                (string) $s->current_transaction_id => (int) $s->courier->poster_user_id,
            ])
            ->all();
    }

    private function deliveries(): OpenDeliveries
    {
        return $this->deliveries ?: ($this->deliveries = new OpenDeliveries());
    }

    private function locations(): DeliveryLocations
    {
        return $this->locations ?: ($this->locations = new DeliveryLocations());
    }
}
