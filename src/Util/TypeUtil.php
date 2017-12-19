<?php
declare(strict_types=1);

namespace JDWil\Zest\Util;

use JDWil\PhpGenny\ValueObject\InternalType;
use JDWil\Zest\Builder\XsdTypeFactory;

class TypeUtil
{
    /**
     * @param string $xsdType
     * @return null|InternalType
     */
    public static function mapXsdTypeToInternalType(string $xsdType)
    {
        $ns = null;
        if (strpos($xsdType, ':') !== false) {
            list($ns, $type) = explode(':', $xsdType);
        } else {
            $type = $xsdType;
        }

        if ($ns !== null && $ns !== 'xsd' && $ns !== 'xs') {
            return null;
        }

        switch ($type) {
            case 'string':
            case 'ID':
            case 'IDREF':
            case 'IDREFS':
                return InternalType::string();

            case 'boolean':
                return InternalType::bool();

            case 'long':
            case 'int':
            case 'integer':
                return InternalType::int();

            case 'decimal':
            case 'float':
            case 'double':
                return InternalType::float();

            default:
                return null;
        }
    }

    public static function mapXsdTypeToInternalXsdType(string $xsdType, XsdTypeFactory $factory)
    {
        $ns = null;
        if (strpos($xsdType, ':') !== false) {
            list($ns, $type) = explode(':', $xsdType);
        } else {
            $type = $xsdType;
        }

        if ($ns !== null && $ns !== 'xsd' && $ns !== 'xs') {
            return null;
        }

        switch ($type) {
            case 'anyURI':
                return $factory->buildAnyUri();

            case 'base64Binary':
                return $factory->buildBase64Binary();

            case 'byte':
                return $factory->buildByte();

            case 'date':
                return $factory->buildDate();

            case 'dateTime':
                return $factory->buildDateTime();

            case 'duration':
                return $factory->buildDuration();

            case 'gDay':
                return $factory->buildGDay();

            case 'gMonth':
                return $factory->buildGMonth();

            case 'gMonthDay':
                return $factory->buildGMonthDay();

            case 'gYear':
                return $factory->buildGYear();

            case 'gYearMonth':
                return $factory->buildGYearMonth();

            case 'hexBinary':
                return $factory->buildHexBinary();

            case 'language':
                return $factory->buildLanguage();

            case 'negativeInteger':
                return $factory->buildNegativeInteger();

            case 'nonNegativeInteger':
                return $factory->buildNonNegativeInteger();

            case 'nonPositiveInteger':
                return $factory->buildNonPositiveInteger();

            case 'normalizedString':
                return $factory->buildNormalizedString();

            case 'positiveInteger':
                return $factory->buildPositiveInteger();

            case 'short':
                return $factory->buildShort();

            case 'time':
                return $factory->buildTime();

            case 'token':
                return $factory->buildToken();

            case 'unsignedLong':
                return $factory->buildUnsignedLong();

            case 'unsignedInt':
                return $factory->buildUnsignedInt();

            case 'unsignedShort':
                return $factory->buildUnsignedShort();

            case 'unsignedByte':
                return $factory->buildUnsignedByte();

            default:
                return null;
        }
    }
}
