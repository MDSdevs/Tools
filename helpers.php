<?php
class Helpers
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
        return array_merge(static::getTranslationsFromBranch($folder, $previous), static::getTranslationsFromBranch($folder, $current));
    }

    public static function getTranslationsFromBranch($folder, $branch)
    {
        static::branch($folder, $branch);

        $translations = array();

        foreach (static::getFiles($folder) as $file) {
            $translations[str_replace($folder, '', $file)] = static::getTranslationsFromFile($file);
        }

        return $translations;
    }

    public static function getTranslationsFromFile($file)
    {
        $xml = static::getObjectFromFile($file);

        if (basename($file) === 'strings.xml') {
            return static::getTranslationsFromStrings($xml);
        }

        return static::getTranslationsFromPlurarls($xml);
    }

    public static function getObjectFromFile($file)
    {
        return simplexml_load_file($file);
    }

    public static function getTranslationsFromStrings($object)
    {
        die(var_dump($object));
    }

    public static function getTranslationsFromPlurarls($object)
    {
        $translations = array();

        foreach ($object->plurals as $plural) {
            $name = (string)$plural['name'];
            $translations[$name] = array();

            foreach ($plural->item as $item) {
                $translations[$name][(string)$item['quantity']] = (string)$item[0];
            }
        }

        return $translations;
    }
}