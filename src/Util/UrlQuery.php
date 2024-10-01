<?php

namespace App\Util;

class UrlQuery
{
    private array $parameter = [];

    public function __construct(string $query)
    {
        foreach (\explode('&', $query) as $val) {
            if (!$val) continue;

            $q = \explode('=', $val, 2);

            $qKey = \urldecode($q[0]);
            if (!isset($this->parameter[$qKey])) {
                $this->parameter[$qKey] = [];
            }

            $qVal = isset($q[1]) ? \urldecode($q[1]) : null;
            if ($qVal == "\x00") $qVal = null;
            $this->parameter[$qKey][] = $qVal;
        }
    }

    public function all(string $key, string $pluralKey = null): array
    {
        $result = [];

        foreach ($this->parameter as $k => $v) {
            if ($k == $key) {
                \array_push($result, ...$v);
                continue;
            }

            if ($pluralKey) {
                if ($key == $pluralKey . '[]') {
                    \array_push($result, ...$v);
                    continue;
                }

                if (!\str_starts_with($k, $pluralKey . '[') || !\str_ends_with($k, ']')) {
                    continue;
                }

                $i = \substr($k, \strlen($pluralKey) + 1, -1);
                if (\filter_var($i, \FILTER_VALIDATE_INT) !== false) {
                    \array_splice($result, $i + 1, 0, \end($v));
                    continue;
                }
            }
        }

        return $result;
    }
}
