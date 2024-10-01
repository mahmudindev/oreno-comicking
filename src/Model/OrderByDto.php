<?php

namespace App\Model;

class OrderByDto
{
    public function __construct(
        public string $name,
        public ?string $order = null,
        public ?string $nulls = null,
        public ?array $custom = null
    ) {}

    public static function parse(string $value): OrderByDto
    {
        $orderBy = \explode(' ', $value);

        if (!\str_contains($orderBy[1] ?? '=', '=')) {
            return new self($orderBy[0], $orderBy[1]);
        }

        $result = new self(\array_shift($orderBy));
        foreach ($orderBy as $val) {
            $kv = \explode('=', $val, 2);

            switch (\strtolower($kv[0])) {
                case 'order':
                    $result->order = $kv[1];
                    break;
                case 'nulls':
                    $result->nulls = $kv[1];
                    break;
                default:
                    $result->custom[$kv[0]] = $kv[1] ?? null;
            }
        }

        return $result;
    }
}
