<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Org\Wplake\Advanced_Views\Vendors\Twig\Profiler\NodeVisitor;

use Org\Wplake\Advanced_Views\Vendors\Twig\Environment;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\BlockNode;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\BodyNode;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\MacroNode;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\ModuleNode;
use Org\Wplake\Advanced_Views\Vendors\Twig\Node\Node;
use Org\Wplake\Advanced_Views\Vendors\Twig\NodeVisitor\NodeVisitorInterface;
use Org\Wplake\Advanced_Views\Vendors\Twig\Profiler\Node\EnterProfileNode;
use Org\Wplake\Advanced_Views\Vendors\Twig\Profiler\Node\LeaveProfileNode;
use Org\Wplake\Advanced_Views\Vendors\Twig\Profiler\Profile;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class ProfilerNodeVisitor implements NodeVisitorInterface
{
    private $extensionName;
    private $varName;
    public function __construct(string $extensionName)
    {
        $this->extensionName = $extensionName;
        $this->varName = \sprintf('__internal_%s', \hash(\PHP_VERSION_ID < 80100 ? 'sha256' : 'xxh128', $extensionName));
    }
    public function enterNode(Node $node, Environment $env) : Node
    {
        return $node;
    }
    public function leaveNode(Node $node, Environment $env) : ?Node
    {
        if ($node instanceof ModuleNode) {
            $node->setNode('display_start', new Node([new EnterProfileNode($this->extensionName, Profile::TEMPLATE, $node->getTemplateName(), $this->varName), $node->getNode('display_start')]));
            $node->setNode('display_end', new Node([new LeaveProfileNode($this->varName), $node->getNode('display_end')]));
        } elseif ($node instanceof BlockNode) {
            $node->setNode('body', new BodyNode([new EnterProfileNode($this->extensionName, Profile::BLOCK, $node->getAttribute('name'), $this->varName), $node->getNode('body'), new LeaveProfileNode($this->varName)]));
        } elseif ($node instanceof MacroNode) {
            $node->setNode('body', new BodyNode([new EnterProfileNode($this->extensionName, Profile::MACRO, $node->getAttribute('name'), $this->varName), $node->getNode('body'), new LeaveProfileNode($this->varName)]));
        }
        return $node;
    }
    public function getPriority() : int
    {
        return 0;
    }
}
