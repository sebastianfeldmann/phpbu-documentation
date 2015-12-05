#!/usr/bin/env php
<?php
/**
 * @author Sebastian Bergmann <sebastian@phpunit.de>
 * @author Sebastian Feldmann <sebastian@phpbu.de>
 */
require __DIR__ . DIRECTORY_SEPARATOR . 'HTMLFilterIterator.php';

/**
 * Parses out the table of contents and builds a Version/Language navigation.
 * Webifies all HTML files in the given directory.
 *
 * @param string $directory
 * @param string $language
 * @param string $version
 */
function webify_directory($directory, $language, $version)
{
    // find table of contents
    $tocHTML = get_substring(
        file_get_contents($directory . DIRECTORY_SEPARATOR . 'index.html'),
        '<dl class="toc">',
        '</dl>',
        TRUE,
        TRUE,
        TRUE
    );

    // add some css classes to the toc navigation
    $tocHTML = str_replace(
        'class="toc"',
        'class="toc nav hidden-print"',
        $tocHTML
    );

    // list of available languages
    $languages = array(
        'de' => 'German',
        'en' => 'English',
    );

    // list of available versions with their respective languages
    $editions = array(
        '2.1' => array(
            'flag' => 'stable',
            'lang' => array('en'),
        ),
        '3.0' => array(
            'flag' => 'beta',
            'lang' => array('en'),
        ),
    );

    $editionAmount = count($editions);
    $editionItter  = 1;
    $editionsHTML   = '<li class="dropdown">
  <a aria-expanded="false" role="button" data-toggle="dropdown" class="dropdown-toggle" href="#">Versions <span class="caret"></span></a>
  <ul role="menu" class="dropdown-menu">
';
    foreach ($editions as $v => $edition) {
        $editionsHTML .= sprintf(
            '<li class="dropdown-header">%s%s</li>',
            $v,
            !empty($edition['flag']) ? ' (' . $edition['flag'] . ')' : ''
        );
        foreach ($edition['lang'] as $l) {
            $editionsHTML .= sprintf(
                '<li%s><a href="../../%s/%s/index.html"><span class="flag %s"></span>%s</a></li>' ,
                $version == $v && $l == $language ? ' class="active"' : '',
                $v,
                $l,
                $l,
                $languages[$l]
            );
        }
        if ($editionAmount > $editionItter) {
            $editionsHTML .= PHP_EOL . '<li class="divider"></li>' . PHP_EOL;
        }
        $editionItter++;
    }
    $editionsHTML .= '
  </ul>
</li>
';

    foreach (new HTMLFilterIterator(new DirectoryIterator($directory)) as $file) {
        webify_file($file->getPathName(), $tocHTML, $editionsHTML, $version, $language);
    }
}

/**
 * Use a base template and some search replace calls to make the HTML look a little bit less 'web 1.0'.
 *
 * @param string $file
 * @param string $tocHTML
 * @param string $editionsHTML
 * @param string $version
 * @param string $language
 */
function webify_file($file, $tocHTML, $editionsHTML, $version, $language)
{
    $filename = basename($file);

    if ($filename == 'phpbu-manual.html') {
        return;
    }

    $tocHTML = str_replace(
      '<a href="' . $filename . '">',
      '<a href="' . $filename . '" class="active">',
      $tocHTML
    );

    $template = file_get_contents(
      dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'page.html'
    );

    $title_text = array(
        'en' => 'PHPBU Manual',
        'de' => 'PHPBU Anleitung',
    );
    // i18n for text on page.
    $prev_text = array(
        'en' => 'Prev',
        'de' => 'Zur&uuml;ck',
    );
    $next_text = array(
        'en' => 'Next',
        'de' => 'Weiter',
    );
    $suggestions_text = array(
        'en' => 'Please <a href="https://github.com/sebastianfeldmann/phpbu-documentation/issues">open a ticket</a> on GitHub to suggest improvements to this page. Thanks!',
        'de' => 'F&uuml;r Verbessrungsvorschl&auml;ge zu diese Seite <a href="https://github.com/sebastianfeldmann/phpbu-documentation/issues">&ouml;ffne bitte ein Ticket</a> auf GitHub. Danke!',
    );

    $title   = get_text_in_language($title_text, $language);
    $prev    = '';
    $next    = '';
    $improve = '<div class="alert alert-info improve">' . get_text_in_language($suggestions_text, $language) . '</div>';

    if ($filename !== 'index.html') {
        if (strpos($filename, 'appendixes') === 0) {
            $type = 'appendix';
        } else if (strpos($filename, 'preface') === 0) {
            $type = 'preface';
        } else {
            $type = 'chapter';
        }

        $buffer   = file_get_contents($file);
        $_title   = get_substring($buffer, '<title>', '</title>', FALSE, FALSE);
        $content  = get_substring($buffer, '<div class="' . $type . '"', '<div class="navfooter">', TRUE, FALSE);
        $prev     = get_substring($buffer, '<link rel="prev" href="', '" title', FALSE, FALSE);
        $next     = get_substring($buffer, '<link rel="next" href="', '" title', FALSE, FALSE);

        if (!empty($prev)) {
            $prev = '<a accesskey="p" href="' . $prev . '">' . get_text_in_language($prev_text, $language) . '</a>';
        }

        if (!empty($next)) {
            $next = '<a accesskey="n" href="' . $next . '">' . get_text_in_language($next_text, $language) . '</a>';
        }

        if (!empty($_title)) {
            $title .= ' &#8211; ' . $_title;
        }
    } else {
        $content = '<h1 style="font-size:4em;">PHPBU Manual</h1><h4>Edition for PHPBU version ' . $version . '</h4>';
    }

    $buffer = str_replace(
      array(
          '{filename}',
          '{title}',
          '{content}',
          '{toc}',
          '{language}',
          '{versions}',
          '{prev}',
          '{next}',
          '<div class="caution" style="margin-left: 0.5in; margin-right: 0.5in;">',
          '<div class="warning" style="margin-left: 0.5in; margin-right: 0.5in;">',
          '<div class="note" style="margin-left: 0.5in; margin-right: 0.5in;">',
          '<table',
          ' border="1">',
          '{improve}'
      ),
      array(
          $filename,
          $title,
          $content,
          $tocHTML,
          $language,
          $editionsHTML,
          $prev,
          $next,
          '<div class="alert alert-warning">',
          '<div class="alert alert-danger">',
          '<div class="alert alert-info">',
          '<table class="table table-striped"',
          '>',
          $improve
      ),
      $template
    );

    file_put_contents($file, $buffer);
}

function get_text_in_language($text_list, $lang)
{
    if(array_key_exists($lang, $text_list)){
        return $text_list[$lang];
    }
    else{
        return $text_list['en'];
    }
}

function get_substring($buffer, $start, $end, $includeStart = TRUE, $includeEnd = TRUE, $strrpos = FALSE)
{
    if ($includeStart) {
        $prefix = 0;
    } else {
        $prefix = strlen($start);
    }

    if ($includeEnd) {
        $suffix = strlen($end);
    } else {
        $suffix = 0;
    }

    $start = strpos($buffer, $start);

    if ($strrpos) {
        $_end = strrpos($buffer, $end);
    } else {
        $_end = strpos($buffer, $end, $start);
    }

    if ($start !== FALSE) {
        return substr(
          $buffer,
          $start + $prefix,
          $_end - ($start + $prefix) + $suffix
        );
    } else {
        return '';
    }
}

echo "webify html files: ";

webify_directory($argv[1], $argv[2], $argv[3]);

echo "done\n";
