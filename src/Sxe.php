<?php


namespace CodexSoft\XmlBrowser;


class Sxe
{
    public static function toArray(\SimpleXMLElement $sxe): array
    {
        $result = [
            '@tag' => $sxe->getName(),
            '@value' => (string) $sxe,
            '@attributes' => [],
            '@allChildren' => [],
        ];
        foreach ($sxe->children() as $child) {
            $childParsed = self::toArray($child);
            $result[$child->getName()][] = &$childParsed;
            $result['@allChildren'][] = &$childParsed;
        }
        foreach ($sxe->attributes() as $attribute) {
            $result['@attributes'][$attribute->getName()] = (string) $attribute;
        }
        return $result;
    }
}
