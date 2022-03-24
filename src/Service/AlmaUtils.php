<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Service;

use SimpleXMLElement;

class AlmaUtils
{
    /**
     * A mapping or null if non exists.
     */
    public static function getColumnMapping(SimpleXMLElement $xml): array
    {
        $namespaces = $xml->getNamespaces(true);
        if (!key_exists('xsd', $namespaces)) {
            throw new \RuntimeException('No schema found in Alma response');
        }
        $xml->registerXPathNamespace('xsd', $namespaces['xsd']);
        $elements = $xml->xpath('//xsd:schema//xsd:element');
        if ($elements === false || count($elements) === 0) {
            throw new \RuntimeException('Empty schema found in Alma response');
        }
        $mapping = [];
        foreach ($elements as $e) {
            $elementName = (string) $e->attributes()->name;
            $tableHeading = (string) $e->attributes('saw-sql', true)['tableHeading'];
            $columnHeading = (string) $e->attributes('saw-sql', true)['columnHeading'];
            $key = $tableHeading.'::'.$columnHeading;

            if (key_exists($key, $mapping)) {
                throw new \RuntimeException('Duplicate key in Alma schema: '.$key);
            }
            $mapping[$key] = $elementName;
        }

        return $mapping;
    }

    /**
     * Returns a array mapping column headers to values (both are strings).
     */
    public static function mapRowColumns(SimpleXMLElement $row, array $mapping): array
    {
        $values = [];
        foreach ($mapping as $key => $columnKey) {
            $values[$key] = (string) $row->$columnKey;
        }

        return $values;
    }
}
