<?php

/* __string_template__c5788f47d9731ffe07267ea9a082e3f12b38968322e4390e8d61b0fd369b019b */
class __TwigTemplate_2823d0c2092001b6802768c7137ca2b09eb4cd6fa017ccac3754635a2c2b29d7 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 22
        $this->parent = $this->loadTemplate("default_frame.twig", "__string_template__c5788f47d9731ffe07267ea9a082e3f12b38968322e4390e8d61b0fd369b019b", 22);
        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'sub_title' => array($this, 'block_sub_title'),
            'javascript' => array($this, 'block_javascript'),
            'main' => array($this, 'block_main'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "default_frame.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 24
        $context["menus"] = array(0 => "content", 1 => "page");
        // line 29
        $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->setTheme(($context["form"] ?? null), array(0 => "Form/bootstrap_3_horizontal_layout.html.twig"));
        // line 30
        $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->setTheme(($context["list_form"] ?? null), array(0 => "Form/bootstrap_3_horizontal_layout.html.twig"));
        // line 22
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 26
    public function block_title($context, array $blocks = array())
    {
        echo "コンテンツ管理";
    }

    // line 27
    public function block_sub_title($context, array $blocks = array())
    {
        echo "レイアウト管理";
    }

    // line 32
    public function block_javascript($context, array $blocks = array())
    {
        // line 33
        echo "    <script src=\"";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "admin_urlpath", array()), "html", null, true);
        echo "/assets/js/vendor/jquery.ui/jquery.ui.core.min.js\"></script>
    <script src=\"";
        // line 34
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "admin_urlpath", array()), "html", null, true);
        echo "/assets/js/vendor/jquery.ui/jquery.ui.widget.min.js\"></script>
    <script src=\"";
        // line 35
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "admin_urlpath", array()), "html", null, true);
        echo "/assets/js/vendor/jquery.ui/jquery.ui.mouse.min.js\"></script>
    <script src=\"";
        // line 36
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "admin_urlpath", array()), "html", null, true);
        echo "/assets/js/vendor/jquery.ui/jquery.ui.sortable.min.js\"></script>
    <script src=\"";
        // line 37
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "admin_urlpath", array()), "html", null, true);
        echo "/assets/js/layout_design.js\"></script>
    <script>
        \$(function() {
            var page_id = '";
        // line 40
        echo twig_escape_filter($this->env, $this->getAttribute(($context["TargetPageLayout"] ?? null), "id", array()), "html", null, true);
        echo "';
            if (page_id != '1') {
                \$('.anywhere').attr('disabled', true);
                \$('.anywhere:checked').each(function() {
                    \$(this).parents('.sort').children('input[type=hidden]').each(function() {
                        \$(this).remove();
                    });
                });
            }

            \$(\"#";
        // line 50
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute(($context["list_form"] ?? null), "layout", array()), "vars", array()), "id", array()), "html", null, true);
        echo "\").on(\"change\", function() {
                var url = '";
        // line 51
        echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_layout_edit", array("id" => 9999));
        echo "';
                location.href = url.replace(9999, \$(this).val());
            });
        });
        function doPreview() {
            document.form1.action = \"";
        // line 56
        echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_layout_preview", array("id" => $this->getAttribute(($context["TargetPageLayout"] ?? null), "id", array()))), "html", null, true);
        echo "\";
            document.form1.target = \"_blank\";
            document.form1.submit();
            return false;
        }
        function doRegister() {
            document.form1.action = \"";
        // line 62
        echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_layout_edit", array("id" => $this->getAttribute(($context["TargetPageLayout"] ?? null), "id", array()))), "html", null, true);
        echo "\";
            document.form1.target = \"_self\";
            document.form1.submit();
            return false;
        }
    </script>
";
    }

    // line 70
    public function block_main($context, array $blocks = array())
    {
        // line 71
        echo " <div class=\"row\" id=\"aside_wrap\">
    <form name=\"form1\" id=\"form1\" method=\"post\" action=\"";
        // line 72
        echo twig_escape_filter($this->env, $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_layout_edit", array("id" => $this->getAttribute(($context["TargetPageLayout"] ?? null), "id", array()))), "html", null, true);
        echo "\">
        ";
        // line 73
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "_token", array()), 'widget');
        echo "
            <div id=\"detail_wrap\" class=\"col-md-9\">
                ";
        // line 76
        echo "                <div id=\"detail_box\" class=\"box\">
                    <div id=\"detail_box__menu\" class=\"box-header\">
                        ";
        // line 78
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\FormExtension')->renderer->searchAndRenderBlock($this->getAttribute(($context["list_form"] ?? null), "layout", array()), 'widget');
        echo "
                    </div>
                    <div id=\"detail_box__body\" class=\"box-body no-border row\">
                        <div id=\"detail_box__layout_box--left_column\" class=\"col-md-8\">
                            <div class=\"table-responsive\">
                                <table class=\"table table-bordered text-center design-layout\">
                                    <tbody>

                                    <tr>
                                        <td id=\"position_";
        // line 87
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_HEAD"), "html", null, true);
        echo "\" class=\"ui-sortable\" colspan=\"3\">
                                            ";
        // line 88
        $context["loop_index"] = 0;
        // line 89
        echo "                                            ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "HeadPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 90
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 91
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 92
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 93
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 94
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 95
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 97
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 100
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 101
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 102
        echo "                                        </td>
                                    </tr>

                                    <tr>
                                        <td id=\"position_";
        // line 106
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_HEADER"), "html", null, true);
        echo "\" class=\"ui-sortable\" colspan=\"3\">
                                            ";
        // line 107
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "HeaderPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 108
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 109
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 110
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 111
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 112
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 113
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 115
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 118
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 119
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 120
        echo "                                        </td>
                                    </tr>

                                    <tr>
                                        <td id=\"position_";
        // line 124
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_CONTENTS_TOP"), "html", null, true);
        echo "\" class=\"ui-sortable\" colspan=\"3\">
                                            ";
        // line 125
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "ContentsTopPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 126
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 127
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 128
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 129
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 130
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 131
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 133
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 136
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 137
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 138
        echo "                                        </td>
                                    </tr>

                                    <tr>
                                        <td id=\"position_";
        // line 142
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_SIDE_LEFT"), "html", null, true);
        echo "\" class=\"ui-sortable\" rowspan=\"3\">
                                            ";
        // line 143
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "SideLeftPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 144
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 145
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 146
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 147
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 148
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 149
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 151
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 154
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 155
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 156
        echo "                                        </td>
                                        <td id=\"position_";
        // line 157
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_MAIN_TOP"), "html", null, true);
        echo "\" class=\"ui-sortable\">
                                            ";
        // line 158
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "MainTopPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 159
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 160
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 161
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 162
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 163
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 164
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 166
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 169
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 170
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 171
        echo "                                        </td>
                                        <td id=\"position_";
        // line 172
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_SIDE_RIGHT"), "html", null, true);
        echo "\" class=\"ui-sortable\" rowspan=\"3\">
                                            ";
        // line 173
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "SideRightPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 174
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 175
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 176
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 177
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 178
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 179
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 181
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 184
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 185
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 186
        echo "                                        </td>
                                    </tr>

                                    <tr id=\"detail_box__layout_item_main_text\">
                                        <td>
                                            Main
                                        </td>
                                    </tr>

                                    <tr>
                                        <td id=\"position_";
        // line 196
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_MAIN_BOTTOM"), "html", null, true);
        echo "\" class=\"ui-sortable\">
                                            ";
        // line 197
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "MainBottomPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 198
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 199
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 200
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 201
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 202
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 203
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 205
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 208
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 209
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 210
        echo "                                        </td>
                                    </tr>

                                    <tr>
                                        <td id=\"position_";
        // line 214
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_CONTENTS_BOTTOM"), "html", null, true);
        echo "\" class=\"ui-sortable\" colspan=\"3\">
                                            ";
        // line 215
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "ContentsBottomPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 216
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 217
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 218
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 219
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 220
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 221
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 223
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 226
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 227
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 228
        echo "                                        </td>
                                    </tr>

                                    <tr>
                                        <td id=\"position_";
        // line 232
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_FOOTER"), "html", null, true);
        echo "\" class=\"ui-sortable\" colspan=\"3\">
                                            ";
        // line 233
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "FooterPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 234
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 235
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 236
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 237
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 238
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 239
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 241
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 244
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 245
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 246
        echo "                                        </td>
                                    </tr>

                                    </tbody>
                                </table>
                            </div>
                            ";
        // line 253
        echo "                        </div>
                        <div id=\"detail_box__layout_box--right_column\" class=\"col-md-4\">
                            <div class=\"\">
                                <table class=\"table table-bordered text-center design-layout\">
                                    <tbody>
                                    <tr>
                                        <td id=\"position_";
        // line 259
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Entity\\PageLayout::TARGET_ID_UNUSED"), "html", null, true);
        echo "\" class=\"ui-sortable\">
                                            ";
        // line 260
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["TargetPageLayout"] ?? null), "UnusedPosition", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["BlockPosition"]) {
            // line 261
            echo "                                                <div id=\"detail_box__layout_item--";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" class=\"sort";
            if ($this->getAttribute($context["loop"], "first", array())) {
                echo " first";
            }
            echo "\">
                                                    <input type=\"hidden\" class=\"name\" name=\"name_";
            // line 262
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"id\" name=\"id_";
            // line 263
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"target-id\" name=\"target_id_";
            // line 264
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "target_id", array()), "html", null, true);
            echo "\" />
                                                    <input type=\"hidden\" class=\"top\" name=\"top_";
            // line 265
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["BlockPosition"], "block_row", array()), "html", null, true);
            echo "\" />
                                                    ";
            // line 266
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["BlockPosition"], "Block", array()), "name", array()), "html", null, true);
            echo "
                                                    <label class=\"anywherecheck\">
                                                        (<input type=\"checkbox\" class=\"anywhere\" name=\"anywhere_";
            // line 268
            echo twig_escape_filter($this->env, ($context["loop_index"] ?? null), "html", null, true);
            echo "\" value=\"1\" ";
            if (($this->getAttribute($context["BlockPosition"], "anywhere", array()) == 1)) {
                echo " checked=\"checked\"";
            }
            echo " />全ページ)
                                                    </label>
                                                </div>
                                                ";
            // line 271
            $context["loop_index"] = (($context["loop_index"] ?? null) + 1);
            // line 272
            echo "                                            ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['BlockPosition'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 273
        echo "                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div><!-- /.col -->
                    </div>
                    <div id=\"detail_box__footer\" class=\"row\">
                        <div id=\"detail_box__back_button\" class=\"col-xs-10 col-xs-offset-1 col-sm-6 col-sm-offset-3 text-center btn_area2\">
                            <p><a href=\"";
        // line 282
        echo $this->env->getExtension('Eccube\Twig\Extension\EccubeExtension')->getUrl("admin_content_page");
        echo "\">戻る</a></p>
                        </div>
                    </div>
                </div>
            </div>
            <div id=\"aside_column\" class=\"col-md-3\">
                <div id=\"common_box\" class=\"col_inner\">
                    <div id=\"common_button_box\" class=\"box no-header\">
                        <div id=\"common_button_box__body\" class=\"box-body\">
                            <div id=\"common_button_box__insert_button\" class=\"row text-center\">
                                <div class=\"col-sm-6 col-sm-offset-3 col-md-12 col-md-offset-0\">
                                    <button class=\"btn btn-primary btn-block btn-lg\" onclick=\"return doRegister();\">登録</button>
                                </div>
                            </div>
                        </div><!-- /.box-body -->
                    </div><!-- /.box -->
                    <div id=\"preview_box\" class=\"box\">
                        <div id=\"preview_box__preview_button\" class=\"box-header\">
                            ";
        // line 300
        $context["disabled"] = true;
        // line 301
        echo "                            ";
        if (((((((((((($this->getAttribute(($context["TargetPageLayout"] ?? null), "url", array()) != "entry_activate") && ($this->getAttribute(        // line 302
($context["TargetPageLayout"] ?? null), "url", array()) != "shopping")) && ($this->getAttribute(        // line 303
($context["TargetPageLayout"] ?? null), "url", array()) != "shopping_shipping")) && ($this->getAttribute(        // line 304
($context["TargetPageLayout"] ?? null), "url", array()) != "shopping_shipping_multiple")) && ($this->getAttribute(        // line 305
($context["TargetPageLayout"] ?? null), "url", array()) != "shopping_complete")) && ($this->getAttribute(        // line 306
($context["TargetPageLayout"] ?? null), "url", array()) != "shopping_login")) && ($this->getAttribute(        // line 307
($context["TargetPageLayout"] ?? null), "url", array()) != "shopping_nonmember")) && ($this->getAttribute(        // line 308
($context["TargetPageLayout"] ?? null), "url", array()) != "shopping_shipping_edit")) && ($this->getAttribute(        // line 309
($context["TargetPageLayout"] ?? null), "url", array()) != "shopping_shipping_multiple_edit")) && ($this->getAttribute(        // line 310
($context["TargetPageLayout"] ?? null), "url", array()) != "shopping_error")) && ($this->getAttribute(        // line 311
($context["TargetPageLayout"] ?? null), "url", array()) != "forgot_reset"))) {
            // line 313
            echo "                            ";
            $context["disabled"] = false;
            // line 314
            echo "                            ";
        }
        // line 315
        echo "                            <button  class=\"btn btn-default btn-block btn-sm\" ";
        if ((($this->getAttribute($this->getAttribute(($context["TargetPageLayout"] ?? null), "DeviceType", array()), "id", array()) != twig_constant("Eccube\\Entity\\Master\\DeviceType::DEVICE_TYPE_PC")) || ($context["disabled"] ?? null))) {
            echo "disabled";
        }
        echo " onclick=\"return doPreview();\">
                                プレビュー
                            </button>
                        </div><!-- /.box-header -->
                    </div>

                </div>
            </div><!-- /.col -->
    </form>
</div>
";
    }

    public function getTemplateName()
    {
        return "__string_template__c5788f47d9731ffe07267ea9a082e3f12b38968322e4390e8d61b0fd369b019b";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  1122 => 315,  1119 => 314,  1116 => 313,  1114 => 311,  1113 => 310,  1112 => 309,  1111 => 308,  1110 => 307,  1109 => 306,  1108 => 305,  1107 => 304,  1106 => 303,  1105 => 302,  1103 => 301,  1101 => 300,  1080 => 282,  1069 => 273,  1055 => 272,  1053 => 271,  1043 => 268,  1038 => 266,  1032 => 265,  1026 => 264,  1020 => 263,  1014 => 262,  1005 => 261,  988 => 260,  984 => 259,  976 => 253,  968 => 246,  954 => 245,  952 => 244,  942 => 241,  937 => 239,  931 => 238,  925 => 237,  919 => 236,  913 => 235,  904 => 234,  887 => 233,  883 => 232,  877 => 228,  863 => 227,  861 => 226,  851 => 223,  846 => 221,  840 => 220,  834 => 219,  828 => 218,  822 => 217,  813 => 216,  796 => 215,  792 => 214,  786 => 210,  772 => 209,  770 => 208,  760 => 205,  755 => 203,  749 => 202,  743 => 201,  737 => 200,  731 => 199,  722 => 198,  705 => 197,  701 => 196,  689 => 186,  675 => 185,  673 => 184,  663 => 181,  658 => 179,  652 => 178,  646 => 177,  640 => 176,  634 => 175,  625 => 174,  608 => 173,  604 => 172,  601 => 171,  587 => 170,  585 => 169,  575 => 166,  570 => 164,  564 => 163,  558 => 162,  552 => 161,  546 => 160,  537 => 159,  520 => 158,  516 => 157,  513 => 156,  499 => 155,  497 => 154,  487 => 151,  482 => 149,  476 => 148,  470 => 147,  464 => 146,  458 => 145,  449 => 144,  432 => 143,  428 => 142,  422 => 138,  408 => 137,  406 => 136,  396 => 133,  391 => 131,  385 => 130,  379 => 129,  373 => 128,  367 => 127,  358 => 126,  341 => 125,  337 => 124,  331 => 120,  317 => 119,  315 => 118,  305 => 115,  300 => 113,  294 => 112,  288 => 111,  282 => 110,  276 => 109,  267 => 108,  250 => 107,  246 => 106,  240 => 102,  226 => 101,  224 => 100,  214 => 97,  209 => 95,  203 => 94,  197 => 93,  191 => 92,  185 => 91,  176 => 90,  158 => 89,  156 => 88,  152 => 87,  140 => 78,  136 => 76,  131 => 73,  127 => 72,  124 => 71,  121 => 70,  110 => 62,  101 => 56,  93 => 51,  89 => 50,  76 => 40,  70 => 37,  66 => 36,  62 => 35,  58 => 34,  53 => 33,  50 => 32,  44 => 27,  38 => 26,  34 => 22,  32 => 30,  30 => 29,  28 => 24,  11 => 22,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "__string_template__c5788f47d9731ffe07267ea9a082e3f12b38968322e4390e8d61b0fd369b019b", "");
    }
}
