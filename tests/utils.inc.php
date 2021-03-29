<?php declare(strict_types=1);

function getGmiFiles($directory): generator {
    $flags =
        FilesystemIterator::KEY_AS_PATHNAME
      | FilesystemIterator::CURRENT_AS_FILEINFO
      | FilesystemIterator::SKIP_DOTS
    ;
    #TODO: Prevent preloading of symlinks. Otherwise it keeps loading instead of not
    # going into.
    #TODO: Prevent going into .git/ by browsing "manually" instead of RecursiveIteratorIterator
    $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, $flags));
    foreach ($dir as $fileinfo) {
        $filename = $fileinfo->getFilename();
        $filePathname = $fileinfo->getPathname();
        $extension = $fileinfo->getExtension();
        if ("gmi" == $extension) {
            yield $filePathname;
        }
    }
}

