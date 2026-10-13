(function () {
    'use strict';

    var day = null, expanded = null, loading = false, screen = null;

    function params(extra) {
        return $.extend({ date: $('#cpDate').val(), spot_id: $('#cpSpot').val() }, extra || {});
    }

    /**
     * Every handler answers with the whole day, so one renderer serves them all.
     * Validation errors (closed day, bad km) are shown by the framework's flash.
     */
    function call(handler, extra) {
        $.request(handler, {
            data: params(extra),
            success: function (data) { render(data); }
        });
    }

    function load(silent) {
        if (loading) { return; }

        loading = true;

        if (!silent) {
            $('#cpRows').html('<tr><td colspan="8" class="cp-empty">Загрузка…</td></tr>');
        }

        $.request('onLoadDay', {
            data: params(),
            complete: function () { loading = false; },
            success: function (data) { render(data); },
            error: function () {
                $('#cpRows').html('<tr><td colspan="8" class="cp-empty">Не удалось загрузить расчёт</td></tr>');
            }
        });
    }

    function render(data) {
        var error = $('#cpError');

        if (data.error) {
            error.text('Poster недоступен: ' + data.error).prop('hidden', false);
            $('#cpRows').html('<tr><td colspan="8" class="cp-empty">Нет данных</td></tr>');
            return;
        }

        error.prop('hidden', true);
        day = data;

        renderState();
        renderWarnings();
        renderRows();
        renderSummary();
    }

    function renderState() {
        $('#cpState')
            .text(day.closed ? 'День закрыт ' + (day.closed_at || '') : 'День открыт')
            .toggleClass('closed', !!day.closed);
        $('#cpClose').prop('hidden', !!day.closed);
        $('#cpReopen').prop('hidden', !day.closed);
        $('#cpExport').attr('href', $('.courier-payments-screen').data('export-url') + '?date=' + encodeURIComponent($('#cpDate').val()));
    }

    function renderWarnings() {
        var html = '';

        (day.unmapped || []).forEach(function (u) {
            html += warning('Сотрудник Poster ID ' + u.poster_user_id + ': ' + u.receipts + ' ' + plural(u.receipts, 'чек', 'чека', 'чеков') +
                ' не попадают в расчёт — этот сотрудник не привязан ни к одному курьеру. Привяжите его в разделе «Курьеры».');
        });

        if (day.no_courier) {
            html += warning(day.no_courier + ' ' + plural(day.no_courier, 'закрытый чек', 'закрытых чека', 'закрытых чеков') +
                ' доставки без курьера — их никому не засчитали.');
        }

        if (day.summary && day.summary.open_receipts) {
            html += warning('Ещё открыто ' + day.summary.open_receipts + ' ' + plural(day.summary.open_receipts, 'чек', 'чека', 'чеков') +
                ' с назначенным курьером. Они попадут в расчёт только после закрытия в Poster.');
        }

        $('#cpWarnings').html(html);
    }

    function warning(text) {
        return '<div class="cp-warning">' + escapeHtml(text) + '</div>';
    }

    function renderRows() {
        if (!day.rows.length) {
            $('#cpRows').html('<tr><td colspan="8" class="cp-empty">' +
                (day.closed ? 'В закрытом дне нет выплат' : 'Нет курьеров с доставками за этот день') + '</td></tr>');
            return;
        }

        $('#cpRows').html(day.rows.map(function (r) {
            var open = expanded === r.courier_id;
            var html = '<tr class="cp-row' + (open ? ' expanded' : '') + '">' +
                '<td><div class="cp-courier">' +
                    '<button type="button" class="cp-expand' + (open ? ' open' : '') + '" data-courier="' + r.courier_id + '">⌄</button>' +
                    '<span class="cp-avatar">' + escapeHtml(String(r.name).charAt(0)) + '</span>' +
                    '<b>' + escapeHtml(r.name) + '</b>' +
                    (r.open_receipts ? '<span class="cp-open-badge" title="Открытые чеки с этим курьером">+' + r.open_receipts + ' откр.</span>' : '') +
                '</div></td>' +
                '<td><b>' + r.receipts + '</b></td>' +
                '<td><b>' + r.tasks + '</b></td>' +
                '<td><b>' + r.deliveries + '</b></td>' +
                '<td>' + money(r.base) + '</td>' +
                '<td><input type="number" min="0" step="0.1" class="cp-km" data-courier="' + r.courier_id + '" value="' + r.km + '"' +
                    (day.closed ? ' disabled' : '') + '></td>' +
                '<td>' + money(r.rate_per_km) + '</td>' +
                '<td class="cp-total">' + money(r.total) + '</td>' +
            '</tr>';

            if (open) {
                html += '<tr class="cp-detail-row"><td colspan="8">' + ledger(r) + '</td></tr>';
            }

            return html;
        }).join(''));
    }

    function ledger(r) {
        var head = '<div class="cp-detail-head"><b>Все доставки · ' + escapeHtml(r.name) + '</b>' +
            '<span>' + (day.closed ? 'День закрыт — список зафиксирован' : 'Изменения сразу пересчитывают выплату') + '</span></div>';

        var rows = r.items.length ? r.items.map(function (x) {
            var task = x.kind === 'task';
            var action = '';

            if (!day.closed) {
                if (task) {
                    action = '<button type="button" class="cp-action cp-danger" data-delete-task="' + escapeHtml(x.id) + '">Удалить</button>';
                } else if (x.excluded) {
                    action = '<button type="button" class="cp-action" data-restore="' + escapeHtml(x.id) + '">Вернуть</button>';
                } else {
                    action = '<button type="button" class="cp-action cp-danger" data-exclude="' + escapeHtml(x.id) + '" data-courier="' + r.courier_id + '">Убрать</button>';
                }
            }

            return '<div class="cp-ledger-row' + (x.excluded ? ' excluded' : '') + '">' +
                '<div><span class="cp-type' + (task ? ' task' : '') + '">' + escapeHtml(x.type) + '</span></div>' +
                '<div class="cp-ledger-id">' + (task ? '' : '#' + escapeHtml(x.id)) + '</div>' +
                '<div class="cp-ledger-desc">' + escapeHtml(x.title || '—') +
                    (x.note ? '<small>' + escapeHtml(x.note) + '</small>' : '') +
                    (x.excluded ? '<small class="cp-excluded-note">Убран из расчёта</small>' : '') + '</div>' +
                '<div class="cp-ledger-time">' + escapeHtml(x.time) + '</div>' +
                '<div>' + action + '</div>' +
            '</div>';
        }).join('') : '<div class="cp-empty">Доставок нет</div>';

        var form = day.closed ? '' : $('#cpTaskForm').html().replace('<form ', '<form data-courier="' + r.courier_id + '" ');

        return '<div class="cp-detail">' + head + '<div class="cp-ledger">' + rows + '</div>' + form + '</div>';
    }

    function renderSummary() {
        var s = day.summary || {};

        $('#cpGrand').text(money(s.total));
        $('#cpBase').text(money(s.base));
        $('#cpKm').text(money(s.km_pay));
        $('#cpTotal').text(money(s.total));
    }

    function money(value) {
        var n = Number(value || 0);

        return n.toLocaleString('ru-RU', { maximumFractionDigits: 2 }) + ' ₴';
    }

    function plural(n, one, few, many) {
        var mod10 = n % 10, mod100 = n % 100;

        if (mod10 === 1 && mod100 !== 11) { return one; }
        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) { return few; }
        return many;
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

    function init() {
        var el = document.querySelector('.courier-payments-screen');

        if (!el) {
            screen = null;
            return;
        }

        // "render" also fires after every AJAX request, including our own.
        if (el === screen) {
            return;
        }

        screen = el;
        expanded = null;
        load(false);
    }

    $(document)
        .on('click', '#cpRefresh', function () { load(false); })
        .on('change', '#cpDate, #cpSpot', function () {
            expanded = null;
            load(false);
        })
        .on('click', '.cp-expand', function () {
            var id = Number($(this).data('courier'));

            expanded = expanded === id ? null : id;
            renderRows();
        })
        .on('change', '.cp-km', function () {
            call('onSaveKm', { courier_id: $(this).data('courier'), km: $(this).val() });
        })
        .on('click', '[data-exclude]', function () {
            if (!confirm('Убрать чек #' + $(this).data('exclude') + ' из расчёта курьера?')) { return; }
            call('onExcludeReceipt', { transaction_id: $(this).data('exclude'), courier_id: $(this).data('courier') });
        })
        .on('click', '[data-restore]', function () {
            call('onRestoreReceipt', { transaction_id: $(this).data('restore') });
        })
        .on('click', '[data-delete-task]', function () {
            if (!confirm('Удалить эту поездку из расчёта?')) { return; }
            call('onDeleteTask', { task_id: $(this).data('delete-task') });
        })
        .on('submit', '.cp-task-form', function () {
            var form = $(this);

            call('onAddTask', {
                courier_id: form.data('courier'),
                type: form.find('[name=type]').val(),
                details: form.find('[name=details]').val(),
                comment: form.find('[name=comment]').val(),
                time: form.find('[name=time]').val()
            });

            return false;
        })
        .on('click', '#cpClose', function () {
            var notes = [];

            if (day && day.summary && day.summary.open_receipts) {
                notes.push('ещё открыто чеков с курьером: ' + day.summary.open_receipts);
            }
            if (day && day.unmapped && day.unmapped.length) {
                notes.push('есть чеки непривязанных сотрудников Poster');
            }

            var text = 'Закрыть день ' + $('#cpDate').val() + '? Суммы будут зафиксированы.' +
                (notes.length ? '\n\nВнимание: ' + notes.join('; ') + ' — они не войдут в выплату.' : '');

            if (!confirm(text)) { return; }
            call('onCloseDay');
        })
        .on('click', '#cpReopen', function () {
            if (!confirm('Открыть день заново? Суммы снова будут считаться по текущим данным Poster.')) { return; }
            call('onReopenDay');
        });

    $(document).ready(init);
    $(document).on('render page:loaded page:updated', init);
}());
