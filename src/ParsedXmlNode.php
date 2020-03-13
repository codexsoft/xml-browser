<?php


namespace CodexSoft\XmlBrowser;


use Symfony\Component\CssSelector\CssSelectorConverter;

class ParsedXmlNode
{
    /** @var string */
    private $tag;

    /** @var ParsedXmlNode[] */
    private $children = [];

    /** @var ParsedXmlNode[] */
    private $childrenByTag = [];

    /** @var string[] */
    private $attributes = [];

    /** @var string */
    private $value = '';

    /** @var string */
    private $trimmedValue;

    /** @var string|null */
    private $outerXml;

    /** @var \SimpleXMLElement */
    private $simpleXMLElement;

    /** @var CssSelectorConverter */
    private static $cssSelector;

    public function __construct(\SimpleXMLElement $simpleXMLElement, string $tag, array $children, array $childrenByTag, array $attributes, string $value, ?string $outerXml = null)
    {
        $this->simpleXMLElement = $simpleXMLElement;
        $this->tag = $tag;
        $this->children = $children;
        $this->childrenByTag = $childrenByTag;
        $this->attributes = $attributes;
        $this->value = $value;
        $this->trimmedValue = trim($value);
        $this->outerXml = $outerXml;

        if (self::$cssSelector === null) {
            self::$cssSelector = new CssSelectorConverter(true);
        }
    }

    private function assertCssSelectorIsInstalled(): void
    {
        if (!\class_exists(CssSelectorConverter::class)) {
            throw new \LogicException('To filter with a CSS selector, install the CssSelector component ("composer require symfony/css-selector"). Or use Xpath instead.');
        }
    }

    public static function createFromSimpleXMLElement(\SimpleXMLElement $sxe): ParsedXmlNode
    {
        $attributes = [];
        $allChildren = [];
        $childrenByTag = [];
        foreach ($sxe->children() as $child) {
            $childParsed = self::createFromSimpleXMLElement($child);
            $childrenByTag[$child->getName()][] = $childParsed;
            $allChildren[] = $childParsed;
        }
        foreach ($sxe->attributes() as $attribute) {
            $attributes[$attribute->getName()] = (string) $attribute;
        }
        $outerXml = $sxe->asXML();
        return new ParsedXmlNode($sxe, $sxe->getName(), $allChildren, $childrenByTag, $attributes, (string) $sxe, $outerXml);
    }

    /**
     * @return string
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * @param string $name
     * @param null $default
     *
     * @return string
     */
    public function getAttribute(string $name, ?string $default = null): ?string
    {
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     * @param string $name
     *
     * @return string
     * @throws \Exception
     */
    public function getAttributeOrFail(string $name): string
    {
        if (!\array_key_exists($name, $this->attributes)) {
            throw new \Exception("Attribute $name does not exists.");
        }
        return $this->attributes[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $cssSelectorString
     *
     * @param int|null $limit
     *
     * @return \SimpleXMLElement[]
     */
    public function getDescendantsByCssSelectorAsSimpleXMLElements(string $cssSelectorString, ?int $limit = null): array
    {
        $this->assertCssSelectorIsInstalled();
        $xpath = self::$cssSelector->toXPath($cssSelectorString);
        return $this->getDescendantsByXPathAsSimpleXMLElements($xpath, $limit);
    }

    /**
     * @param string $xpath
     *
     * @param int|null $limit
     *
     * @return \SimpleXMLElement[]
     */
    public function getDescendantsByXPathAsSimpleXMLElements(string $xpath, ?int $limit = null): array
    {
        if ($limit !== null && $limit <= 0) {
            return [];
        }
        $document = new \SimpleXMLElement($this->outerXml);
        $foundNodes = [];
        $i = 0;
        foreach ($document->xpath($xpath) as $node) {
            $i++;
            $foundNodes[] = $node;
            if ($i === $limit) {
                break;
            }
        }
        return $foundNodes;
    }

    /**
     * @param string $cssSelectorString
     *
     * @param int|null $limit
     *
     * @return ParsedXmlNode[]
     */
    public function getDescendantsByCssSelectorAsParsedXmlNodes(string $cssSelectorString, ?int $limit = null): array
    {
        $this->assertCssSelectorIsInstalled();
        $xpath = self::$cssSelector->toXPath($cssSelectorString);
        return $this->getDescendantsByXPathAsParsedXmlNodes($xpath, $limit);
    }

    /**
     * @param string $xpath
     *
     * @param int|null $limit
     *
     * @return ParsedXmlNode[]
     */
    public function getDescendantsByXPathAsParsedXmlNodes(string $xpath, ?int $limit = null): array
    {
        $foundNodes = $this->getDescendantsByXPathAsSimpleXMLElements($xpath, $limit);
        $convertedNodes = [];
        foreach ($foundNodes as $foundNode) {
            $convertedNodes[] = self::createFromSimpleXMLElement($foundNode);
        }
        return $convertedNodes;
    }

    /**
     * @param string $xpath
     *
     * @return ParsedXmlNode|null
     */
    public function getFirstDescendantByCssSelectorAsParsedXmlNode(string $xpath): ?ParsedXmlNode
    {
        $foundNodes = $this->getDescendantsByCssSelectorAsParsedXmlNodes($xpath, 1);
        return \count($foundNodes) ? $foundNodes[0] : null;
    }

    /**
     * @param string $xpath
     *
     * @return \SimpleXMLElement|null
     */
    public function getFirstDescendantByCssSelectorAsSimpleXMLElement(string $xpath): ?\SimpleXMLElement
    {
        $foundNodes = $this->getDescendantsByCssSelectorAsSimpleXMLElements($xpath, 1);
        return \count($foundNodes) ? $foundNodes[0] : null;
    }

    /**
     * @param string $xpath
     *
     * @return ParsedXmlNode|null
     */
    public function getFirstDescendantByXPathAsParsedXmlNode(string $xpath): ?ParsedXmlNode
    {
        $foundNodes = $this->getDescendantsByXPathAsParsedXmlNodes($xpath, 1);
        return \count($foundNodes) ? $foundNodes[0] : null;
    }

    /**
     * @param string $xpath
     *
     * @return \SimpleXMLElement|null
     */
    public function getFirstDescendantByXPathAsSimpleXMLElement(string $xpath): ?\SimpleXMLElement
    {
        $foundNodes = $this->getDescendantsByXPathAsSimpleXMLElements($xpath, 1);
        return \count($foundNodes) ? $foundNodes[0] : null;
    }

    public function getFirstChild(?string $tag = null): ?ParsedXmlNode
    {
        if ($tag) {
            return $this->getChildByTagAndIndex($tag, 0);
        }

        return \count($this->children) ? $this->children[0] : null;
    }

    public function getFirstChildAsSimpleXMLElement(?string $tag = null): ?\SimpleXMLElement
    {
        $found = $this->getFirstChild($tag);
        return $found ? $found->getSimpleXMLElement() : null;
    }

    /**
     * @return ParsedXmlNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function hasChildrenOfTag(string $tag): bool
    {
        return \array_key_exists($tag, $this->childrenByTag);
    }

    /**
     * @param string $tag
     * @param int $index
     *
     * @return ParsedXmlNode|null
     */
    public function getChildByTagAndIndex(string $tag, int $index): ?ParsedXmlNode
    {
        if (\array_key_exists($tag, $this->childrenByTag) && \array_key_exists($index, $this->childrenByTag[$tag])) {
            return $this->childrenByTag[$tag][$index];
        }
        return null;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function getTrimmedValue(): string
    {
        return $this->trimmedValue;
    }

    /**
     * @return \SimpleXMLElement
     */
    public function getSimpleXMLElement(): \SimpleXMLElement
    {
        return $this->simpleXMLElement;
    }
}
