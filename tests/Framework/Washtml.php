<?php

/**
 * Test class to test rcube_washtml class
 *
 * @package Tests
 */
class Framework_Washtml extends PHPUnit_Framework_TestCase
{

    /**
     * Test the elimination of some XSS vulnerabilities
     */
    function test_html_xss3()
    {
        // #1488850
        $html = '<p><a href="data:text/html,&lt;script&gt;alert(document.cookie)&lt;/script&gt;">Firefox</a>'
            .'<a href="vbscript:alert(document.cookie)">Internet Explorer</a></p>';

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertNotRegExp('/data:text/', $washed, "Remove data:text/html links");
        $this->assertNotRegExp('/vbscript:/', $washed, "Remove vbscript: links");
    }

    /**
     * Test fixing of invalid href (#1488940)
     */
    function test_href()
    {
        $html = "<p><a href=\"\nhttp://test.com\n\">Firefox</a>";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertRegExp('|href="http://test.com">|', $washed, "Link href with newlines (#1488940)");
    }

    /**
     * Test XSS in area's href (#5240)
     */
    function test_href_area()
    {
        $html = '<p><area href="data:text/html,&lt;script&gt;alert(document.cookie)&lt;/script&gt;">'
            . '<area href="vbscript:alert(document.cookie)">Internet Explorer</p>'
            . '<area href="javascript:alert(document.domain)" shape=default>';

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertNotRegExp('/data:text/', $washed, "data:text/html in area href");
        $this->assertNotRegExp('/vbscript:/', $washed, "vbscript: in area href");
        $this->assertNotRegExp('/javascript:/', $washed, "javascript: in area href");
    }

    /**
     * Test handling HTML comments
     */
    function test_comments()
    {
        $washer = new rcube_washtml;

        $html   = "<!--[if gte mso 10]><p>p1</p><!--><p>p2</p>";
        $washed = $washer->wash($html);

        $this->assertEquals('<!-- html ignored --><!-- body ignored --><p>p2</p>', $washed, "HTML conditional comments (#1489004)");

        $html   = "<!--TestCommentInvalid><p>test</p>";
        $washed = $washer->wash($html);

        $this->assertEquals('<!-- html ignored --><!-- body ignored --><p>test</p>', $washed, "HTML invalid comments (#1487759)");

        $html   = "<p>para1</p><!-- comment --><p>para2</p>";
        $washed = $washer->wash($html);

        $this->assertEquals('<!-- html ignored --><!-- body ignored --><p>para1</p><p>para2</p>', $washed, "HTML comments - simple comment");

        $html   = "<p>para1</p><!-- <hr> comment --><p>para2</p>";
        $washed = $washer->wash($html);

        $this->assertEquals('<!-- html ignored --><!-- body ignored --><p>para1</p><p>para2</p>', $washed, "HTML comments - tags inside (#1489904)");
    }

    /**
     * Test fixing of invalid self-closing elements (#1489137)
     */
    function test_self_closing()
    {
        $html = "<textarea>test";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertRegExp('|<textarea>test</textarea>|', $washed, "Self-closing textarea (#1489137)");
    }

    /**
     * Test fixing of invalid closing tags (#1489446)
     */
    function test_closing_tag_attrs()
    {
        $html = "<a href=\"http://test.com\">test</a href>";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertRegExp('|</a>|', $washed, "Invalid closing tag (#1489446)");
    }

    /**
     * Test fixing of invalid lists nesting (#1488768)
     */
    function test_lists()
    {
        $data = array(
            array(
                "<ol><li>First</li><li>Second</li><ul><li>First sub</li></ul><li>Third</li></ol>",
                "<ol><li>First</li><li>Second<ul><li>First sub</li></ul></li><li>Third</li></ol>"
            ),
            array(
                "<ol><li>First<ul><li>First sub</li></ul></li></ol>",
                "<ol><li>First<ul><li>First sub</li></ul></li></ol>",
            ),
            array(
                "<ol><li>First<ol><li>First sub</li></ol></li></ol>",
                "<ol><li>First<ol><li>First sub</li></ol></li></ol>",
            ),
            array(
                "<ul><li>First</li><ul><li>First sub</li><ul><li>sub sub</li></ul></ul><li></li></ul>",
                "<ul><li>First<ul><li>First sub<ul><li>sub sub</li></ul></li></ul></li><li></li></ul>",
            ),
            array(
                "<ul><li>First</li><li>second</li><ul><ul><li>sub sub</li></ul></ul></ul>",
                "<ul><li>First</li><li>second<ul><ul><li>sub sub</li></ul></ul></li></ul>",
            ),
            array(
                "<ol><ol><ol></ol></ol></ol>",
                "<ol><ol><ol></ol></ol></ol>",
            ),
            array(
                "<div><ol><ol><ol></ol></ol></ol></div>",
                "<div><ol><ol><ol></ol></ol></ol></div>",
            ),
        );

        foreach ($data as $element) {
            rcube_washtml::fix_broken_lists($element[0]);

            $this->assertSame($element[1], $element[0], "Broken nested lists (#1488768)");
        }
    }

    /**
     * Test color style handling (#1489697)
     */
    function test_color_style()
    {
        $html = "<p style=\"font-size: 10px; color: rgb(241, 245, 218)\">a</p>";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertRegExp('|color: rgb\(241, 245, 218\)|', $washed, "Color style (#1489697)");
        $this->assertRegExp('|font-size: 10px|', $washed, "Font-size style");
    }

    /**
     * Test handling of unicode chars in style (#1489777)
     */
    function test_style_unicode()
    {
        $html = "<html><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
            <body><span style='font-family:\"新細明體\",\"serif\";color:red'>test</span></body></html>";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertRegExp('|style="font-family: \&quot;新細明體\&quot;,\&quot;serif\&quot;; color: red"|', $washed, "Unicode chars in style attribute - quoted (#1489697)");

        $html = "<html><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
            <body><span style='font-family:新細明體;color:red'>test</span></body></html>";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertRegExp('|style="font-family: 新細明體; color: red"|', $washed, "Unicode chars in style attribute (#1489697)");
    }

    /**
     * Test style item fixes
     */
    function test_style_wash()
    {
        $html = "<p style=\"line-height: 1; height: 10\">a</p>";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertRegExp('|line-height: 1;|', $washed, "Untouched line-height (#1489917)");
        $this->assertRegExp('|; height: 10px|', $washed, "Fixed height units");

        $html     = "<div style=\"padding: 0px\n   20px;border:1px solid #000;\"></div>";
        $expected = "<div style=\"padding: 0px 20px; border: 1px solid #000\"></div>";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertTrue(strpos($washed, $expected) !== false, "White-space and new-line characters handling");
    }

    /**
     * Test invalid style cleanup - XSS prevention (#1490227)
     */
    function test_style_wash_xss()
    {
        $html = "<img style=aaa:'\"/onerror=alert(1)//'>";
        $exp  = "<img style=\"aaa: '&quot;/onerror=alert(1)//'\" />";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertTrue(strpos($washed, $exp) !== false, "Style quotes XSS issue (#1490227)");

        $html = "<img style=aaa:'&quot;/onerror=alert(1)//'>";
        $exp  = "<img style=\"aaa: '&quot;/onerror=alert(1)//'\" />";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertTrue(strpos($washed, $exp) !== false, "Style quotes XSS issue (#1490227)");
    }

    /**
     * Test SVG cleanup
     */
    function test_style_wash_svg()
    {
        $svg = '<?xml version="1.0" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" baseProfile="full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" viewBox="0 0 100 100">
  <polygon id="triangle" points="0,0 0,50 50,0" fill="#009900" stroke="#004400" onmouseover="alert(1)" />
  <text x="50" y="68" font-size="48" fill="#FFF" text-anchor="middle"><![CDATA[410]]></text>
  <script type="text/javascript">
    alert(document.cookie);
  </script>
  <text x="10" y="25" >An example text</text>
  <a xlink:href="http://www.w.pl"><rect width="100%" height="100%" /></a>
  <foreignObject xlink:href="data:text/xml,%3Cscript xmlns=\'http://www.w3.org/1999/xhtml\'%3Ealert(1)%3C/script%3E"/>
  <set attributeName="onmouseover" to="alert(1)"/>
  <animate attributeName="onunload" to="alert(1)"/>
  <animate attributeName="xlink:href" begin="0" from="javascript:alert(1)" />
</svg>';

        $exp = '<svg xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" version="1.1" baseProfile="full" viewBox="0 0 100 100">
  <polygon id="triangle" points="0,0 0,50 50,0" fill="#009900" stroke="#004400" x-washed="onmouseover" />
  <text x="50" y="68" font-size="48" fill="#FFF" text-anchor="middle">410</text>
  <!-- script not allowed -->
  <text x="10" y="25">An example text</text>
  <a xlink:href="http://www.w.pl"><rect width="100%" height="100%" /></a>
  <!-- foreignObject ignored -->
  <set attributeName="onmouseover" x-washed="to" />
  <animate attributeName="onunload" x-washed="to" />
  <animate attributeName="xlink:href" begin="0" x-washed="from" />
</svg>';

        $washer = new rcube_washtml;
        $washed = $washer->wash($svg);

        $this->assertSame($washed, $exp, "SVG content");
    }

    /**
     * Test position:fixed cleanup - (#5264)
     */
    function test_style_wash_position_fixed()
    {
        $html = "<img style='position:fixed' /><img style=\"position:/**/ fixed; top:10px\" />";
        $exp  = "<img style=\"position: absolute\" /><img style=\"position: absolute; top: 10px\" />";

        $washer = new rcube_washtml;
        $washed = $washer->wash($html);

        $this->assertTrue(strpos($washed, $exp) !== false, "Position:fixed (#5264)");
    }
}
