<?php
class xml
{
    public static function getDOMDocument()
    {
        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->formatOutput = true;
        $xml->recover = true;
        $xml->preserveWhiteSpace = true;
        $xml->substituteEntities = false;

        return $xml;
    }

    public static function fromFile($file)
    {
        return static::fromString(file_get_contents($file));
    }

    public static function fromString($string)
    {
        $xml = static::getDOMDocument();

        $xml->loadXML($string);

        return array(
            $xml->documentElement->tagName => static::nodeToArray($xml->documentElement)
        );
    }

    public static function toString(array $array)
    {
        $xml = static::getDOMDocument();

        $xml->appendChild(static::arrayToNode($xml, 'resources', $array['resources']));

        return $xml->saveXML();
    }

    private static function nodeToArray($node)
    {
        if ($node->nodeType === XML_CDATA_SECTION_NODE) {
            return array('@cdata' => trim($node->textContent));
        }

        if ($node->nodeType === XML_TEXT_NODE) {
            return trim($node->textContent);
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return array();
        }

        $output = array();

        if (in_array((string)$node->tagName, array('string', 'item'), true)) {
            $output['@value'] = '';

            for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                $output['@value'] .= static::decodeString($node->ownerDocument->saveXml($node->childNodes->item($i)));
            }
        } else {
            for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                $child = $node->childNodes->item($i);
                $v = static::nodeToArray($child);

                if (isset($child->tagName)) {
                    $t = $child->tagName;

                    if (!isset($output[$t])) {
                        $output[$t] = array();
                    }

                    $output[$t][] = $v;
                } elseif ($v !== '') {
                    if (!isset($output['@value'])) {
                        $output['@value'] = '';
                    }

                    $output['@value'] .= $v;
                }
            }
        }

        if (empty($output)) {
            $output = '';
        }

        if (!is_array($output)) {
            $output = array('@value' => $output);
        }

        if ($node->attributes->length) {
            $output['@attributes'] = array();

            foreach ($node->attributes as $name => $node) {
                $output['@attributes'][$name] = (string)$node->value;
            }
        }

        return $output;
    }

    private static function arrayToNode($xml, $name, $array)
    {
        $node = $xml->createElement($name);

        if (!is_array($array)) {
            return static::nodeValue($node, $array);
        }

        if (isset($array['@attributes'])) {
            foreach ($array['@attributes'] as $key => $value) {
                if (!static::isValidTagName($key)) {
                    throw new Exception('[Array2XML] Illegal character in attribute name. attribute: '.$key.' in node: '.$value);
                }

                $node->setAttribute($key, static::bool2str($value));
            }
        }

        if (isset($array['@cdata'])) {
            $node->appendChild($xml->createCDATASection(static::bool2str($array['@cdata'])));
        }

        if (isset($array['@xml'])) {
            $node->appendChild($xml->createDocumentFragment()->appendXML($array['@xml']));
        }

        if (isset($array['@value'])) {
            $node = static::nodeValue($node, $array['@value']);
        }

        foreach ($array as $key => $value) {
            if (strstr($key, '@')) {
                continue;
            }

            if (!static::isValidTagName($key)) {
                var_dump('[Array2XML] Illegal character in tag name. tag: '.$key.' in node: '.$value);
                var_dump($name, $array);
                continue;
            }

            if (is_array($value) && is_numeric(key($value))) {
                foreach ($value as $v) {
                    $node->appendChild(static::arrayToNode($xml, $key, $v));
                }
            } else {
                $node->appendChild(static::arrayToNode($xml, $key, $value));
            }
        }

        return $node;
    }

    private static function nodeValue($node, $value)
    {
        $node->appendChild($node->ownerDocument->createTextNode(static::decodeString(static::bool2str($value))));

        return $node;
    }

    private static function decodeString($string)
    {
        $decode = htmlspecialchars_decode($string, ENT_HTML401);

        while ($string !== $decode) {
            $decode = htmlspecialchars_decode($string = $decode, ENT_HTML401);
        }

        return $decode;
    }

    private static function bool2str($v)
    {
        return ($v === true) ? 'true' : (($v === false) ? 'false' : $v);
    }

    private static function isValidTagName($tag)
    {
        return preg_match('/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i', $tag, $matches) && ($matches[0] === $tag);
    }
}
