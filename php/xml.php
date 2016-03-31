<?php
class xml
{
    public static function fromFile($file)
    {
        return static::fromString(file_get_contents($file));
    }

    public static function fromString($string)
    {
        $xml = new DOMDocument();
        $xml->loadXML($string);

        return array(
            $xml->documentElement->tagName => static::nodeToArray($xml->documentElement)
        );
    }

    public static function toString(array $array)
    {
        $xml = new DomDocument('1.0', 'utf-8');
        $xml->formatOutput = true;

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
                $output['@value'] = $v;
            }
        }

        if ($output && is_array($output)) {
            foreach ($output as $t => $v) {
                if (is_array($v) && (count($v) === 1)) {
                    $output[$t] = $v[0];
                }
            }
        } elseif (empty($output)) {
            $output = '';
        }

        if ($node->attributes->length) {
            $a = array();

            foreach ($node->attributes as $name => $node) {
                $a[$name] = (string)$node->value;
            }

            if (!is_array($output)) {
                $output = array('@value' => $output);
            }

            $output['@attributes'] = $a;
        }

        return $output;
    }

    private static function arrayToNode($xml, $name, $array)
    {
        $node = $xml->createElement($name);

        if (is_array($array)) {
            if (isset($array['@attributes'])) {
                foreach ($array['@attributes'] as $key => $value) {
                    if (!static::isValidTagName($key)) {
                        throw new Exception('[Array2XML] Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $node_name);
                    }

                    $node->setAttribute($key, static::bool2str($value));
                }

                unset($array['@attributes']);
            }

            if (isset($array['@value'])) {
                $node->appendChild($xml->createTextNode(static::bool2str($array['@value'])));

                return $node;
            }

            if (isset($array['@cdata'])) {
                $node->appendChild($xml->createCDATASection(static::bool2str($array['@cdata'])));

                return $node;
            }

            if (isset($array['@xml'])) {
                $node->appendChild($xml->createDocumentFragment()->appendXML($array['@xml']));

                return $node;
            }

            // recurse to get the node for that key
            foreach ($array as $key => $value) {
                if (!static::isValidTagName($key)) {
                    throw new Exception('[Array2XML] Illegal character in tag name. tag: ' . $key . ' in node: ' . $node_name);
                }

                if (is_array($value) && is_numeric(key($value))) {
                    foreach ($value as $v) {
                        $node->appendChild(static::arrayToNode($xml, $key, $v));
                    }
                } else {
                    $node->appendChild(static::arrayToNode($xml, $key, $value));
                }

                unset($array[$key]);
            }
        }

        if (!is_array($array)) {
            $node->appendChild($xml->createTextNode(static::bool2str($array)));
        }

        return $node;
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
