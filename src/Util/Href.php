<?php

namespace App\Util;

class Href
{
    private array $components = [];

    public function __construct(string $href)
    {
        if ($href == '') {
            return;
        }

        $a0 = \explode('://', $href, 2);
        $v0 = $a0[1] ?? $a0[0];

        if (!isset($a0[1]) && \str_starts_with($v0, '//')) {
            $v0 = \substr($v0, 2);
        }

        if (\str_starts_with($v0, '?') || \str_starts_with($v0, '#')) {
            $this->components['relativeReference'] = $v0;
            return;
        }

        $a1 = \explode('/', $v0, 2);

        $v1 = $a1[0];
        if (\str_contains($v1, '@')) {
            $c1 = \explode('@', $v1, 2);

            $v1 = $c1[1];
        }

        $a2 = \explode(':', $v1, 2);
        $this->components['host'] = $a2[0];

        if (isset($a1[1])) {
            $this->components['relativeReference'] = '/' . $a1[1];
        }
    }

    public function getHost(): string
    {
        return $this->components['host'] ?? '';
    }

    public function getRelativeReference(): ?string
    {
        return $this->components['relativeReference'] ?? null;
    }
}
