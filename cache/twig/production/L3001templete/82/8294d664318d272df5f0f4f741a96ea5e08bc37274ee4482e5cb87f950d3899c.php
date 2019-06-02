<?php

/* __string_template__17db57665a509e7c37787aa8a4016b5bb608c306a31cc66e8b7b72fb3c074a47 */
class __TwigTemplate_81b64f7701fbbfba4fd5535d2a46a0e1e1cbc045df49f0928dcea366962b7170 extends Twig_Template
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
        if ($this->env->getExtension('Symfony\Bridge\Twig\Extension\SecurityExtension')->isGranted("ROLE_USER")) {
            // line 23
            echo "    <div id=\"member\" class=\"member drawer_block pc\">
        <ul class=\"member_link\">
            <li>
                <a href=\"";
            // line 26
            echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("mypage");
            echo "\" title=\"マイページ\">
                    <svg class=\"cb cb-user\"><use xlink:href=\"#cb-user\" /></svg><span class=\"member_link-text\">マイページ</span>
                </a>
            </li>
            ";
            // line 30
            if (($this->getAttribute(($context["BaseInfo"] ?? null), "option_favorite_product", array()) == 1)) {
                // line 31
                echo "                <li><a href=\"";
                echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("mypage_favorite");
                echo "\" title=\"お気に入り\"><svg class=\"cb cb-heart\"><use xlink:href=\"#cb-heart\"></use></svg><span class=\"member_link-text\">お気に入り</span></a></li>
            ";
            }
            // line 33
            echo "            <li>
                <a href=\"";
            // line 34
            echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("logout");
            echo "\" title=\"ログアウト\">
                    <svg class=\"cb cb-lock\"><use xlink:href=\"#cb-lock\" /></svg><span class=\"member_link-text\">ログアウト</span>
                </a>
            </li>
        </ul>
    </div>
";
        } else {
            // line 41
            echo "    <div id=\"member\" class=\"member drawer_block pc\">
        <ul class=\"member_link\">
            <li>
                <a href=\"";
            // line 44
            echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("entry");
            echo "\" title=\"新規会員登録\">
                    <svg class=\"cb cb-user\"><use xlink:href=\"#cb-user\" /></svg><span class=\"member_link-text\">新規会員登録</span>
                </a>
            </li>
            ";
            // line 48
            if (($this->getAttribute(($context["BaseInfo"] ?? null), "option_favorite_product", array()) == 1)) {
                // line 49
                echo "                <li><a href=\"";
                echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("mypage_favorite");
                echo "\" title=\"お気に入り\"><svg class=\"cb cb-heart\"><use xlink:href=\"#cb-heart\"></use></svg><span class=\"member_link-text\">お気に入り</span></a></li>
            ";
            }
            // line 51
            echo "            <li>
                <a href=\"";
            // line 52
            echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("mypage_login");
            echo "\" title=\"ログイン\">
                    <svg class=\"cb cb-lock\"><use xlink:href=\"#cb-lock\" /></svg><span class=\"member_link-text\">ログイン</span>
                </a>
            </li>
        </ul>
    </div>
";
        }
    }

    public function getTemplateName()
    {
        return "__string_template__17db57665a509e7c37787aa8a4016b5bb608c306a31cc66e8b7b72fb3c074a47";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  77 => 52,  74 => 51,  68 => 49,  66 => 48,  59 => 44,  54 => 41,  44 => 34,  41 => 33,  35 => 31,  33 => 30,  26 => 26,  21 => 23,  19 => 22,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "__string_template__17db57665a509e7c37787aa8a4016b5bb608c306a31cc66e8b7b72fb3c074a47", "");
    }
}
