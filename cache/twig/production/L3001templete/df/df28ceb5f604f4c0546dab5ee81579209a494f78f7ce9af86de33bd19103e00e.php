<?php

/* default_frame.twig */
class __TwigTemplate_1965423c5a7838d0df875bd7e87f4b1c8f4981cd69f1e82643e29d0536109a27 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
            'meta_tags' => array($this, 'block_meta_tags'),
            'stylesheet' => array($this, 'block_stylesheet'),
            'main' => array($this, 'block_main'),
            'javascript' => array($this, 'block_javascript'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!doctype html>
";
        // line 23
        echo "<html lang=\"ja\">
<head>
<meta charset=\"utf-8\">
<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
<title>";
        // line 27
        echo twig_escape_filter($this->env, $this->getAttribute(($context["BaseInfo"] ?? null), "shop_name", array()), "html", null, true);
        if ((array_key_exists("subtitle", $context) &&  !twig_test_empty(($context["subtitle"] ?? null)))) {
            echo " / ";
            echo twig_escape_filter($this->env, ($context["subtitle"] ?? null), "html", null, true);
        } elseif ((array_key_exists("title", $context) &&  !twig_test_empty(($context["title"] ?? null)))) {
            echo " / ";
            echo twig_escape_filter($this->env, ($context["title"] ?? null), "html", null, true);
        }
        echo "</title>
";
        // line 28
        if ( !twig_test_empty($this->getAttribute(($context["PageLayout"] ?? null), "author", array()))) {
            // line 29
            echo "    <meta name=\"author\" content=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["PageLayout"] ?? null), "author", array()), "html", null, true);
            echo "\">
";
        }
        // line 31
        if ( !twig_test_empty($this->getAttribute(($context["PageLayout"] ?? null), "description", array()))) {
            // line 32
            echo "    <meta name=\"description\" content=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["PageLayout"] ?? null), "description", array()), "html", null, true);
            echo "\">
";
        }
        // line 34
        if ( !twig_test_empty($this->getAttribute(($context["PageLayout"] ?? null), "keyword", array()))) {
            // line 35
            echo "    <meta name=\"keywords\" content=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["PageLayout"] ?? null), "keyword", array()), "html", null, true);
            echo "\">
";
        }
        // line 37
        if ( !twig_test_empty($this->getAttribute(($context["PageLayout"] ?? null), "meta_robots", array()))) {
            // line 38
            echo "    <meta name=\"robots\" content=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["PageLayout"] ?? null), "meta_robots", array()), "html", null, true);
            echo "\">
";
        }
        // line 40
        echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    ";
        // line 41
        if ( !twig_test_empty($this->getAttribute(($context["PageLayout"] ?? null), "meta_tags", array()))) {
            // line 42
            echo "        ";
            echo $this->getAttribute(($context["PageLayout"] ?? null), "meta_tags", array());
            echo "
    ";
        }
        // line 44
        echo "    ";
        $this->displayBlock('meta_tags', $context, $blocks);
        // line 45
        echo "<link rel=\"icon\" href=\"";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/img/common/favicon.ico\">
<link rel=\"stylesheet\" href=\"";
        // line 46
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/css/style.css?v=";
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Common\\Constant::VERSION"), "html", null, true);
        echo "\">
<link rel=\"stylesheet\" href=\"";
        // line 47
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/css/slick.css?v=";
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Common\\Constant::VERSION"), "html", null, true);
        echo "\">
<link rel=\"stylesheet\" href=\"";
        // line 48
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/css/default.css?v=";
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Common\\Constant::VERSION"), "html", null, true);
        echo "\">
<!-- for original theme CSS -->
";
        // line 50
        $this->displayBlock('stylesheet', $context, $blocks);
        // line 51
        echo "
<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js\"></script>
<script>window.jQuery || document.write('<script src=\"";
        // line 53
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/js/vendor/jquery-1.11.3.min.js?v=";
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Common\\Constant::VERSION"), "html", null, true);
        echo "\"><\\/script>')</script>

";
        // line 56
        if ($this->getAttribute(($context["PageLayout"] ?? null), "Head", array())) {
            // line 57
            echo "    ";
            // line 58
            echo "    ";
            echo twig_include($this->env, $context, "block.twig", array("Blocks" => $this->getAttribute(($context["PageLayout"] ?? null), "Head", array())));
            echo "
    ";
        }
        // line 62
        echo "
</head>
<body id=\"page_";
        // line 64
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "request", array()), "get", array(0 => "_route"), "method"), "html", null, true);
        echo "\" class=\"";
        echo twig_escape_filter($this->env, ((array_key_exists("body_class", $context)) ? (_twig_default_filter(($context["body_class"] ?? null), "other_page")) : ("other_page")), "html", null, true);
        echo "\">
<div id=\"wrapper\">
    <header id=\"header\">
        <div class=\"container-fluid inner\">
            <div class=\"row\">
            ";
        // line 70
        echo "            ";
        if ($this->getAttribute(($context["PageLayout"] ?? null), "Header", array())) {
            // line 71
            echo "                ";
            // line 72
            echo "                ";
            echo twig_include($this->env, $context, "block.twig", array("Blocks" => $this->getAttribute(($context["PageLayout"] ?? null), "Header", array())));
            echo "
                ";
            // line 74
            echo "            ";
        }
        // line 75
        echo "            ";
        // line 76
        echo "            <p id=\"btn_menu\"><a class=\"nav-trigger\" href=\"#nav\">Menu<span></span></a></p>
        </div>
        </div>
    </header>

    <div id=\"contents\" class=\"";
        // line 81
        echo twig_escape_filter($this->env, $this->getAttribute(($context["PageLayout"] ?? null), "theme", array()), "html", null, true);
        echo "\">

        <div id=\"contents_top\">
            ";
        // line 85
        echo "            ";
        if ($this->getAttribute(($context["PageLayout"] ?? null), "ContentsTop", array())) {
            // line 86
            echo "                ";
            // line 87
            echo "                ";
            echo twig_include($this->env, $context, "block.twig", array("Blocks" => $this->getAttribute(($context["PageLayout"] ?? null), "ContentsTop", array())));
            echo "
                ";
            // line 89
            echo "            ";
        }
        // line 90
        echo "            ";
        // line 91
        echo "        </div>

        <div class=\"container-fluid inner\">
            ";
        // line 95
        echo "            ";
        if ($this->getAttribute(($context["PageLayout"] ?? null), "SideLeft", array())) {
            // line 96
            echo "                <div id=\"side_left\" class=\"side\">
                    ";
            // line 98
            echo "                    ";
            echo twig_include($this->env, $context, "block.twig", array("Blocks" => $this->getAttribute(($context["PageLayout"] ?? null), "SideLeft", array())));
            echo "
                    ";
            // line 100
            echo "                </div>
            ";
        }
        // line 102
        echo "            ";
        // line 103
        echo "
            <div id=\"main\">
                ";
        // line 106
        echo "                ";
        if ($this->getAttribute(($context["PageLayout"] ?? null), "MainTop", array())) {
            // line 107
            echo "                    <div id=\"main_top\">
                        ";
            // line 108
            echo twig_include($this->env, $context, "block.twig", array("Blocks" => $this->getAttribute(($context["PageLayout"] ?? null), "MainTop", array())));
            echo "
                    </div>
                ";
        }
        // line 111
        echo "                ";
        // line 112
        echo "
                <div id=\"main_middle\">
                    ";
        // line 114
        $this->displayBlock('main', $context, $blocks);
        // line 115
        echo "                </div>

                ";
        // line 118
        echo "                ";
        if ($this->getAttribute(($context["PageLayout"] ?? null), "MainBottom", array())) {
            // line 119
            echo "                    <div id=\"main_bottom\">
                        ";
            // line 120
            echo twig_include($this->env, $context, "block.twig", array("Blocks" => $this->getAttribute(($context["PageLayout"] ?? null), "MainBottom", array())));
            echo "
                    </div>
                ";
        }
        // line 123
        echo "                ";
        // line 124
        echo "            </div>

            ";
        // line 127
        echo "            ";
        if ($this->getAttribute(($context["PageLayout"] ?? null), "SideRight", array())) {
            // line 128
            echo "                <div id=\"side_right\" class=\"side\">
                    ";
            // line 130
            echo "                    ";
            echo twig_include($this->env, $context, "block.twig", array("Blocks" => $this->getAttribute(($context["PageLayout"] ?? null), "SideRight", array())));
            echo "
                    ";
            // line 132
            echo "                </div>
            ";
        }
        // line 134
        echo "            ";
        // line 135
        echo "
            ";
        // line 137
        echo "            ";
        if ($this->getAttribute(($context["PageLayout"] ?? null), "ContentsBottom", array())) {
            // line 138
            echo "                <div id=\"contents_bottom\">
                    ";
            // line 140
            echo "                    ";
            echo twig_include($this->env, $context, "block.twig", array("Blocks" => $this->getAttribute(($context["PageLayout"] ?? null), "ContentsBottom", array())));
            echo "
                    ";
            // line 142
            echo "                </div>
            ";
        }
        // line 144
        echo "            ";
        // line 145
        echo "
        </div>

        <footer id=\"footer\">
            ";
        // line 150
        echo "            ";
        if ($this->getAttribute(($context["PageLayout"] ?? null), "Footer", array())) {
            // line 151
            echo "                ";
            // line 152
            echo "                ";
            echo twig_include($this->env, $context, "block.twig", array("Blocks" => $this->getAttribute(($context["PageLayout"] ?? null), "Footer", array())));
            echo "
                ";
            // line 154
            echo "            ";
        }
        // line 155
        echo "            ";
        // line 156
        echo "
        </footer>

    </div>

    <div id=\"drawer\" class=\"drawer sp\">
    </div>

</div>

<div class=\"overlay\"></div>

<script src=\"";
        // line 168
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/js/vendor/bootstrap.custom.min.js?v=";
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Common\\Constant::VERSION"), "html", null, true);
        echo "\"></script>
<script src=\"";
        // line 169
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/js/vendor/slick.min.js?v=";
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Common\\Constant::VERSION"), "html", null, true);
        echo "\"></script>
<script src=\"";
        // line 170
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/js/function.js?v=";
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Common\\Constant::VERSION"), "html", null, true);
        echo "\"></script>
<script src=\"";
        // line 171
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/js/eccube.js?v=";
        echo twig_escape_filter($this->env, twig_constant("Eccube\\Common\\Constant::VERSION"), "html", null, true);
        echo "\"></script>
<script>
\$(function () {
    \$('#drawer').append(\$('.drawer_block').clone(true).children());
    \$.ajax({
        url: '";
        // line 176
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/img/common/svg.html',
        type: 'GET',
        dataType: 'html',
    }).done(function(data){
        \$('body').prepend(data);
    }).fail(function(data){
    });
});
</script>
";
        // line 185
        $this->displayBlock('javascript', $context, $blocks);
        // line 186
        echo "</body>
</html>
";
    }

    // line 44
    public function block_meta_tags($context, array $blocks = array())
    {
    }

    // line 50
    public function block_stylesheet($context, array $blocks = array())
    {
    }

    // line 114
    public function block_main($context, array $blocks = array())
    {
    }

    // line 185
    public function block_javascript($context, array $blocks = array())
    {
    }

    public function getTemplateName()
    {
        return "default_frame.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  396 => 185,  391 => 114,  386 => 50,  381 => 44,  375 => 186,  373 => 185,  361 => 176,  351 => 171,  345 => 170,  339 => 169,  333 => 168,  319 => 156,  317 => 155,  314 => 154,  309 => 152,  307 => 151,  304 => 150,  298 => 145,  296 => 144,  292 => 142,  287 => 140,  284 => 138,  281 => 137,  278 => 135,  276 => 134,  272 => 132,  267 => 130,  264 => 128,  261 => 127,  257 => 124,  255 => 123,  249 => 120,  246 => 119,  243 => 118,  239 => 115,  237 => 114,  233 => 112,  231 => 111,  225 => 108,  222 => 107,  219 => 106,  215 => 103,  213 => 102,  209 => 100,  204 => 98,  201 => 96,  198 => 95,  193 => 91,  191 => 90,  188 => 89,  183 => 87,  181 => 86,  178 => 85,  172 => 81,  165 => 76,  163 => 75,  160 => 74,  155 => 72,  153 => 71,  150 => 70,  140 => 64,  136 => 62,  130 => 58,  128 => 57,  126 => 56,  119 => 53,  115 => 51,  113 => 50,  106 => 48,  100 => 47,  94 => 46,  89 => 45,  86 => 44,  80 => 42,  78 => 41,  75 => 40,  69 => 38,  67 => 37,  61 => 35,  59 => 34,  53 => 32,  51 => 31,  45 => 29,  43 => 28,  32 => 27,  26 => 23,  23 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "default_frame.twig", "/var/www/html/eccube-3/app/template/L3001templete/default_frame.twig");
    }
}
