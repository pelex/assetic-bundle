<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Twig;

use Assetic\Asset\AssetInterface;
use Assetic\Extension\Twig\AsseticNode as BaseAsseticNode;

/**
 * Class TargetPathNode
 * @package Symfony\Bundle\AsseticBundle\Twig
 */
class TargetPathNode extends AsseticNode
{
    private $node;
    private $asset;
    private $name;

    /**
     * TargetPathNode constructor.
     * @param AsseticNode    $node
     * @param AssetInterface $asset
     * @param array          $name
     */
    public function __construct(AsseticNode $node, AssetInterface $asset, $name)
    {
        parent::__construct($asset, $node, [], [], $name);
        $this->node = $node;
        $this->asset = $asset;
        $this->name = $name;
    }

    /**
     * @param \Twig_Compiler $compiler
     */
    public function compile(\Twig_Compiler $compiler)
    {
        BaseAsseticNode::compileAssetUrl($compiler, $this->asset, $this->name);
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->node->getTemplateLine();
    }
}
