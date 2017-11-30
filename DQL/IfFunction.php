<?php

namespace NaxCrmBundle\DQL;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * IF function
 *
 * You must boostrap this function in your ORM as a DQLFunction.
 *
 *
 * IF(condition, true_value, false_value) : @link https://dev.mysql.com/doc/refman/5.7/en/control-flow-functions.html#function_if
 *
 *
 * PLEASE REMEMBER TO CHECK YOUR NAMESPACE
 *
 * @link labs.ultravioletdesign.co.uk
 * @author Rob Squires <rob@ultravioletdesign.co.uk>
 *
 *
 */
class IfFunction extends FunctionNode
{
    private $expr = array();

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->expr[] = $parser->ConditionalExpression();

        for ($i = 0; $i < 2; $i++)
        {
            $parser->match(Lexer::T_COMMA);
            $this->expr[] = $parser->ArithmeticExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return sprintf('IF(%s, %s, %s)',
            $sqlWalker->walkConditionalExpression($this->expr[0]),
            $sqlWalker->walkArithmeticPrimary($this->expr[1]),
            $sqlWalker->walkArithmeticPrimary($this->expr[2]));
    }
}
