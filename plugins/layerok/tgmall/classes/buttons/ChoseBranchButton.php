<?php

namespace Layerok\TgMall\Classes\Buttons;

use Lovata\BaseCode\Models\Branches;

class ChoseBranchButton
{
    protected $data = [];

    public function __construct(Branches $branch)
    {
        $this->data = [
            'text' => $branch->name,
            'callback_data' => json_encode([
                'name' => 'chose_branch',
                'arguments' => [
                    'id' => $branch->id
                ]
            ])
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }
}
