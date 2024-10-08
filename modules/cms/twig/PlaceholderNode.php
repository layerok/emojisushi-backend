<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Compiler as TwigCompiler;

/**
 * PlaceholderNode represents a "placeholder" node
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class PlaceholderNode extends TwigNode
{
    /**
     * __construct
     */
    public function __construct(?TwigNode $body, $name, $paramValues, $lineno, $tag = 'placeholder')
    {
        $nodes = [];

        if ($body) {
            $nodes['default'] = $body;
        }

        $attributes = $paramValues;
        $attributes['name'] = $name;

        parent::__construct($nodes, $attributes, $lineno, $tag);
    }

    /**
     * compile the node to PHP
     */
    public function compile(TwigCompiler $compiler)
    {
        $hasBody = $this->hasNode('default');
        $varId = '__placeholder_'.$this->getAttribute('name').'_default_contents';
        $compiler
            ->addDebugInfo($this)
            ->write("\$context[")
            ->raw("'".$varId."'")
            ->raw("] = null;");

        if ($hasBody) {
            $compiler
                ->addDebugInfo($this)
                ->write("\$context[")
                ->raw("'".$varId."'")
                ->raw("] = implode('', iterator_to_array((function() use (\$context, \$blocks, \$macros) {")
                ->subcompile($this->getNode('default'))
                ->raw('return; yield "";})()));')
            ;
        }

        $isText = $this->hasAttribute('type') && $this->getAttribute('type') == 'text';

        $compiler->addDebugInfo($this);
        if (!$isText) {
            $compiler->write("yield \$this->env->getExtension(\Cms\Twig\Extension::class)->displayBlock(");
        }
        else {
            $compiler->write("yield \$this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(\$this->env->getExtension(\Cms\Twig\Extension::class)->displayBlock(");
        }

        $compiler
            ->raw("'".$this->getAttribute('name')."', ")
            ->raw("\$context[")
            ->raw("'".$varId."'")
            ->raw("]")
            ->raw(")");

        if (!$isText) {
            $compiler->raw(";\n");
        }
        else {
            $compiler->raw(");\n");
        }

        $compiler
            ->addDebugInfo($this)
            ->write("unset(\$context[")
            ->raw("'".$varId."'")
            ->raw("]);");
    }
}
