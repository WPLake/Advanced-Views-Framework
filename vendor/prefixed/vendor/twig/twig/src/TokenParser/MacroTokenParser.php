<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Org\Wplake\Advanced_Views\Vendors\Twig\TokenParser;

use Org\Wplake\Advanced_Views\Vendors\Twig\Error\SyntaxError;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\BodyNode;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\MacroNode;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\Node;
use Org\Wplake\Advanced_Views\Vendors\Twig\Token;
/**
 * Defines a macro.
 *
 *   {% macro input(name, value, type, size) %}
 *      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
 *   {% endmacro %}
 *
 * @internal
 */
final class MacroTokenParser extends AbstractTokenParser
{
    public function parse(Token $token) : Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(
            /* Token::NAME_TYPE */
            5
        )->getValue();
        $arguments = $this->parser->getExpressionParser()->parseArguments(\true, \true);
        $stream->expect(
            /* Token::BLOCK_END_TYPE */
            3
        );
        $this->parser->pushLocalScope();
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        if ($token = $stream->nextIf(
            /* Token::NAME_TYPE */
            5
        )) {
            $value = $token->getValue();
            if ($value != $name) {
                throw new SyntaxError(\sprintf('Expected endmacro for macro "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        $this->parser->popLocalScope();
        $stream->expect(
            /* Token::BLOCK_END_TYPE */
            3
        );
        $this->parser->setMacro($name, new MacroNode($name, new BodyNode([$body]), $arguments, $lineno, $this->getTag()));
        return new Node();
    }
    public function decideBlockEnd(Token $token) : bool
    {
        return $token->test('endmacro');
    }
    public function getTag() : string
    {
        return 'macro';
    }
}
