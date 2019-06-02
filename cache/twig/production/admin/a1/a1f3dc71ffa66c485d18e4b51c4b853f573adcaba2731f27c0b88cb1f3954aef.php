<?php

/* __string_template__927ceb5036d4a3c3b35313effc21b37b1c711444625d05b71428f8f86b1bd171 */
class __TwigTemplate_e35bccad25944b2486d89fe89399589a551ba00437f90a271e0cd473a5b59a6e extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 24
        $this->parent = $this->loadTemplate("default_frame.twig", "__string_template__927ceb5036d4a3c3b35313effc21b37b1c711444625d05b71428f8f86b1bd171", 24);
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
        // line 31
        $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->setTheme(($context["form"] ?? null), array(0 => "Form/bootstrap_3_horizontal_layout.html.twig"));
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

    // line 33
    public function block_main($context, array $blocks = array())
    {
        // line 34
        echo "<div class=\"row\" id=\"aside_wrap\">
    <form role=\"form\" name=\"content_page_form\" id=\"content_page_form\" method=\"post\"
          action=\"";
        // line 36
        if ( !(null === ($context["page_id"] ?? null))) {
            echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_page_edit", array("id" => ($context["page_id"] ?? null))), "html", null, true);
        } else {
            echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_page_new");
        }
        echo "\">
        ";
        // line 37
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "_token", array()), 'widget');
        echo "
        ";
        // line 38
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "id", array()), 'widget');
        echo "
        ";
        // line 39
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "DeviceType", array()), 'widget', array("attr" => array("style" => "display: none;")));
        echo "
            <div id=\"detail\" class=\"col-md-9\">
                <div id=\"detail_box\" class=\"box form-horizontal\">
                    <div id=\"detail_box__header\" class=\"box-header\">
                        <h3 class=\"box-title\">ページ詳細編集</h3>
                    </div>
                    <!-- /.box-header -->
                    <div id=\"detail_box__body\" class=\"box-body\">

                        <div id=\"detail_box__name\" class=\"form-group\">
                            ";
        // line 49
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "name", array()), 'label');
        echo "
                            <div class=\"col-sm-9 col-lg-10\">
                                ";
        // line 51
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "name", array()), 'widget');
        echo "
                                ";
        // line 52
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "name", array()), 'errors');
        echo "
                            </div>
                        </div>
                        <div id=\"detail_box__url\" class=\"form-group word-break\">
                            ";
        // line 56
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "url", array()), 'label');
        echo "
                            <div class=\"col-sm-9 col-lg-10 form-inline\">
                                ";
        // line 58
        if (($context["editable"] ?? null)) {
            // line 59
            echo "                                    ";
            echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("top");
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "user_data_route", array()), "html", null, true);
            echo "/";
            echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "url", array()), 'widget');
            echo "
                                ";
        } else {
            // line 61
            echo "                                    ";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "request", array()), "schemeAndHttpHost", array()), "html", null, true);
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "request", array()), "basePath", array()), "html", null, true);
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["app"] ?? null), "routes", array()), "get", array(0 => $this->getAttribute($this->getAttribute($this->getAttribute(($context["form"] ?? null), "url", array()), "vars", array()), "value", array())), "method"), "path", array()), "html", null, true);
            echo "
                                    ";
            // line 62
            echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "url", array()), 'widget', array("type" => "hidden"));
            echo "
                                ";
        }
        // line 64
        echo "                                ";
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "url", array()), 'errors');
        echo "
                            </div>
                        </div>
                        <div id=\"detail_box__file_name\" class=\"form-group word-break\">
                            ";
        // line 68
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "file_name", array()), 'label');
        echo "
                            <div class=\"col-sm-9 col-lg-10 form-inline\">
                                ";
        // line 70
        if (($context["editable"] ?? null)) {
            // line 71
            echo "                                    ";
            echo twig_escape_filter($this->env, ($context["template_path"] ?? null), "html", null, true);
            echo "/";
            echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "file_name", array()), 'widget');
            echo ".twig
                                ";
        } else {
            // line 73
            echo "                                    ";
            echo twig_escape_filter($this->env, ($context["template_path"] ?? null), "html", null, true);
            echo "/";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["form"] ?? null), "file_name", array()), "vars", array()), "value", array()), "html", null, true);
            echo ".twig
                                    ";
            // line 74
            echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "file_name", array()), 'widget', array("type" => "hidden"));
            echo "
                                ";
        }
        // line 76
        echo "                                ";
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "file_name", array()), 'errors');
        echo "
                            </div>
                        </div>

                        <div id=\"detail_box__tpl_data\" class=\"form-group\">
                            ";
        // line 81
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "tpl_data", array()), 'label');
        echo "
                            <div class=\"col-sm-9 col-lg-10\">
                                ";
        // line 83
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "tpl_data", array()), 'widget', array("attr" => array("rows" => 15, "style" => "font-size:12px")));
        echo "
                                ";
        // line 84
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "tpl_data", array()), 'errors');
        echo "
                            </div>
                        </div>

                        <div class=\"extra-form\">
                            ";
        // line 89
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["form"] ?? null), "getIterator", array()));
        foreach ($context['_seq'] as $context["_key"] => $context["f"]) {
            // line 90
            echo "                                ";
            if (preg_match("[^plg*]", $this->getAttribute($this->getAttribute($context["f"], "vars", array()), "name", array()))) {
                // line 91
                echo "                                    ";
                echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($context["f"], 'row');
                echo "
                                ";
            }
            // line 93
            echo "                            ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['f'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 94
        echo "                        </div>
                    </div>
                </div>
                <div id=\"detail_meta_box\" class=\"box form-horizontal\">
                    <div id=\"detail_meta_box__header\" class=\"box-header\">
                        <h3 class=\"box-title\">
                            meta設定
                        </h3>
                    </div>
                    <!-- /.box-header -->

                    <div id=\"detail_meta_box__body\" class=\"box-body\">
                        ";
        // line 106
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "author", array()), 'row');
        echo "
                        ";
        // line 107
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "description", array()), 'row');
        echo "
                        ";
        // line 108
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "keyword", array()), 'row');
        echo "
                        ";
        // line 109
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "meta_robots", array()), 'row');
        echo "
                        ";
        // line 110
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "meta_tags", array()), 'row', array("attr" => array("placeholder" => "複数のmetaタグを入力可能")));
        echo "
                    </div>
                </div>

                <div id=\"detail_box__footer\" class=\"row hidden-xs hidden-sm\">
                    <div id=\"detail_box__back_button\" class=\"col-xs-10 col-xs-offset-1 col-sm-6 col-sm-offset-3 text-center btn_area\">
                        <p><a href=\"";
        // line 116
        echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_page");
        echo "\">戻る</a></p>
                    </div>
                </div>
            </div>
            <!-- /.col -->
            <div class=\"col-md-3\" id=\"aside_column\">
                <div id=\"common_box\" class=\"col_inner\">
                    <div id=\"common_button_box\" class=\"box no-header\">
                        <div id=\"common_button_box__body\" class=\"box-body\">
                            <div id=\"common_button_box__insert_button\" class=\"row text-center\">
                                <div class=\"col-sm-6 col-sm-offset-3 col-md-12 col-md-offset-0\">
                                    <button class=\"btn btn-primary btn-block btn-lg\" type=\"submit\">登録</button>
                                </div>
                            </div>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
            </div>
            <!-- /.col -->
    </form>
</div>
";
    }

    public function getTemplateName()
    {
        return "__string_template__927ceb5036d4a3c3b35313effc21b37b1c711444625d05b71428f8f86b1bd171";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  246 => 116,  237 => 110,  233 => 109,  229 => 108,  225 => 107,  221 => 106,  207 => 94,  201 => 93,  195 => 91,  192 => 90,  188 => 89,  180 => 84,  176 => 83,  171 => 81,  162 => 76,  157 => 74,  150 => 73,  142 => 71,  140 => 70,  135 => 68,  127 => 64,  122 => 62,  115 => 61,  106 => 59,  104 => 58,  99 => 56,  92 => 52,  88 => 51,  83 => 49,  70 => 39,  66 => 38,  62 => 37,  54 => 36,  50 => 34,  47 => 33,  41 => 29,  35 => 28,  31 => 24,  29 => 31,  27 => 26,  11 => 24,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "__string_template__927ceb5036d4a3c3b35313effc21b37b1c711444625d05b71428f8f86b1bd171", "");
    }
}
