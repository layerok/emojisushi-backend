<div class="container-fluid">

    <!-- <div class="card mb-3">
        <div class="card-header">
            <h3>Оправить уведомление всем устройствам</h3>
        </div>

        <div class="card-body">

            <form
                data-request="onSendNotification"
                data-request-confirm="Оправить уведомление всем пользователям?"
            >

                <div class="form-group">
                    <label>Заголовок</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control" />
                </div>

                <div class="form-group">
                    <label>Сообщение</label>
                    <textarea
                        name="body"
                        class="form-control"
                        rows="3"></textarea>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary">
                    Отпраить на все устройства
                </button>

            </form>

        </div>
    </div> -->

    <div class="card mb-3">
        <div class="card-header">
            <h3>Отправить в топик</h3>
            <!-- <p class="text-muted mb-0">
                Требует, чтобы токены уже были подписаны на топик (см. "Подписать существующие токены" ниже).
            </p> -->
        </div>

        <div class="card-body">

            <form
                data-request="onSendToTopic"
                data-request-confirm="Отправить уведомление в выбранный топик?"
            >

                <div class="form-group">
                    <label>Топик</label>
                    <select name="topic" class="form-control">
                        <option value="<?= e($allUsersTopic) ?>">все пользователи (<?= e($allUsersTopic) ?>)</option>
                        <?php foreach ($cityTopics as $city): ?>
                            <option value="<?= e($city) ?>"><?= e($city) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Заголовок</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control" />
                </div>

                <div class="form-group">
                    <label>Сообщение</label>
                    <textarea
                        name="body"
                        class="form-control"
                        rows="3"></textarea>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary">
                    Отправить в топик
                </button>

            </form>

        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3>(Не использовать) Подписать существующие токены</h3>
            <p class="text-muted mb-0">
                Подписывает все токены на <?= e($allUsersTopic) ?>, и токены с заполненным city — на соответствующий
                топик города
            </p>
        </div>

        <div class="card-body">
            <form
                data-request="onSubscribeExisting"
                data-request-confirm="Подписать все существующие токены на топики?"
            >
                <button
                    type="submit"
                    class="btn btn-secondary">
                    Подписать существующие токены на топики
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Устройства (<?= (int) $totalTokens ?>)</h3>
                </div>

                <div class="card-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Тип</th>
                            <th>Кол-во</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($platformCounts as $row): ?>
                            <tr>
                                <td><?= e($row->platform ?: 'unknown') ?></td>
                                <td><?= $row->total ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Города</h3>
                </div>

                <div class="card-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Город</th>
                            <th>Кол-во</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($cityCounts as $row): ?>
                            <tr>
                                <td><?= e($row->city ?: '(не указан)') ?></td>
                                <td><?= $row->total ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
