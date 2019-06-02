<?php

/* Block/footer.twig */
class __TwigTemplate_64be6a7e727138d915e5caab55779c76663b1e1354274bf0003232c684ec01fd extends Twig_Template
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
        echo "<div class=\"container-fluid inner\">
    <div class=\"row\">
        <ul class=\"footer-nav col-xs-12 col-sm-4\">
            <li><a href=\"";
        // line 25
        echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("help_about");
        echo "\">当サイトについて<svg class=\"cb cb-angle-right\"><use xlink:href=\"#cb-angle-right\" /></svg></a></li>
            <li><a href=\"";
        // line 26
        echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("help_privacy");
        echo "\">プライバシーポリシー<svg class=\"cb cb-angle-right\"><use xlink:href=\"#cb-angle-right\" /></svg></a></li>
            <li><a href=\"";
        // line 27
        echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("help_tradelaw");
        echo "\">特定商取引法に基づく表記<svg class=\"cb cb-angle-right\"><use xlink:href=\"#cb-angle-right\" /></svg></a></li>
            <li><a href=\"";
        // line 28
        echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("contact");
        echo "\">お問い合わせ<svg class=\"cb cb-angle-right\"><use xlink:href=\"#cb-angle-right\" /></svg></a></li>
        </ul>
        <ul class=\"col-xs-12 col-sm-4\">
            ";
        // line 35
        echo "        </ul>
        <div class=\"footer_logo_area col-xs-12 col-sm-4 \">
            <a href=\"";
        // line 37
        echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("homepage");
        echo "\">
                <p class=\"copy\">くらしを楽しむライフスタイルグッズ</p>
                <p class=\"logo\">";
        // line 39
        echo twig_escape_filter($this->env, $this->getAttribute(($context["BaseInfo"] ?? null), "shop_name", array()), "html", null, true);
        echo "</p>
            </a>
        </div>
    </div>
    <div class=\"row\">
        <p class=\"footer-copyright col-xs-12\">
            <small>copyright (c) ";
        // line 45
        echo twig_escape_filter($this->env, $this->getAttribute(($context["BaseInfo"] ?? null), "shop_name", array()), "html", null, true);
        echo " all rights reserved.</small>
        </p>
    </div>
</div>

";
    }

    public function getTemplateName()
    {
        return "Block/footer.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  60 => 45,  51 => 39,  46 => 37,  42 => 35,  36 => 28,  32 => 27,  28 => 26,  24 => 25,  19 => 22,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "Block/footer.twig", "/var/www/html/eccube-3/app/template/L3001templete/Block/footer.twig");
    }
}
