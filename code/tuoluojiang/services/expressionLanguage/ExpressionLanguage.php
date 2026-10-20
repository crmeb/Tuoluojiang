<?php

namespace Tuoluojiang\Open\services\expressionLanguage;


use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * 表达式语言
 */
class ExpressionLanguage
{

    /**
     * @var SymfonyExpressionLanguage
     */
    private $language;

    /**
     *
     */
    public function __construct()
    {
        $this->language = new SymfonyExpressionLanguage();
        $this->language->registerProvider(new CustomBcDivExpressionFunctionProvider());
        $this->language->registerProvider(new CustomBcAddExpressionFunctionProvider());
        $this->language->registerProvider(new CustomBcmulExpressionFunctionProvider());
        $this->language->registerProvider(new CustomBcsubExpressionFunctionProvider());
    }

    /**
     * 执行表达式
     * @param Expression|string $expression
     * @param array $values
     * @return mixed
     */
    public function evaluate(Expression|string $expression, array $values = [])
    {
        return $this->language->evaluate($expression, $values);
    }
}
