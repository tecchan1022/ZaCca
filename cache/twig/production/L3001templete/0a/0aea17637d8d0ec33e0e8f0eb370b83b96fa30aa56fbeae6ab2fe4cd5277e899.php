<?php

/* Block/free.twig */
class __TwigTemplate_e759dcb4109dd1f252c7e9a82e6e1e8871bec9eb845811bbb3931bec7b47401b extends Twig_Template
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
        // line 22
        echo "<div class=\"row\">
    <div class=\"col-xs-12 txt_bnr_area\">
        <div class=\"txt_bnr\">";
        // line 25
        if ($this->getAttribute(($context["BaseInfo"] ?? null), "delivery_free_amount", array())) {
            // line 26
            echo "<strong>";
            echo twig_escape_filter($this->env, twig_number_format_filter($this->env, $this->getAttribute(($context["BaseInfo"] ?? null), "delivery_free_amount", array())), "html", null, true);
            echo "円以上の購入で<br><strong>配送料無料</strong></strong><br>一部地域は除く";
        } else {
            // line 28
            echo "<strong>0円以上の購入で<br><strong>配送料無料</strong></strong><br>一部地域は除く";
        }
        // line 30
        echo "</div>
    </div>
</div>";
    }

    public function getTemplateName()
    {
        return "Block/free.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  33 => 30,  30 => 28,  25 => 26,  23 => 25,  19 => 22,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "Block/free.twig", "/var/www/html/eccube-3/app/template/L3001templete/Block/free.twig");
    }
}
