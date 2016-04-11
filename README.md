# sasshhvm

The `sass` extension for [HHVM](https://github.com/facebook/hhvm) gives you an object-oriented system of parsing [Sass](http://sass-lang.com/) from within your PHP applications. Under the hood it uses [libsass](https://github.com/sass/libsass), a C library to parse and compile sass/scss files that does not require ruby.
It is based on the [sass extension for php](https://github.com/sensational/sassphp).

![Libsass 3.3.4](https://img.shields.io/badge/libsass-3.3.4-yellow.svg) [![Build Status](https://travis-ci.org/absalomedia/sasshhvm.svg)](https://travis-ci.org/absalomedia/sasshhvm)

## What's Sass?

Sass is a CSS pre-processor language to add on exciting, new, awesome features to CSS. Sass was the first language of its kind and by far the most mature and up to date codebase.

Sass was originally created by Hampton Catlin ([@hcatlin](http://twitter.com/hcatlin)). The extension and continuing evolution of the language has all been the result of years of work by Natalie Weizenbaum ([@nex4](http://twitter.com/nex3)) and Chris Eppstein ([@chriseppstein](http://twitter.com/chriseppstein)).

For more information about Sass itself, please visit [http://sass-lang.com](http://sass-lang.com)

### Building & Installation

Requires HHVM 3.2 or later and either the hhvm source tree (use the variable $HPHP_HOME to point to your hhvm source tree) or the [hhvm-dev package](https://github.com/facebook/hhvm/wiki/Prebuilt-Packages-for-HHVM).

Update the submodule with
~~~
git submodule update --init --recursive
~~~
and then run

~~~
./build.sh
~~~


To enable the extension, you need to have the following section in your PHP ini file:

~~~
hhvm.dynamic_extension_path = /path/to/hhvm/extensions
hhvm.dynamic_extensions[sass] = sass.so
~~~

Where `/path/to/hhvm/extensions` is a folder containing all HHVM extensions,
and `sass.so` is in it. This will cause the extension to be loaded when the
virtual machine starts up.

### Testing

To run the test suite:

~~~
$ cd /path/to/extension
$ ./test.sh
~~~

If you have the complete hhvm source tree you can run the tests with the test runner.

~~~
HPHP_HOME=/path/to/hhvm/source ./test.sh
~~~


## Usage

This extension has a very simple API:

    $sass = new Sass();
    $css = $sass->compile($source);

You can compile a file with `compileFile()`:

    $sass = new Sass();
    $css = $sass->compileFile($source);

You can set the include path for the library to use:

    $sass = new Sass();
    $sass->setIncludePath('/tmp');
    $css = $sass->compile($source);

You can set the style of your SASS file to suit your needs:

    $sass = new Sass();
    $sass->setStyle(Sass::STYLE_NESTED);

    $sass = new Sass();
    $sass->setStyle(Sass::STYLE_EXPANDED);

    $sass = new Sass();
    $sass->setStyle(Sass::STYLE_COMPACT);

    $sass = new Sass();
    $sass->setStyle(Sass::STYLE_COMPRESSED);

As the [Libsass](https://github.com/hcatlin/libsass) library has matured to get closer to 100% SASS coverage, so this extension has also matured:
* SASS file compilation is an array when a source map file is specified.
* The ability to define source comments
* The ability to embed the source map into the CSS output
* The ability to specify .SASS file input instead of .SCSS
* The ability to set a source map path, required when generating a dedicated .map file
* The ability to define a root directory for the source map itself

The output of `compileFile()` is an array when creating source map files, allowing both compiled SASS file and .map file to be generated in the same function call.

As there are multiple ways of generating source comments, there are now PHP level settings to control that output.

To generate source comments for a file inline:

    $sass = new Sass();
    $sass->setComments(true);
    $css = $sass->compileFile($source);

Aliases also exist so you can also use:

    $css = $sass->compile_file($source);

You can tell the compiler to use indented syntax (SASS syntax). By default it expects SCSS syntax:

    $sass = new Sass();
    $sass->setIndent(true); //TRUE -> SASS, FALSE -> SCSS
    $css = $sass->compile($source);

You can tell the compiler to embed the source map into the actual CSS file as well:

    $sass = new Sass();
    $sass->setEmbed(true);
    $css = $sass->compile($source);

You can set the source map file for the library to use:

    $sass = new Sass();
    $sass->setMapPath('/random.output.css.map');
    $css = $sass->compileFile($source);

This needs to be done prior to getting the output of the map file. As it stands, both the output of the SASS file compile & the SASS source map file generation sequence are both strings.

The first array item will always be the compiled SASS file:
    $css[0]

The second array item will always be the source map output:
    $css[1]

You can set the root of the generated source map file like so:

    $sass = new Sass();
    $sass->setMapRoot('/some/dir');
    $sass->setMapPath('/random.output.css.map');
    $css = $sass->compileFile($source);

If there's a problem, the extension will throw a `SassException`:

    $sass = new Sass();

    try
    {
        $css = $sass->compile('dayrui3dui36di37');
    }
    catch (SassException $e)
    {
        // $e->getMessage() - ERROR -- , line 1: invalid top-level expression

        $css = FALSE;
    }
