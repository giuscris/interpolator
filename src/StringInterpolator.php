<?php

namespace Interpolator;

class StringInterpolator {
    protected const INTERPOLATION_REGEX = '/(\\\\?)\$\{((?:[^{}\\\\]|\\\\.)*)\}/';

    protected array $vars;

    public function __construct(array $vars)
    {
        $this->vars = $vars;
    }

    public function interpolate(string $string): string
    {
        return preg_replace_callback(
            self::INTERPOLATION_REGEX,
            function (array $matches): ?string {
                [$match, $escape, $value] = $matches;
                if ($escape !== '') {
                    return substr($match, 1);
                }
                $value = str_replace(['\\{', '\\}'], ['{', '}'], $value);
                return NodeInterpolator::fromString($value, $this->vars)->interpolate();
            },
            $string
        );
    }
}