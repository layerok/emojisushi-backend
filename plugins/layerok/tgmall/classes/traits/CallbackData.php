<?php
namespace Layerok\TgMall\Classes\Traits;

trait CallbackData {

    public static function prepareCallbackData($name, $arguments = []): string
    {
        return json_encode([$name, $arguments]);
    }

    public static function parseCallbackData($data): array
    {
        $data = json_decode($data, true);
        try {
            $name = $data[0];
            $arguments = $data[1] ?? [];
            return [$name, $arguments];
        } catch (\ErrorException $e) {
            \Log::error('Cannot parse callback query data. Error happened inside [' . self::class . ']');
            \Log::error($e);
            return ['noop', []];
        }

    }

}
