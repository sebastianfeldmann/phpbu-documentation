# PHPBU Documentation

## Requirements

- Apache Ant
- Ruby
- PHP 5 (with DOM, PCRE, SPL, and Tokenizer extensions enabled)
- xsltproc

## Building the Documentation

To build the complete documentation use:

    ant

To build only one version of the documentation use:

    ant build-LANG-VERSION

for example

    ant build-en-2.1

## Output

The generated files will be in `build/output/VERSION/LANG`.

## Shoutout

Big thanks to Sebastian Bergmann and his `phpunit-documentation` repository,
which helped a lot to get my head around the whole docbook stuff.

A lot of the ANT, XSLT and PHP scripts are just minimal adjustments to Sebastian's originals.
