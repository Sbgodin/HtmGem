<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__)."/../lib-htmgem.inc.php";
require_once dirname(__FILE__)."/../lib-html.inc.php";

final class securityTest extends TestCase {

    /**
     * Test that XSS attempts in alt attribute of code blocks are escaped
     */
    public function test_xss_in_alt_attribute(): void {
        $gemtext = "```<script>alert('XSS')</script>\nsome code\n```";
        $gt_html = new \htmgem\GemTextTranslate_html($gemtext);
        $output = $gt_html->translatedGemtext;
        
        // Should escape the script tag in alt attribute
        $this->assertStringNotContainsString("<script>alert('XSS')</script>", $output);
        $this->assertStringContainsString("&lt;script&gt;", $output);
    }

    /**
     * Test that XSS attempts in CSS href are escaped
     */
    public function test_xss_in_css_href(): void {
        $gemtext = "# Test Page";
        $gt_html = new \htmgem\GemTextTranslate_html($gemtext);
        $gt_html->addCss("javascript:alert('XSS')");
        
        $header = \htmgem\html\getHeader($gt_html);
        
        // Should escape quotes to prevent breaking out of attribute
        $this->assertStringNotContainsString("alert('XSS')", $header);
        $this->assertStringContainsString("&#039;", $header); // Escaped single quote
    }

    /**
     * Test that XSS attempts in menu links are escaped
     */
    public function test_xss_in_menu_links(): void {
        $path = "/test<script>alert('XSS')</script>.gmi";
        $menu = \htmgem\html\getMenu("https://", "example.com", $path, "/htmgem?url=");
        
        // Should escape the script tag
        $this->assertStringNotContainsString("<script>alert('XSS')</script>", $menu);
        $this->assertStringContainsString("&lt;script&gt;", $menu);
    }

    /**
     * Test that title with special characters is escaped
     */
    public function test_title_escaping(): void {
        $gemtext = "# Test <b>Bold</b> & \"Quotes\"";
        $gt_html = new \htmgem\GemTextTranslate_html($gemtext);
        $output = $gt_html->translatedGemtext;
        
        // Should escape HTML entities
        $this->assertStringContainsString("&lt;b&gt;", $output);
        $this->assertStringContainsString("&amp;", $output);
        $this->assertStringContainsString("&quot;", $output);
    }

    /**
     * Test path traversal protection in resolve_path
     */
    public function test_path_traversal_protection(): void {
        // Attempting to go above root should return "/"
        $result = \htmgem\resolve_path("/../../../etc/passwd");
        $this->assertEquals("/", $result);
        
        $result = \htmgem\resolve_path("/test/../../..");
        $this->assertEquals("/", $result);
    }

    /**
     * Test that link URLs are properly escaped
     */
    public function test_link_url_escaping(): void {
        $gemtext = "=> http://example.com/test?a=1&b=2 Test Link";
        $gt_html = new \htmgem\GemTextTranslate_html($gemtext);
        $output = $gt_html->translatedGemtext;
        
        // Should escape the ampersand in the URL
        $this->assertStringContainsString("&amp;", $output);
    }
}
