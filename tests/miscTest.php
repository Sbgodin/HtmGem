<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__)."/../lib-htmgem.inc.php";

final class miscTest extends TestCase {

    public function test_split_path_links(): void {
        $this->assertSame(
            array(),
            \htmgem\split_path_links(""),
            "empty link"
        );
        $this->assertSame(
            array(
                "noslash" => "noslash",
            ),
            \htmgem\split_path_links("noslash"),
            "no slash"
        );
        $this->assertSame(
            array(),
            \htmgem\split_path_links("/"),
            "only a slash"
        );
        $this->assertSame(
            array(
                "one" => "/one",
            ),
            \htmgem\split_path_links("/one"),
            "/one"
        );
        $this->assertSame(
            array(
                "one" => "one",
                "two" => "one/two",
            ),
            \htmgem\split_path_links("one/two"),
            "one/two"
        );
        $this->assertSame(
            array(
                "one" => "/one",
                "two" => "/one/two",
                "file.ext" => "/one/two/file.ext",
            ),
            \htmgem\split_path_links("/one/two/file.ext"),
            "/one/two/file.ext"
        );
    }

    public function test_resolve_path(): void {
        $this->assertSame(
            \htmgem\resolve_path(""),
            "",
            "empty link"
        );
        $this->assertSame(
            \htmgem\resolve_path("test"),
            "test",
            "single word"
        );
        $this->assertSame(
            \htmgem\resolve_path(" "),
            " ",
            "single space"
        );
        $this->assertSame(
            \htmgem\resolve_path(" A B "),
            " A B ",
            "several space"
        );
        $this->assertSame(
            \htmgem\resolve_path("/"),
            "/",
            "one slash"
        );
        $this->assertSame(
            \htmgem\resolve_path("//"),
            "/",
            "two slashes"
        );
        $this->assertSame(
            \htmgem\resolve_path("/////"),
            "/",
            "five slashes"
        );
        $this->assertSame(
            \htmgem\resolve_path("one/"),
            "one",
            "strip the last slash"
        );
        $this->assertSame(
            \htmgem\resolve_path("/two"),
            "/two",
            "slash at the beginning"
        );
        $this->assertSame(
            \htmgem\resolve_path("/two/"),
            "/two",
            "slash at the beginning and the end"
        );
        $this->assertSame(
            \htmgem\resolve_path("one/two/"),
            "one/two",
            "only the last slash remains"
        );
        $this->assertSame(
            \htmgem\resolve_path("one/two/three//"),
            "one/two/three",
            "strip the last slashes"
        );
        $this->assertSame(
            \htmgem\resolve_path("one/../"),
            "",
            "empty one"
        );
        $this->assertSame(
            \htmgem\resolve_path("one/two/../"),
            "one",
            "empty one two"
        );
        $this->assertSame(
            \htmgem\resolve_path("one/two/../.."),
            "",
            "empty one two twice"
        );
        $this->assertSame(
            \htmgem\resolve_path("one/../two/./../three"),
            "three",
            "waltz"
        );
        $this->assertSame(
            \htmgem\resolve_path("one/../.."),
            "/",
            "directory traversal"
        );
    }

}
