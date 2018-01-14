<?php
declare(strict_types=1);

namespace JDWil\Zest\Util;

use JDWil\PhpGenny\Builder\Node\Scalar;
use JDWil\PhpGenny\Builder\Node\Type;
use JDWil\PhpGenny\ValueObject\InternalType;
use JDWil\Zest\Builder\XsdTypeFactory;
use JDWil\Zest\XsdType\QName;

class TypeUtil
{
    /**
     * @param QName|null $type
     * @param string|null $value
     * @param XsdTypeFactory $factory
     * @return Scalar|Type|null
     * @throws \Exception
     */
    public static function convertTypeToScalar(QName $type = null, string $value = null, XsdTypeFactory $factory)
    {
        if (null === $type || null === $value || (!$internalType = self::mapXsdTypeToInternalXsdType($type, $factory))) {
            return null;
        }

        switch ($internalType->getName()) {
            case 'XString':
                return Scalar::string($value);
            case 'XInt':
                return Scalar::int((int) $value);
            case 'XFloat':
                return Scalar::float((float) $value);
            case 'XBool':
                return $value === 'true' ? Type::true() : Type::false();
        }

        return null;
    }

    /**
     * @param QName $qname
     * @param XsdTypeFactory $factory
     * @return \JDWil\PhpGenny\Type\Class_|null
     * @throws \Exception
     */
    public static function mapXsdTypeToInternalXsdType(QName $qname, XsdTypeFactory $factory)
    {
        $ns = $qname->getNamespace();
        if ($ns !== null && $ns !== 'xsd' && $ns !== 'xs') {
            return null;
        }

        switch ($qname->getName()) {
            case 'space':
            case 'string':
            case 'ID':
            case 'IDREF':
            case 'IDREFS':
                return $factory->buildXString();

            case 'boolean':
                return $factory->buildXBool();

            case 'long':
            case 'int':
            case 'integer':
                return $factory->buildXInt();

            case 'decimal':
            case 'float':
            case 'double':
                return $factory->buildXFloat();

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
