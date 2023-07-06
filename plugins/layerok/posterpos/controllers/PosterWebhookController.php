<?php

namespace Layerok\PosterPos\Controllers;
use Illuminate\Support\Facades\Log;
use Layerok\PosterPos\Classes\PosterTransition;
use poster\src\PosterApi;

class PosterWebhookController {
    public function __invoke() {
        //Log::info("Пришел хук от постера");
        // Секретный ключ вашего приложения
        $client_secret = config('poster.application_secret');

        // Приводим к нужному формату входящие данные
        $postJSON = file_get_contents('php://input');
        $postData = json_decode($postJSON, true);
        $verify_original = $postData['verify'];
        unset($postData['verify']);

        $verify = [
            $postData['account'],
            $postData['object'],
            $postData['object_id'],
            $postData['action'],
        ];

        // Если есть дополнительные параметры
        if (isset($postData['data'])) {
            $verify[] = $postData['data'];
        }
        $verify[] = $postData['time'];
        $verify[] = $client_secret;

        // Создаём строку для верификации запроса клиентом
        $verify = md5(implode(';', $verify));

        // Проверяем валидность данных
        if ($verify != $verify_original) {
            Log::info("Проверка валидности данных провалилась");
            exit;
        }


        $transition = new PosterTransition;

        if ($postData['action'] == "added" || $postData['action'] == "changed") {
            $config = [
                'access_token' => config('poster.access_token'),
                'application_secret' => config('poster.application_secret'),
                'application_id' => config('poster.application_id'),
                'account_name' => config('poster.account_name')
            ];
            PosterApi::init($config);
            $result = (object)PosterApi::menu()->getProduct([
                'product_id' => $postData['object_id']
            ]);
            $product = $result->response;
            if (!$product) {
                return;
            }

        }

        switch ($postData['action']) {
            case "added":
                //Log::info("Добавляем {$postData['object_id']}", ['instance' => $product]);
                $transition->createProduct($product);
                break;
            case "removed":
                //Log::info("Удаляем {$postData['object_id']}", ['poster_id' => $postData['object_id']]);
                $transition->deleteProduct($postData['object_id']);
                break;
            case "changed":
                //Log::info("Обновляем {$postData['object_id']}", ['instance' => $product]);
                $transition->updateProduct($product);
                break;
        }

        // Если не ответить на запрос, Poster продолжит слать Webhook
        echo json_encode(['status' => 'accept']);
        return;
    }
}
