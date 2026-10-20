<?php

namespace Tuoluojiang\Open\services\expressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * 自定义 减法
 */
class CustomBcsubExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction('bcsub', function ($a, $b, $precision = 0) {
                return sprintf('bcsub(%1$s, %2$s, %3$d)', $a, $b, $precision);
            }, function ($variables, $a, $b, $precision = 0) {
                return bcsub($a, $b, $precision);
            }),
        ];
    }
}
