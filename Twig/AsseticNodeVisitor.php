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

use Assetic\Extension\Twig\AsseticFilterFunction;
use Assetic\Extension\Twig\AsseticFilterInvoker;
use Symfony\Bundle\AsseticBundle\Exception\InvalidBundleException;
use Symfony\Bundle\AsseticBundle\Factory\AssetFactory;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\Templating\TemplateNameParserInterface;

/**
 * Assetic node visitor.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class AsseticNodeVisitor extends \Twig_BaseNodeVisitor
{
    private $templateNameParser;
    private $enabledBundles;

    public function __construct(TemplateNameParserInterface $templateNameParser, array $enabledBundles)
    {
        $this->templateNameParser = $templateNameParser;
        $this->enabledBundles = $enabledBundles;
    }

    protected function doEnterNode(\Twig_Node $node, \Twig_Environment $env)
    {
        return $node;
    }

    protected function doLeaveNode(\Twig_Node $node, \Twig_Environment $env)
    {
        if (!$formula = $this->checkNode($node, $env, $name)) {
            return $node;
        }

        // check the bundle
        $templateRef = $this->templateNameParser->parse((new \Twig_Parser($env))->getStream()->getSourceContext()->getName());
        $bundle = $templateRef instanceof TemplateReference ? $templateRef->get('bundle') : null;
        if ($bundle && !in_array($bundle, $this->enabledBundles)) {
            throw new InvalidBundleException($bundle, "the $name() function", $templateRef->getLogicalName(), $this->enabledBundles);
        }

        list($input, $filters, $options) = $formula;
        $line = $node->getTemplateLine();

        // check context and call either asset() or path()
        return new \Twig_Node_Expression_Conditional(
            new \Twig_Node_Expression_GetAttr(
                new \Twig_Node_Expression_Name('assetic', $line),
                new \Twig_Node_Expression_Constant('use_controller', $line),
                new \Twig_Node_Expression_Array(array(), 0),
                \Twig_Template::ARRAY_CALL,
                $line
            ),
            new \Twig_Node_Expression_Function(
                'path',
                new \Twig_Node(array(
                    new \Twig_Node_Expression_Constant('_assetic_'.$options['name'], $line),
                )),
                $line
            ),
            new \Twig_Node_Expression_Function(
                'asset',
                new \Twig_Node(array($node, new \Twig_Node_Expression_Constant(isset($options['package']) ? $options['package'] : null, $line))),
                $line
            ),
            $line
        );
    }

    /**
     * Extracts formulae from filter function nodes.
     *
     * @return array|null The formula
     */
    private function checkNode(\Twig_Node $node, \Twig_Environment $env, &$name = null)
    {
        if ($node instanceof \Twig_Node_Expression_Function) {
            $name = $node->getAttribute('name');
            if ($env->getFunction($name) instanceof AsseticFilterFunction) {
                $arguments = array();
                foreach ($node->getNode('arguments') as $argument) {
                    $arguments[] = eval('return '.$env->compile($argument).';');
                }

                /** @var AsseticExtension $assetic */
                $assetic = $env->getExtension('assetic');
                /** @var AsseticFilterInvoker $invoker */
                $invoker = $assetic->getFilterInvoker($name);
                /** @var AssetFactory $factory */
                $factory = $invoker->getFactory();

                $inputs = isset($arguments[0]) ? (array) $arguments[0] : array();
                $filters = $invoker->getFilters();
                $options = array_replace($invoker->getOptions(), isset($arguments[1]) ? $arguments[1] : array());

                if (!isset($options['name'])) {
                    $options['name'] = $factory->generateAssetName($inputs, $filters);
                }

                return array($inputs, $filters, $options);
            }
        }

        return null;
    }

    public function getPriority()
    {
        return 0;
    }
}
