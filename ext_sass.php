<?hh

/**
 * Sass
 * HHVM bindings to libsass - fast, native Sass parsing in HHVM!
 *
 * https://github.com/derpapst/sasshhvm
 * Based on https://github.com/sensational/sassphp/
 * Copyright (c)2015 Alexander Papst <http://derpapst.org>
 * with work done by Lawrence Meckan <http://absalom.biz>
 *
*/

class Sass {
    private array<string> $includePaths = array();
    private int $precision = 5;
    private int $style = self::STYLE_NESTED;
    private bool $comments = false;
    private string $map_path = null;
    private bool $omit_map_url = false;
    private bool $map_embed = false;
    private bool $map_contents = false;
    private string $map_root = null;

    /**
     * Parse a string of Sass; a basic input -> output affair.
     * @param string $source - String containing some sass source code.
     * @throws SassException - If the source is invalid.
     * @return string - Compiled css code
     */
    <<__Native>>
    public function compile(string $source): string;

    /**
     * The native implementation of compileFile().
     */
    <<__Native>>
    final private function compileFileNative(string $fileName): string;

    /**
     * Parse a whole file full of Sass and return the css output.
     * Only local files without the use of a stream or wrapper are supported.
     * @param string $fileName
     *    String containing the file name of a sass source code file.
     * @throws SassException
     *    If the file can not be read or source is invalid.
     * @return string - Compiled css code
     */
    final public function compileFile(string $fileName): string {
        if (empty($fileName)) {
            throw new SassException(
                'The file name may not be empty.', 1435750241
            );
        }
        // Make  the file path absolute
        if (substr($fileName, 0, 1) !== '/') {
            $fileName = getcwd().'/'.$fileName;
        }
        if (!file_exists($fileName) || !is_readable($fileName)) {
            throw new SassException(
                'The file can not be read.', 1435750470
            );
        }
        return $this->compileFileNative($fileName);
    }

    /**
     * Alias of self::compileFile()
     * @param string $fileName
     *    String containing the file name of a sass source code file.
     * @return string - Compiled css code
     */
    final public function compile_file(string $file_name): string {
        return $this->compileFile($file_name);
    }

    /**
     * Get the currently used formatting style. Default is Sass::STYLE_NESTED.
     * @return int
     */
    public function getStyle(): int {
        return $this->style;
    }

    /**
     * Set the formatting style.
     * Available styles are:
     *  * Sass::STYLE_NESTED
     *  * Sass::STYLE_EXPANDED
     *  * Sass::STYLE_COMPACT
     *  * Sass::STYLE_COMPRESSED
     * @param int $style
     * @throws SassException - If the style is not supported.
     * @return Sass
     */
    final public function setStyle(int $style): Sass {
        if (!in_array($style, array (
            self::STYLE_NESTED, self::STYLE_EXPANDED,
            self::STYLE_COMPACT, self::STYLE_COMPRESSED
        ))) {
            throw new SassException(
                'This style is not supported.', 1435749818
            );
        }
        $this->style = $style;
        return $this;
    }

    /**
     * Gets the currently used include paths where the compiler will search for
     * included files.
     * @return array
     */
    public function getIncludePaths(): array<string> {
        return $this->includePaths;
    }

    /**
     * Add a path for searching for included files.
     * Only local directories without the use of a stream or wrapper
     * are supported.
     * @param string $includePath - The path to look for further sass files.
     * @throws SassException - If the path does not exist or is not readable.
     * @return Sass
     */
    final public function addIncludePath(string $includePath): Sass {
        // Make  the file path absolute
        if (substr($includePath, 0, 1) !== '/') {
            $includePath = getcwd().'/'.$includePath;
        }
        if (!is_dir($includePath) || !is_readable($includePath)) {
            throw new SassException(
                'The path '.$includePath.' does not exist or is not readable',
                1435748077
            );
        }
        $this->includePaths[] = $includePath;
        return $this;
    }

    /**
     * Sets the include path list. Any previously set paths will be
     * overwritten.
     * Only local directories without the use of a stream or wrapper
     * are supported.
     * @param array<string> $includePaths
     *     The paths to look for further sass files.
     * @throws SassException - If one path does not exist or is not readable.
     * @return Sass
     */
    final public function setIncludePaths(array<string> $includePaths): Sass {
        $this->includePaths = array();
        foreach ($includePaths as $idx => $includePath) {
            $this->addIncludePath($includePath);
        }
        return $this;
    }

    /**
     * Get the status of source comments
     * @return boolean
     */
    public function getComments(): bool {
        return $this->comments;
    }

    /**
     * Set whether source comments are displayed in the final compiled SASS file
     * @param bool $comments
     * @return Sass
     */
    final public function setComments(bool $comments): Sass {
        if ($comments != 'true' && $comments != 'false') {
            throw new SassException(
                'Source comments are either turned on or off by true/false.', 143575012
            );
        }
        $this->comments = $comments;
        return $this;
    }


    /**
     * Get the status of source map embedding
     * @return boolean
     */
    public function getEmbed(): bool {
        return $this->map_embed;
    }

    /**
     * Set whether source map embedding happens
     * @param bool $map_embed
     * @return Sass
     */
    final public function setEmbed(bool $map_embed): Sass {
        if ($map_embed != 'true' && $map_embed != 'false') {
            throw new SassException(
                'Source map embedding is either turned on or off by true/false.', 143575666
            );
        }
        $this->map_embed = $map_embed;
        return $this;
    }

    /**
     * Get the status of source map url ommission
     * @return boolean
     */
    public function getMapURL(): bool {
        return $this->omit_map_url;
    }

    /**
     * Set whether source map url ommission
     * @param bool $omit_map_url
     * @return Sass
     */
    final public function setMapURL(bool $omit_map_url): Sass {
        if ($omit_map_url != 'true' && $omit_map_url != 'false') {
            throw new SassException(
                'Source map URL omission is either turned on or off by true/false.', 143575666
            );
        }
        $this->omit_map_url = $omit_map_url;
        return $this;
    }

   /** Gets the source map functionality status
     * @return boolean
     */
    public function getMapContents(): bool {
        return $this->omit_map_url;
    }

    /**
     * Sets whether the source map functionality turns on or off in LibSASS
     * @param bool $map_contents
     * @return Sass
     */
    final public function setMapContents(bool $map_contents): Sass {
        if ($map_contents != 'true' && $map_contents != 'false') {
            throw new SassException(
                'The ability to have source maps is either turned on or off by true/false.', 143575777
            );
        }
        $this->map_contents = $map_contents;
        return $this;
    }

    /**
     * Get the current source map root
     * @return string
     */
    public function getMapRoot(): string {
        return $this->map_root;
    }

    /**
     * Set the path for the source map root
     * @param string $map_root
     * @return Sass
     */
    final public function setMapRoot(string $map_root): Sass {
        // Make  the file path absolute
        if (substr($map_root, 0, 1) !== '/') {
            $map_root    = getcwd().'/'.$map_root;
        }
        if (!is_dir($map_root) || !is_readable($map_root)) {
            throw new SassException(
                'The path '.$map_root.' does not exist or is not readable',
                1435748099
            );
        }

        $this->map_root = $map_root;
        return $this;
    }

    /**
     * Get the current source map path
     * @return string
     */
    public function getMapPath(): string {
        return $this->map_path;
    }

    /**
     * Set the path for the source map file
     * @param string $map_path
     * @return Sass
     */
    final public function setMapPath(string $map_path): Sass {
        // Make  the file path absolute
        if (substr($map_path, 0, 1) !== '/') {
            $map_path    = getcwd().'/'.$map_path;
        }
        if (!is_writeable($map_path)) {
            throw new SassException(
                'The source map file '.$map_path.' is not able to be created.',
                1435748079
            );
        }
        $this->map_path = $map_path;
        return $this;
    }


    /**
     * Get the currently used precision for decimal numbers.
     * @return int
     */
    public function getPrecision(): int {
        return $this->precision;
    }

    /**
     * Set the precision that will be used for decimal numbers.
     * @param int $precision
     * @return Sass
     */
    final public function setPrecision(int $precision): Sass {
        if ($precision < 0) {
            throw new SassException(
                'The precision has to be greater or equal than 0.', 1435750706
            );
        }
        $this->precision = $precision;
        return $this;
    }




    /**
     * Get the library version of libsass.
     * @return string
     */
    <<__Native>>
    final public static function getLibraryVersion(): string;
}

/**
 * Exception for Sass.
 */
class SassException extends Exception { }
