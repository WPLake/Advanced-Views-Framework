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

use Org\Wplake\Advanced_Views\Vendors\Twig\Node\Expression\AssignNameExpression;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\ImportNode;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\Node;
use Org\Wplake\Advanced_Views\Vendors\Twig\Token;
/**
 * Imports macros.
 *
 *   {% import 'forms.html' as forms %}
 *
 * @internal
 */
final class ImportTokenParser extends AbstractTokenParser
{
    public function parse(Token $token) : Node
    {
        $macro = $this->parser->getExpressionParser()->parseExpression();
        $this->parser->getStream()->expect(
            /* Token::NAME_TYPE */
            5,
            'as'
        );
        $var = new AssignNameExpression($this->parser->getStream()->expect(
            /* Token::NAME_TYPE */
            5
        )->getValue(), $token->getLine());
        $this->parser->getStream()->expect(
            /* Token::BLOCK_END_TYPE */
            3
        );
        $this->parser->addImportedSymbol('template', $var->getAttribute('name'));
        return new ImportNode($macro, $var, $token->getLine(), $this->getTag(), $this->parser->isMainScope());
    }
    public function getTag() : string
    {
        return 'import';
    }
}
