<?php

namespace DBP\API\AlmaBundle\Service;

use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\CoreBundle\Entity\Organization;
use function GuzzleHttp\uri_template;

class AlmaUrlApi
{
    /**
     * Returns [mmsId, holdingId, itemPid].
     *
     * @return string[]
     *
     * @throws InvalidIdentifierException
     */
    private function extractBookOfferID(string $identifier): array
    {
        $list = explode('-', $identifier);
        if (count($list) !== 3) {
            throw new InvalidIdentifierException('Invalid identifier: must contain 3 parts');
        }

        return $list;
    }

    /**
     * Returns [mmsId, holdingId, itemPid, loanId].
     *
     * @return string[]
     *
     * @throws InvalidIdentifierException
     */
    private function extractBookLoanID(string $identifier): array
    {
        $list = explode('-', $identifier);
        if (count($list) !== 4) {
            throw new InvalidIdentifierException('Invalid identifier: must contain 4 parts');
        }

        return $list;
    }

    public function getBookUrl(string $identifier): string
    {
        return uri_template('bibs/{identifier}', [
            'identifier' => $identifier,
        ]);
    }

    /**
     * @throws InvalidIdentifierException
     */
    public function getBookLoanPostUrl(string $identifier, string $userId): string
    {
        [$mmsId, $holdingId, $itemPid] = $this->extractBookOfferID($identifier);

        return uri_template('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}/loans{?user_id}', [
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
            'user_id' => $userId,
        ]);
    }

    /**
     * @throws InvalidIdentifierException
     */
    public function getBookOfferUrl(string $identifier): string
    {
        [$mmsId, $holdingId, $itemPid] = $this->extractBookOfferID($identifier);

        return uri_template('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}', [
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
        ]);
    }

    /**
     * @throws InvalidIdentifierException
     */
    public function getBookLoanUrl(string $identifier): string
    {
        [$mmsId, $holdingId, $itemPid, $loanId] = $this->extractBookLoanID($identifier);

        return uri_template('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}/loans/{loanId}', [
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
            'loanId' => $loanId,
        ]);
    }

    /**
     * @param string $library "F" + number of institution (e.g. F1390)
     *
     * @throws InvalidIdentifierException
     */
    public function getReturnBookOfferUrl(string $identifier, $library = ''): string
    {
        [$mmsId, $holdingId, $itemPid] = $this->extractBookOfferID($identifier);

        return uri_template('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}?op=scan&library={library}&circ_desk=DEFAULT_CIRC_DESK', [
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
            'library' => $library,
        ]);
    }

    /**
     * @throws InvalidIdentifierException
     */
    public function getBookOfferLoansUrl(string $identifier): string
    {
        [$mmsId, $holdingId, $itemPid] = $this->extractBookOfferID($identifier);

        return uri_template('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}/loans', [
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
        ]);
    }

    public function getLoansByUserIdUrl(string $userId, int $limit = 100, int $offset = 0): string
    {
        // see: https://developers.exlibrisgroup.com/alma/apis/docs/users/R0VUIC9hbG1hd3MvdjEvdXNlcnMve3VzZXJfaWR9L2xvYW5z/
        return uri_template('users/{userId}/loans{?limit,offset}', [
            'userId' => $userId,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function getBarcodeBookOfferUrl(string $barcode): string
    {
        return uri_template('items{?item_barcode}', [
            'item_barcode' => $barcode,
        ]);
    }

    /**
     * @throws InvalidIdentifierException
     */
    public function getBookOfferLocationsIdentifierUrl(BookOffer $bookOffer): string
    {
        [$mmsId, $holdingId] = $this->extractBookOfferID($bookOffer->getIdentifier());

        // see: https://developers.exlibrisgroup.com/alma/apis/docs/bibs/R0VUIC9hbG1hd3MvdjEvYmlicy97bW1zX2lkfS9ob2xkaW5ncy97aG9sZGluZ19pZH0vaXRlbXM=/
        // TODO: we are not allowed to use the field chronology_i any more, so sorting is currently broken
        return uri_template('bibs/{mmsId}/holdings/{holdingId}/items{?current_library,order_by,limit}', [
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'current_library' => $bookOffer->getLibrary(),
            'order_by' => 'chron_i',
            'limit' => '100',
        ]);
    }

    /**
     * @param string $resumptionToken
     */
    public function getBookOfferAnalyticsUrl(Organization $organization, $resumptionToken = ''): string
    {
        $institute = $organization->getAlternateName();
        $limit = 1000;
//        $limit = 25;

        return uri_template('analytics/reports?path={path}&filter={filter}&col_names=true&limit={limit}&token={token}', [
            'path' => '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Bestand-Institute-pbeke',
            'filter' => '<sawx:expr xsi:type="sawx:comparison" op="equal" xmlns:saw="com.siebel.analytics.web/report/v1.1" xmlns:sawx="com.siebel.analytics.web/expression/v1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  xmlns:xsd="http://www.w3.org/2001/XMLSchema"><sawx:expr xsi:type="sawx:sqlExpression">"Location"."Library Code"</sawx:expr><sawx:expr xsi:type="xsd:string"><![CDATA['.$institute.']]></sawx:expr></sawx:expr>',
            'limit' => $limit,
            'token' => $resumptionToken,
        ]);
    }

    /**
     * @param string $resumptionToken
     */
    public function getBookLoanAnalyticsUrl(Organization $organization, $resumptionToken = ''): string
    {
        $limit = 1000;
//        $limit = 25;
        $institute = $organization->getAlternateName();
        $filterExpression = '"Item Location at time of loan"."Library Code"';
//        $filterExpression = '"Physical Items"."Library Unit"."Library Code"';

        // language=XML
        $filter = '<sawx:expr xsi:type="sawx:logical" op="and"
                xmlns:saw="com.siebel.analytics.web/report/v1.1"
                xmlns:sawx="com.siebel.analytics.web/expression/v1.1"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                <sawx:expr xsi:type="sawx:comparison" op="equal">
                    <sawx:expr xsi:type="sawx:sqlExpression">'.$filterExpression.'</sawx:expr>
                    <sawx:expr xsi:type="xsd:string"><![CDATA['.$institute.']]></sawx:expr>
                </sawx:expr>
                <sawx:expr xsi:type="sawx:comparison" op="null">
                    <sawx:expr xsi:type="sawx:sqlExpression">"Return Date"."Return Date"</sawx:expr>
                </sawx:expr>
            </sawx:expr>';

        return uri_template('analytics/reports?path={path}&filter={filter}&col_names=true&limit={limit}&token={token}', [
            'path' => '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Ausleihen-Institute-pbeke',
            'filter' => $filter,
            'limit' => $limit,
            'token' => $resumptionToken,
        ]);
    }

    /**
     * @param string $resumptionToken
     */
    public function getBookOrderAnalyticsUrl(Organization $organization, $resumptionToken = ''): string
    {
        $institute = $organization->getAlternateName();
        $limit = 1000;
//        $limit = 25;

        return uri_template('analytics/reports?path={path}&filter={filter}&col_names=true&limit={limit}&token={token}', [
            'path' => '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/PO-List-pbeke_bearb_SF',
            'filter' => '<sawx:expr xsi:type="sawx:comparison" op="equal" xmlns:saw="com.siebel.analytics.web/report/v1.1" xmlns:sawx="com.siebel.analytics.web/expression/v1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  xmlns:xsd="http://www.w3.org/2001/XMLSchema"><sawx:expr xsi:type="sawx:sqlExpression">"PO Line"."PO Line Inventory Library Code"</sawx:expr><sawx:expr xsi:type="xsd:string"><![CDATA['.$institute.']]></sawx:expr></sawx:expr>',
            'limit' => $limit,
            'token' => $resumptionToken,
        ]);
    }

    public function getAnalyticsUpdatesAnalyticsUrl(): string
    {
        return uri_template('analytics/reports?path={path}&col_names=true', [
            'path' => '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Analytics-Updates',
        ]);
    }
}
