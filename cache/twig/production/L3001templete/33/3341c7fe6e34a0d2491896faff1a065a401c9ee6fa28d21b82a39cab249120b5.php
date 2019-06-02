<?php

/* __string_template__a2ed9cf704fe7a7361b3cc2c1a5232a237cb4b182d98f724560f61140b271cab */
class __TwigTemplate_4fc74fd44400501729925d5e15320cbea06cae300d0b70aae03497b1c1cffe94 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 36
        echo "<div class=\"drawer_block pc col-xs-12 col-sm-6\">
<nav id=\"category\">
    <ul class=\"category-nav\">
    ";
        // line 39
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["Categories"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["Category"]) {
            // line 40
            echo "        ";
            echo $this->getAttribute($this, "tree", array(0 => $context["Category"]), "method");
            echo "
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['Category'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 42
        echo "    </ul> <!-- category-nav -->
</nav>
</div>
";
    }

    // line 22
    public function gettree($__Category__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "Category" => $__Category__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 23
            echo "    <li>
        <a href=\"";
            // line 24
            echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("product_list");
            echo "?category_id=";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["Category"] ?? null), "id", array()), "html", null, true);
            echo "\">
            ";
            // line 25
            echo twig_escape_filter($this->env, $this->getAttribute(($context["Category"] ?? null), "name", array()), "html", null, true);
            echo "
        </a>
        ";
            // line 27
            if ((twig_length_filter($this->env, $this->getAttribute(($context["Category"] ?? null), "children", array())) > 0)) {
                // line 28
                echo "            <ul>
            ";
                // line 29
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["Category"] ?? null), "children", array()));
                foreach ($context['_seq'] as $context["_key"] => $context["ChildCategory"]) {
                    // line 30
                    echo "                    ";
                    echo $this->getAttribute($this, "tree", array(0 => $context["ChildCategory"]), "method");
                    echo "
            ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['ChildCategory'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 32
                echo "            </ul>
        ";
            }
            // line 34
            echo "    </li>
";
        } catch (Exception $e) {
            ob_end_clean();

            throw $e;
        } catch (Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        return ('' === $tmp = ob_get_clean()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
    }

    public function getTemplateName()
    {
        return "__string_template__a2ed9cf704fe7a7361b3cc2c1a5232a237cb4b182d98f724560f61140b271cab";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  92 => 34,  88 => 32,  79 => 30,  75 => 29,  72 => 28,  70 => 27,  65 => 25,  59 => 24,  56 => 23,  44 => 22,  37 => 42,  28 => 40,  24 => 39,  19 => 36,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "__string_template__a2ed9cf704fe7a7361b3cc2c1a5232a237cb4b182d98f724560f61140b271cab", "");
    }
}
