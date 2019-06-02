<?php

/* __string_template__f51c6f4db6bbc990aabf5e79d9f45b629f3f0761a34cd037f342c33a90868689 */
class __TwigTemplate_cb74109b9fe84af22ce9a379ef1aff3392109561cd381d2668f729027f68daa8 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 24
        $this->parent = $this->loadTemplate("default_frame.twig", "__string_template__f51c6f4db6bbc990aabf5e79d9f45b629f3f0761a34cd037f342c33a90868689", 24);
        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'sub_title' => array($this, 'block_sub_title'),
            'main' => array($this, 'block_main'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "default_frame.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 26
        $context["menus"] = array(0 => "content", 1 => "page");
        // line 24
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 28
    public function block_title($context, array $blocks = array())
    {
        echo "コンテンツ管理";
    }

    // line 29
    public function block_sub_title($context, array $blocks = array())
    {
        echo "ページ管理";
    }

    // line 31
    public function block_main($context, array $blocks = array())
    {
        // line 32
        echo "<div id=\"page_wrap\" class=\"container-fluid\">
    <div id=\"page_list\" class=\"row\">
        <div id=\"page_list_box\" class=\"col-md-12\">
            <div id=\"page_list__body\" class=\"box\">
                <div id=\"page_list_box__header\" class=\"box-header\">
                    <div class=\"box-title\">
                        ページ一覧
                    </div>
                </div>
                <div id=\"sortable_list_box\" class=\"box-body no-padding no-border\">
                    <div id=\"sortable_list_box__list\" class=\"sortable_list\">
                        <div class=\"tableish\">
                            <div class=\"item_box tr\" style=\"background-color: #F9F9F9;\">
                                <div class=\"item_pattern td\"><strong>ページ名</strong></div>
                                <div class=\"item_pattern td\"><strong>ルーティング名</strong></div>
                                <div class=\"item_pattern td\"><strong>ファイル名</strong></div>
                                <div class=\"item_pattern td\"></div>
                            </div>
                            ";
        // line 50
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["PageLayouts"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["PageLayout"]) {
            // line 51
            echo "                                <div id=\"sortable_list_box__item--";
            echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "id", array()), "html", null, true);
            echo "\" class=\"item_box tr\">
                                    <div id=\"sortable_list_box__name--";
            // line 52
            echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "id", array()), "html", null, true);
            echo "\" class=\"item_pattern td\">
                                        ";
            // line 53
            echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "name", array()), "html", null, true);
            echo "
                                    </div>
                                    <div id=\"sortable_list_box__url--";
            // line 55
            echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "id", array()), "html", null, true);
            echo "\" class=\"td\">
                                        ";
            // line 56
            echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "url", array()), "html", null, true);
            echo "
                                    </div>
                                    <div id=\"sortable_list_box__file_name--";
            // line 58
            echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "id", array()), "html", null, true);
            echo "\" class=\"td\">
                                        ";
            // line 59
            if ($this->getAttribute($context["PageLayout"], "file_name", array())) {
                echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "file_name", array()), "html", null, true);
                echo ".twig";
            }
            // line 60
            echo "                                    </div>
                                    <div id=\"sortable_list_box__menu_box--";
            // line 61
            echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "id", array()), "html", null, true);
            echo "\" class=\"icon_edit td\">
                                        <div id=\"sortable_list_box__menu_box_toggle--";
            // line 62
            echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "id", array()), "html", null, true);
            echo "\" class=\"dropdown\">
                                            <a class=\"dropdown-toggle\" data-toggle=\"dropdown\"><svg class=\"cb cb-ellipsis-h\"> <use xlink:href=\"#cb-ellipsis-h\" /></svg></a>
                                            <ul id=\"sortable_list_box__menu--";
            // line 64
            echo twig_escape_filter($this->env, $this->getAttribute($context["PageLayout"], "id", array()), "html", null, true);
            echo "\" class=\"dropdown-menu dropdown-menu-right\">
                                                <li>
                                                    <a href=\"";
            // line 66
            echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_layout_edit", array("id" => $this->getAttribute($context["PageLayout"], "id", array()))), "html", null, true);
            echo "\" >レイアウト編集</a>
                                                </li>
                                                <li>
                                                    ";
            // line 69
            if ((twig_length_filter($this->env, $this->getAttribute($context["PageLayout"], "filename", array())) >= 1)) {
                // line 70
                echo "                                                        <a href=\"";
                echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_page_edit", array("id" => $this->getAttribute($context["PageLayout"], "id", array()))), "html", null, true);
                echo "\">ページ編集</a>
                                                    ";
            } else {
                // line 72
                echo "                                                        <a>ページ編集</a>
                                                    ";
            }
            // line 74
            echo "                                                </li>
                                                <li>
                                                    ";
            // line 76
            if (($this->getAttribute($context["PageLayout"], "edit_flg", array()) == twig_constant("Eccube\\Entity\\PageLayout::EDIT_FLG_USER"))) {
                // line 77
                echo "                                                        <a href=\"";
                echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_page_delete", array("id" => $this->getAttribute($context["PageLayout"], "id", array()))), "html", null, true);
                echo "\" ";
                echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getCsrfTokenForAnchor();
                echo " data-method=\"delete\" data-message=\"このページを削除してもよろしいですか？\">削除</a>
                                                    ";
            } else {
                // line 79
                echo "                                                        <a>削除</a>
                                                    ";
            }
            // line 81
            echo "                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div><!-- /.item_box -->
                            ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['PageLayout'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 87
        echo "
                        </div>
                    </div>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
            <!-- ▲ データがある時 ▲ -->
        </div><!-- /.col -->

        <div id=\"page_list__footer\" class=\"row btn_area2\">
            <div id=\"page_list__insert_button\" class=\"col-xs-10 col-xs-offset-1 col-sm-6 col-sm-offset-3 text-center\">
                <a class=\"btn btn-primary btn-block btn-lg\" href=\"";
        // line 97
        echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_page_new");
        echo "\">新規入力</a>
            </div>
        </div>

    </div>
</div>
";
    }

    public function getTemplateName()
    {
        return "__string_template__f51c6f4db6bbc990aabf5e79d9f45b629f3f0761a34cd037f342c33a90868689";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  180 => 97,  168 => 87,  157 => 81,  153 => 79,  145 => 77,  143 => 76,  139 => 74,  135 => 72,  129 => 70,  127 => 69,  121 => 66,  116 => 64,  111 => 62,  107 => 61,  104 => 60,  99 => 59,  95 => 58,  90 => 56,  86 => 55,  81 => 53,  77 => 52,  72 => 51,  68 => 50,  48 => 32,  45 => 31,  39 => 29,  33 => 28,  29 => 24,  27 => 26,  11 => 24,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "__string_template__f51c6f4db6bbc990aabf5e79d9f45b629f3f0761a34cd037f342c33a90868689", "");
    }
}
