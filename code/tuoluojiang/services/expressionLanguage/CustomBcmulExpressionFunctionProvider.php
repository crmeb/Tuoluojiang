<?php

namespace Tuoluojiang\Open\services\expressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * 自定义 乘法
 */
class CustomBcmulExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction('bcmul', function ($a, $b, $precision = 0) {
                return sprintf('bcmul(%1$s, %2$s, %3$d)', $a, $b, $precision);
            }, function ($variables, $a, $b, $precision = 0) {
                return bcmul($a, $b, $precision);
            }),
        ];
    }
}
