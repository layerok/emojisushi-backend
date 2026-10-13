(function () {
    'use strict';

    var REFRESH_MS = 30000;

    var timer = null, loading = false, screen = null;

    function load(silent) {
        if (loading) {
            return;
        }

        loading = true;

        if (!silent) {
            $('#opSpots, #opEvents, #opLate').html('<div class="op-empty">Загрузка…</div>');
        }

        $.request('onLoadOverview', {
            data: { city_id: $('#opCity').val() },
            complete: function () { loading = false; },
            success: function (data) {
                var error = $('#opError');

                if (data.error) {
                    error.text('Poster недоступен: ' + data.error).prop('hidden', false);
                } else {
                    error.prop('hidden', true);
                }

                renderSpots(data.spots || []);
                renderEvents(data.events || []);
                renderLate(data.late || [], data.late_after_minutes);
                renderSummary(data.summary || {});
                renderUnaccepted(data.unaccepted || []);
                $('#opUpdated').text('обновлено ' + (data.updated_at || ''));
            },
            error: function () {
                if (!silent) {
                    $('#opSpots').html('<div class="op-empty">Не удалось загрузить данные</div>');
                }
            }
        });
    }

    function renderSpots(spots) {
        if (!spots.length) {
            $('#opSpots').html('<div class="op-empty">Нет заведений</div>');
            return;
        }

        var mapUrl = $('.operators-screen').data('map-url');

        $('#opSpots').html(spots.map(function (s) {
            return '<a class="op-spot' + (s.alert ? ' alert' : '') + '" href="' + escapeHtml(mapUrl + '?spot=' + s.id) + '">' +
                (s.alert ? '<span class="op-spot-alert"></span>' : '') +
                '<div class="op-spot-title">' + escapeHtml(s.name) + '</div>' +
                '<div class="op-spot-city">' + escapeHtml(s.city) + '</div>' +
                '<div class="op-metrics">' +
                    metric(s.open, 'Открыто') +
                    metric(s.no_courier, 'Без курьера', s.no_courier ? 'warn' : '') +
                    metric(s.late, 'Просрочено', s.late ? 'bad' : '') +
                    metric(s.unaccepted, 'Не принято', s.unaccepted ? 'pending' : '') +
                '</div>' +
            '</a>';
        }).join(''));
    }

    function metric(value, label, tone) {
        return '<div class="op-metric"><b class="' + (tone || '') + '">' + value + '</b><span>' + label + '</span></div>';
    }

    function renderEvents(events) {
        if (!events.length) {
            $('#opEvents').html('<div class="op-empty">Событий пока нет. Они появляются по мере прихода вебхуков Poster.</div>');
            return;
        }

        $('#opEvents').html(events.map(function (e) {
            return '<div class="op-event">' +
                '<div class="op-event-time">' + escapeHtml(e.time) + '</div>' +
                '<div class="op-event-spot">' + escapeHtml(e.spot) + '</div>' +
                '<div class="op-event-desc"><b>' + escapeHtml(e.title) + '</b>' +
                    (e.detail ? '<span>' + escapeHtml(e.detail) + '</span>' : '') + '</div>' +
                '<div>' + (e.badge ? '<span class="op-pill ' + escapeHtml(e.tone) + '">' + escapeHtml(e.badge) + '</span>' : '') + '</div>' +
            '</div>';
        }).join(''));
    }

    function renderLate(late, threshold) {
        $('#opLateCount').text(late.length);

        if (!late.length) {
            $('#opLate').html('<div class="op-empty">Просроченных заказов нет</div>');
            return;
        }

        $('#opLate').html(late.map(function (o) {
            return '<div class="op-late">' +
                '<div class="op-late-top"><span class="op-late-num">#' + escapeHtml(o.id) + ' · ' + escapeHtml(o.spot) + '</span>' +
                    '<span class="op-late-time">+' + o.late_minutes + ' мин</span></div>' +
                '<div class="op-late-address">' + escapeHtml(o.address || '—') + '</div>' +
                '<div class="op-late-meta"><span>Доставить до ' + escapeHtml(o.due) + '</span><span>' + escapeHtml(o.client) + '</span></div>' +
                '<div class="op-late-courier' + (o.courier ? '' : ' none') + '">' +
                    (o.courier ? escapeHtml(o.courier) : 'Курьер не назначен') + '</div>' +
            '</div>';
        }).join(''));
    }

    function renderSummary(s) {
        $('#opSummary').html(
            line('Всего открытых чеков', s.open) +
            line('Без курьера', s.no_courier, 'warn') +
            line('С назначенным курьером', s.with_courier, 'ok') +
            line('Просрочено', s.late, 'bad')
        );
    }

    function line(label, value, tone) {
        return '<div class="op-line"><span>' + label + '</span><strong class="' + (tone || '') + '">' +
            (value === null || value === undefined ? '—' : value) + '</strong></div>';
    }

    function renderUnaccepted(orders) {
        $('#opUnacceptedCount').text(orders.length);

        if (!orders.length) {
            $('#opUnaccepted').html('<div class="op-empty">Все онлайн-заказы приняты</div>');
            return;
        }

        $('#opUnaccepted').html(orders.map(function (o) {
            return '<div class="op-pending">' +
                '<div class="op-late-top"><span class="op-late-num">#' + escapeHtml(o.id) + ' · ' + escapeHtml(o.spot) + '</span>' +
                    (o.waiting_minutes !== null ? '<span class="op-pending-time">ждёт ' + o.waiting_minutes + ' мин</span>' : '') + '</div>' +
                '<div class="op-late-address">' + escapeHtml(o.address || '—') + '</div>' +
                '<div class="op-late-meta"><span>' + escapeHtml(o.client) + '</span><span>' + escapeHtml(o.phone) + '</span></div>' +
            '</div>';
        }).join(''));
    }

    function escapeHtml(value) {
        var str = String(value === null || value === undefined ? '' : value);

        return str.replace(/[&<>"']/g, function (c) {
            if (c === '&') { return '&amp;'; }
            if (c === '<') { return '&lt;'; }
            if (c === '>') { return '&gt;'; }
            if (c === '"') { return '&quot;'; }
            return '&#39;';
        });
    }

    function restartTimer() {
        if (timer) { clearInterval(timer); }
        timer = setInterval(function () { load(true); }, REFRESH_MS);
    }

    /**
     * The backend swaps pages over AJAX, so this runs again on every navigation;
     * the timer must stop once the screen is gone.
     */
    function init() {
        var el = document.querySelector('.operators-screen');

        if (!el) {
            if (timer) { clearInterval(timer); timer = null; }
            screen = null;
            return;
        }

        // "render" also fires after every AJAX request, including our own.
        if (el === screen) {
            return;
        }

        screen = el;
        load(false);
        restartTimer();
    }

    $(document)
        .on('click', '#opRefresh', function () {
            load(false);
            restartTimer();
        })
        .on('change', '#opCity', function () {
            load(false);
            restartTimer();
        });

    $(document).ready(init);
    $(document).on('render page:loaded page:updated', init);
}());
