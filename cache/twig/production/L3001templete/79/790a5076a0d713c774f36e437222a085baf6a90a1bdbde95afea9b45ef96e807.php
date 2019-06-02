<?php

/* __string_template__325c66e8587cc10e0a12b4256a7371b80875f9af3c5fccc92d41ea2531ef21f5 */
class __TwigTemplate_ada308288ea9b4719de21ba5887a12b1566f7fb7118a3cccad1aa55771a8e6f8 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 22
        $this->parent = $this->loadTemplate("default_frame.twig", "__string_template__325c66e8587cc10e0a12b4256a7371b80875f9af3c5fccc92d41ea2531ef21f5", 22);
        $this->blocks = array(
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
        $context["body_class"] = "front_page";
        // line 22
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 26
    public function block_javascript($context, array $blocks = array())
    {
        // line 27
        echo "        <script>
            \$(function(){
                \$('.main_visual').slick({
                    dots: true,
                    arrows: false,
                    autoplay: true,
                    speed: 300
                });
            });
        </script>
    ";
    }

    // line 39
    public function block_main($context, array $blocks = array())
    {
        // line 40
        echo "        <div class=\"row\">

            <div class=\"main_visual col-xs-12\">
                <div class=\"item\">
                  <img src=\"http://ec2-52-196-89-87.ap-northeast-1.compute.amazonaws.com/ZaCca_admin/content/file_view?file=%2Fvar%2Fwww%2Fhtml%2Feccube-3%2Fhtml%2Fuser_data%2FTop-%E5%A4%A7%E9%98%AAOPEN%E7%94%A8_%E3%82%B5%E3%82%A4%E3%82%BA%E8%AA%BF%E6%95%B4.jpg\">
              </div>
              <div class=\"item\">
                  <img src=\"";
        // line 47
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["app"] ?? null), "config", array()), "front_urlpath", array()), "html", null, true);
        echo "/img/top/main-visual-img02.jpg\">
              </div>
          </div>
    
<h1> test text</h1>
      </div>
  ";
    }

    public function getTemplateName()
    {
        return "__string_template__325c66e8587cc10e0a12b4256a7371b80875f9af3c5fccc92d41ea2531ef21f5";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  61 => 47,  52 => 40,  49 => 39,  35 => 27,  32 => 26,  28 => 22,  26 => 24,  11 => 22,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "__string_template__325c66e8587cc10e0a12b4256a7371b80875f9af3c5fccc92d41ea2531ef21f5", "");
    }
}
