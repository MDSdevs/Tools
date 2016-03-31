<?php
require __DIR__.'/xml.php';

class helpers
{
    public static function branch($folder, $branch)
    {
        shell_exec('cd "'.$folder.'"; git checkout "'.$branch.'"');
    }

    public static function getFiles($folder)
    {
        $iterator = new RecursiveDirectoryIterator($folder);
        $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
        $files = new RegexIterator($iterator, '#(plurals|strings).xml$#', RegexIterator::GET_MATCH);

        return array_keys(iterator_to_array($files));
    }

    public static function getTranslationsFromBranches($folder, $previous, $current)
    {
        $translations = static::getTranslationsFromBranch($folder, $previous);

        foreach (static::getTranslationsFromBranch($folder, $current) as $file => $updates) {
            $translations[$file] = array_merge($translations[$file], $updates);
        }

        return $translations;
    }

    public static function getTranslationsFromBranch($folder, $branch)
    {
        static::branch($folder, $branch);

        $translations = array();

        foreach (static::getFiles($folder) as $file) {
            $translations[str_replace($folder, '', $file)] = xml::fromFile($file);
        }

        return $translations;
    }

    public static function getXmlArrayKey($file)
    {
        return (basename($file) === 'strings.xml') ? 'string' : 'plurals';
    }

    public static function setTranslations($translations)
    {
        foreach ($translations as $file => $values) {
            xml::toString($values);
        }
    }
}
