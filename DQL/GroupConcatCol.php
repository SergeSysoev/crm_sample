<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 13.08.16
 * Time: 12:11
 */

namespace NaxCrmBundle\DQL;


use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\Lexer;

/**
 * Full support for:
 *
 * GROUP_CONCAT(DISTINCT COALESCE(expr [,expr ...])
 *
 */
class GroupConcatCol extends FunctionNode
{
    public $isDistinct = false;
    public $pathExp = null;
    public $separator = null;
    public $orderBy = null;

    protected $argument;

    /**
     * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
     * @return string
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'GROUP_CONCAT(DISTINCT COALESCE(' . $this->argument->dispatch($sqlWalker) . ', "no_select") SEPARATOR ";")';
    }

    /**
     * @param \Doctrine\ORM\Query\Parser $parser
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->argument = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}