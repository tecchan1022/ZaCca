<?php

/* __string_template__26ff7037735cbccfab58c3826729ae2464015fe272727876464cb6257d86ef3d */
class __TwigTemplate_e8672cfa949323167eb1fab4279df1cace392d8b5fbfc5106b985eb71dbbeabc extends Twig_Template
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
        echo "<script type=\"text/javascript\">
    \$(function(){
        \$(\".newslist\").each(function(){
            var listLenght = \$(this).find(\"dl\").length;
            if(listLenght>5){
                \$(this).find(\"dl:gt(4)\").each(function(){\$(this).hide();});
                \$(this).append('<p class=\"news_more\"><a id=\"news_readmore\">» もっと見る</a></p>');
                var dispNum = 5;
                \$(this).find(\"#news_readmore\").click(function(){
                    dispNum +=5;
                    \$(this).parents(\".newslist\").find(\"dl:lt(\"+dispNum+\")\").show(400);
                    if (dispNum>=listLenght) {
                        \$(this).hide();
                    }
                })
            }
        });
    });
</script>
<div id=\"news_area\" class=\"row\">
    <div class=\"category_header col-xs-12\">
        <h2 class=\"heading01\">INFORMATION</h2>
    </div>
    <div class=\"accordion col-xs-12\">
        <div class=\"newslist\">
            ";
        // line 47
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["NewsList"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["News"]) {
            // line 48
            echo "                <dl>
                    <dt>
                        <span class=\"date\">
                            ";
            // line 51
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($context["News"], "date", array()), "Y/m/d"), "html", null, true);
            echo "
                        </span>
                        <span class=\"news_title\">
                            ";
            // line 54
            echo twig_escape_filter($this->env, $this->getAttribute($context["News"], "title", array()), "html", null, true);
            echo "
                        </span>
                        ";
            // line 56
            if (($this->getAttribute($context["News"], "comment", array()) || $this->getAttribute($context["News"], "url", array()))) {
                // line 57
                echo "                            <span class=\"angle-circle\">
                                <svg class=\"cb cb-angle-down\"><use xlink:href=\"#cb-angle-down\" /></svg>
                            </span>
                        ";
            }
            // line 61
            echo "                    </dt>
                    ";
            // line 62
            if (($this->getAttribute($context["News"], "comment", array()) || $this->getAttribute($context["News"], "url", array()))) {
                // line 63
                echo "                        <dd>";
                echo nl2br($this->getAttribute($context["News"], "comment", array()));
                echo "
                            ";
                // line 64
                if ($this->getAttribute($context["News"], "url", array())) {
                    echo "<br>
                                <a href=\"";
                    // line 65
                    echo twig_escape_filter($this->env, $this->getAttribute($context["News"], "url", array()), "html", null, true);
                    echo "\" ";
                    if (($this->getAttribute($context["News"], "link_method", array()) == "1")) {
                        echo "target=\"_blank\"";
                    }
                    echo ">
                                    詳しくはこちら
                                </a>
                            ";
                }
                // line 69
                echo "                        </dd>
                    ";
            }
            // line 71
            echo "                </dl>
            ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['News'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 73
        echo "        </div><!--/newslist-->
    </div><!--/accordion-->
</div><!--/news_area-->
";
    }

    public function getTemplateName()
    {
        return "__string_template__26ff7037735cbccfab58c3826729ae2464015fe272727876464cb6257d86ef3d";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  110 => 73,  103 => 71,  99 => 69,  88 => 65,  84 => 64,  79 => 63,  77 => 62,  74 => 61,  68 => 57,  66 => 56,  61 => 54,  55 => 51,  50 => 48,  46 => 47,  19 => 22,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "__string_template__26ff7037735cbccfab58c3826729ae2464015fe272727876464cb6257d86ef3d", "");
    }
}
