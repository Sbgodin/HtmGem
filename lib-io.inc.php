<?php declare(strict_types=1);

namespace htmgem\io;

define("_BOMS", array( // Byte Order Mark
// https://www.unicode.org/faq/utf_bom.html
    "UTF-32LE" => "\xFF\xFE\x00\x00",
    "UTF-16LE" => "\xFF\xFE",
    "UTF-16BE" => "\xFE\xFF",
    "UTF-8"     => "\xEF\xBB\xBF",
    "UTF-32BE" => "\x00\x00\xFE\xFF"
));

/**
 * Returns the encoding among Unicode ones, using the BOM
 * @param txt $text
 * @returns the encoding, or UTF-8 if no BOM read
 */
function _detectUnicodeEncoding(&$text) {
    /* The PHP built-in function mb-detect-encoding()
     * doesn't detect UTF-16.
     */
    foreach (_BOMS as $bomName => $bomBytes)
        if (strpos($text, $bomBytes) === 0) return $bomName;
    return "UTF-8";
}

/** Converts to UTF8 an Unicode text and removes the BOM
 */
function convertToUTF8(&$text) {
    $encoding = _detectUnicodeEncoding($text);
    $text = mb_convert_encoding($text, "UTF-8", $encoding);
    $text = preg_replace("/^"._BOMS['UTF-8']."/", "", $text);
    return $encoding;
}
