<?php

namespace Tuoluojiang\Open\services\expressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * 自定义 除法
 */
class CustomBcDivExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction('bcdiv', function ($a, $b, $precision = 0) {
                return sprintf('bcdiv(%1$s, %2$s, %3$d)', $a, $b, $precision);
            }, function ($variables, $a, $b, $precision = 0) {
                return bcdiv($a, $b, $precision);
            }),
        ];
    }
}
