<?php
require __DIR__.'/xml.php';

class helpers
{
    public static function branch($folder, $branch)
    {
        shell_exec('cd "'.$folder.'"; git checkout "'.$branch.'"');
    }

    public static function getFiles($folder, $filter = null)
    {
        $iterator = new RecursiveDirectoryIterator($folder);
        $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
        $files = new RegexIterator($iterator, '#(plurals|strings|arrays).xml$#', RegexIterator::GET_MATCH);
        $files = array_keys(iterator_to_array($files));

        return empty($filter) ? $files : array_filter($files, function($value) use ($filter) {
            return strstr($value, $filter);
        });
    }

    /**
     * @param $git       Directorio base de GIT que incluye el prefijo del proyecto sobre el que se trabaja ("GIT/Lenovo-K3-Note-VibeUI-Translations-")
     * @param $lan       Idioma actual
     * @param $previous  Branch anterior sobre el que obtener las traducciones
     * @param @current   Branch sobre el que aplicar las traducciones ya existentes
     */
    public static function getTranslationsFromBranches($git, $lang, $previous, $current)
    {
        $git_lang = $git.$lang;
        $git_base = $git.'Base';

        static::branch($git_lang, $current);

        $translations = static::getTranslationsFromFolder($git_lang);

        static::branch($git_base, $current);

        foreach ($translations as $file => $values) {
            $replacements = static::getBestLanguageTranslations($lang, $file, $git_lang, $git_base);

            if ($replacements) {
                $translations[$file] = static::merge($values, xml::fromFile($replacements));
            }
        }

        static::branch($git_lang, $previous);

        foreach ($translations as $file => $values) {
            if (is_file($file)) {
                $translations[$file] = static::merge($values, xml::fromFile($file));
            }
        }

        static::branch($git_lang, $current);

        return $translations;
    }

    public static function getBestLanguageTranslations($lang, $file, $git_lang, $git_base)
    {
        $folder = dirname(str_replace($git_lang, $git_base, $file));
        $filename = basename($file);

        $alternative = $folder.'/'.$filename;

        if (is_file($alternative)) {
            return $alternative;
        }

        $alternative = $folder.'-r'.strtoupper($lang).'/'.$filename;

        if (is_file($alternative)) {
            return $alternative;
        }

        $alternative = $folder.'-rUS/'.$filename;

        return is_file($alternative) ? $alternative : null;
    }

    public static function getTranslationsFromFolder($folder, $lang = null, $filter = null)
    {
        $translations = array();
        $target = $lang ? preg_replace('/Base$/', $lang, $folder) : $folder;

        foreach (static::getFiles($folder, $filter) as $file) {
            $translations[str_replace($folder, $target, $file)] = xml::fromFile($file);
        }

        return $translations;
    }

    public static function setTranslations($translations)
    {
        foreach ($translations as $file => $values) {
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 755, true);
            }

            file_put_contents($file, preg_replace_callback('/^(\s+)</m', function($matches) {
                return str_repeat(' ', strlen($matches[1]) * 2).'<';
            }, xml::toString($values)));
        }
    }

    public static function merge($first, $second)
    {
        foreach ($first as $key => $value) {
            if (empty($second[$key])) {
                continue;
            }

            if (!is_array($value) || !is_array($second[$key])) {
                $first[$key] = $second[$key];
                continue;
            }

            if (static::validToMerge($value)) {
                $first[$key] = static::merge($value, $second[$key]);
                continue;
            }

            if (static::validToValueToItem($value)) {
                $first[$key] = static::valueToItem($value, static::getNumericArray($second[$key]));
                continue;
            }

            if (static::validToValueToAttribute($value)) {
                $first[$key] = static::valueToAttribute($value, static::getNumericArray($second[$key]));
                continue;
            }

            if (static::validToValueToKey($value)) {
                $first[$key] = static::valueToKey($value, static::getNumericArray($second[$key]));
                continue;
            }

            $first[$key] = $second[$key];
        }

        foreach ($second as $key => $value) {
            if (empty($first[$key])) {
                $first[$key] = $value;
            }
        }

        return $first;
    }

    public static function valueToKey($first, $second)
    {
        $values = array();

        foreach (array_merge($first, $second) as $value) {
            $values[$value['@value']] = $value;
        }

        return array_values($values);
    }

    public static function valueToAttribute($first, $second)
    {
        $attribute = key($first[0]['@attributes']);
        $values = array();

        foreach (array_merge($first, $second) as $value) {
            if (!isset($value['item'])) {
                $values[$value['@attributes'][$attribute]] = $value;
            }
        }

        return array_values($values);
    }

    public static function valueToIndex($first, $second)
    {
        return $second + $first;
    }

    public static function valueToItem($first, $second)
    {
        $values = array();

        foreach ($first as $value) {
            $values[$value['@attributes']['name']] = $value;
        }

        foreach ($second as $value) {
            $key = $value['@attributes']['name'];

            if (empty($values[$key])) {
                $values[$key] = $value;
                continue;
            }

            if (!isset($values[$key]['item'])) {
                $values[$key] = static::valueToAttribute(static::getNumericArray($values[$key]), static::getNumericArray($value));
                continue;
            }

            $itemFirst = static::getNumericArray($values[$key]['item']);
            $itemSecond = static::getNumericArray($value['item']);

            if (isset($itemFirst[0]['@attributes'])) {
                $values[$key]['item'] = static::valueToAttribute($itemFirst, $itemSecond);
            } else {
                $values[$key]['item'] = static::valueToIndex($itemFirst, $itemSecond);
            }
        }

        return array_values($values);
    }

    public static function getNumericArray($value)
    {
        return isset($value[0]) ? $value : array($value);
    }

    public static function validToMerge($value)
    {
        return empty($value[0]);
    }

    public static function validToValueToKey($value)
    {
        return isset($value[0]['@value']);
    }

    public static function validToValueToAttribute($value)
    {
        return isset($value[0]['@attributes']);
    }

    public static function validToValueToItem($value)
    {
        return isset($value[0]['item']);
    }

    public static function validToArray($value)
    {
        return isset($value[0]);
    }
}
