<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests;

use Dbp\Relay\SublibraryBundle\Service\AlmaUtils;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlmaUtilsTest extends WebTestCase
{
    public function testGetColumnMapping()
    {
        $data = '<?xml version="1.0" encoding="UTF-8"?>
<QueryResult>
<ResultXml>
<rowset>
<xsd:schema targetNamespace="urn:schemas-microsoft-com:xml-analysis:rowset" xmlns:saw-sql="urn:saw-sql" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <xsd:complexType name="Row">
    <xsd:sequence>
        <xsd:element maxOccurs="1" minOccurs="0" name="Column0" saw-sql:columnHeading="0" saw-sql:displayFormula="saw_0" saw-sql:tableHeading="" saw-sql:type="integer" type="xsd:int"/>
        <xsd:element maxOccurs="1" minOccurs="0" name="Column1" saw-sql:columnHeading="Author" saw-sql:displayFormula="saw_1" saw-sql:tableHeading="Bibliographic Details" saw-sql:type="varchar" type="xsd:string"/>
    </xsd:sequence>
    </xsd:complexType>
</xsd:schema>
</rowset>
</ResultXml>
</QueryResult>';

        $xml = simplexml_load_string($data);
        $mapping = AlmaUtils::getColumnMapping($xml);

        $this->assertEquals($mapping['::0'], 'Column0');
        $this->assertEquals($mapping['Bibliographic Details::Author'], 'Column1');

        $rowData = '
           <Row>\n
            <Column1>Zwerger, Klaus</Column1>\n
          </Row>
        ';

        $row = simplexml_load_string($rowData);
        $mapped = AlmaUtils::mapRowColumns($row, $mapping);
        $this->assertEquals($mapped['::0'], '');
        $this->assertEquals($mapped['Bibliographic Details::Author'], 'Zwerger, Klaus');
    }

    public function testGetRows()
    {
        $data = '<QueryResult>
  <ResumptionToken>foobar</ResumptionToken>
  <IsFinished>true</IsFinished>
  <ResultXml>
    <rowset xmlns="urn:schemas-microsoft-com:xml-analysis:rowset">
      <Row>
        <Column0>0</Column0>
        <Column1>Something</Column1>
        <Column2>F00042FOR</Column2>
        <Column3>Test</Column3>
        <Column4>1</Column4>
        <Column5>0</Column5>
        <Column6>1</Column6>
        <Column7>1</Column7>
        <Column8>0</Column8>
      </Row>
    </rowset>
  </ResultXml>
</QueryResult>';

        $xml = simplexml_load_string($data);
        $rows = AlmaUtils::getRows($xml);
        $this->assertCount(1, $rows);
        $this->assertSame('Test', (string) $rows[0]->Column3);
    }
}
