<?php

/* __string_template__c2bd5d8f06f2379cc4f4d88b012fa06e4ebfa72aee2cf0b39bda690bb0f98ea8 */
class __TwigTemplate_30a01027c987f3481c4385511c13213bfc7d2bb83e1c697164365e71f30ddd33 extends Twig_Template
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
        echo "<div class=\"col-xs-12\">
<div id=\"cart_area\">
    <p class=\"cart-trigger\"><a href=\"#cart\">
            <svg class=\"cb cb-shopping-cart\">
                <use xlink:href=\"#cb-shopping-cart\"/>
            </svg>
            <span class=\"badge\">";
        // line 28
        echo twig_escape_filter($this->env, $this->getAttribute(($context["Cart"] ?? null), "total_quantity", array()), "html", null, true);
        echo "</span>
            <svg class=\"cb cb-close\">
                <use xlink:href=\"#cb-close\"/>
            </svg>
        </a>
";
        // line 34
        echo "    </p>
    <div id=\"cart\" class=\"cart\">
        <div class=\"inner\">
            ";
        // line 37
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute($this->getAttribute($this->getAttribute(($context["app"] ?? null), "session", array()), "flashbag", array()), "get", array(0 => "eccube.front.cart.error"), "method"));
        foreach ($context['_seq'] as $context["_key"] => $context["error"]) {
            // line 38
            echo "                <div class=\"message\">
                    <p class=\"errormsg bg-danger\">
                        <svg class=\"cb cb-warning\"><use xlink:href=\"#cb-warning\" /></svg>";
            // line 40
            echo nl2br(twig_escape_filter($this->env, $this->env->getExtension('Symfony\Bridge\Twig\Extension\TranslationExtension')->trans($context["error"]), "html", null, true));
            echo "
                    </p>
                </div>
            ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['error'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 44
        echo "            ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["Cart"] ?? null), "CartItems", array()));
        foreach ($context['_seq'] as $context["_key"] => $context["CartItem"]) {
            // line 45
            echo "                ";
            $context["ProductClass"] = $this->getAttribute($context["CartItem"], "Object", array());
            // line 46
            echo "                ";
            $context["Product"] = $this->getAttribute(($context["ProductClass"] ?? null), "Product", array());
            // line 47
            echo "                <div class=\"item_box\">
                    <div class=\"item_photo\"><img
                                src=\"";
            // line 49
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "image_save_urlpath", array()), "html", null, true);
            echo "/";
            echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getNoImageProduct($this->getAttribute(($context["Product"] ?? null), "MainListImage", array())), "html", null, true);
            echo "\"
                                alt=\"";
            // line 50
            echo twig_escape_filter($this->env, $this->getAttribute(($context["Product"] ?? null), "name", array()), "html", null, true);
            echo "\"></div>
                    <dl class=\"item_detail\">
                        <dt class=\"item_name\">";
            // line 52
            echo twig_escape_filter($this->env, $this->getAttribute(($context["Product"] ?? null), "name", array()), "html", null, true);
            echo "</dt>
                        <dd class=\"item_pattern small\">";
            // line 54
            if ($this->getAttribute(($context["ProductClass"] ?? null), "ClassCategory1", array())) {
                // line 55
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["ProductClass"] ?? null), "ClassCategory1", array()), "ClassName", array()), "html", null, true);
                echo "：";
                echo twig_escape_filter($this->env, $this->getAttribute(($context["ProductClass"] ?? null), "ClassCategory1", array()), "html", null, true);
                // line 56
                if ($this->getAttribute(($context["ProductClass"] ?? null), "ClassCategory2", array())) {
                    // line 57
                    echo "<br>";
                    echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["ProductClass"] ?? null), "ClassCategory2", array()), "ClassName", array()), "html", null, true);
                    echo "：";
                    echo twig_escape_filter($this->env, $this->getAttribute(($context["ProductClass"] ?? null), "ClassCategory2", array()), "html", null, true);
                }
            }
            // line 60
            echo "</dd>
                        <dd class=\"item_price\">";
            // line 61
            echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getPriceFilter($this->getAttribute($context["CartItem"], "price", array())), "html", null, true);
            echo "<span class=\"small\">税込</span></dd>
                        <dd class=\"item_quantity form-group form-inline\">数量：";
            // line 62
            echo twig_escape_filter($this->env, $this->getAttribute($context["CartItem"], "quantity", array()), "html", null, true);
            echo "</dd>
                    </dl>
                </div><!--/item_box-->
                <p class=\"cart_price sp\">合計 <span class=\"price\">";
            // line 65
            echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getPriceFilter($this->getAttribute(($context["Cart"] ?? null), "total_price", array())), "html", null, true);
            echo "</span></p>
            ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['CartItem'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 67
        echo "            ";
        if ((twig_length_filter($this->env, $this->getAttribute(($context["Cart"] ?? null), "CartItems", array())) > 0)) {
            // line 68
            echo "
                <div class=\"btn_area\">
                    <ul>
                        <li>
                            <a href=\"";
            // line 72
            echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("cart");
            echo "\" class=\"btn btn-success\">カートへ進む</a>
                        </li>
                        <li>
                            <button type=\"button\" class=\"btn btn-default btn-sm cart-trigger\">キャンセル</button>
                        </li>
                    </ul>
                </div>
            ";
        } else {
            // line 80
            echo "                <div class=\"btn_area\">
                    <div class=\"message\">
                        <p class=\"errormsg bg-danger\" style=\"margin-bottom: 20px;\">
                            現在カート内に<br>商品はございません。
                        </p>
                    </div>
                </div>
            ";
        }
        // line 88
        echo "        </div>
    </div>
</div>
</div>";
    }

    public function getTemplateName()
    {
        return "__string_template__c2bd5d8f06f2379cc4f4d88b012fa06e4ebfa72aee2cf0b39bda690bb0f98ea8";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  154 => 88,  144 => 80,  133 => 72,  127 => 68,  124 => 67,  116 => 65,  110 => 62,  106 => 61,  103 => 60,  96 => 57,  94 => 56,  90 => 55,  88 => 54,  84 => 52,  79 => 50,  73 => 49,  69 => 47,  66 => 46,  63 => 45,  58 => 44,  48 => 40,  44 => 38,  40 => 37,  35 => 34,  27 => 28,  19 => 22,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "__string_template__c2bd5d8f06f2379cc4f4d88b012fa06e4ebfa72aee2cf0b39bda690bb0f98ea8", "");
    }
}
