<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__)."/../lib-htmgem.inc.php";

final class miscTest extends TestCase {

    public function test_resolveLink(): void {
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path(""),
            "",
            "empty link"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("test"),
            "test",
            "single word"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path(" "),
            " ",
            "single space"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path(" A B "),
            " A B ",
            "several space"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("/"),
            "/",
            "one slash"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("//"),
            "/",
            "two slashes"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("/////"),
            "/",
            "five slashes"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("one/"),
            "one",
            "strip the last slash"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("/two"),
            "/two",
            "slash at the beginning"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("/two/"),
            "/two",
            "slash at the beginning and the end"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("one/two/"),
            "one/two",
            "only the last slash remains"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("one/two/three//"),
            "one/two/three",
            "strip the last slashes"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("one/../"),
            "",
            "empty one"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("one/two/../"),
            "one",
            "empty one two"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("one/two/../.."),
            "",
            "empty one two twice"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("one/../two/./../three"),
            "three",
            "waltz"
        );
        $this->assertSame(
            \htmgem\GemtextTranslate_html::resolve_path("one/../.."),
            "/",
            "directory traversal"
        );
    }

}
