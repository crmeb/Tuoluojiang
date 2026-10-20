<?php

namespace Tuoluojiang\Open\services\expressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * 自定义 加法
 */
class CustomBcAddExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction('bcadd', function ($a, $b, $precision = 0) {
                return sprintf('bcadd(%1$s, %2$s, %3$d)', $a, $b, $precision);
            }, function ($variables, $a, $b, $precision = 0) {
                return bcadd($a, $b, $precision);
            }),
        ];
    }
}
