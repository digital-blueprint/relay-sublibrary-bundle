<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Service;

use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\CoreBundle\Entity\Organization;
use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;

class AlmaUrlApi
{
    /**
     * Returns [mmsId, holdingId, itemPid].
     *
     * @param string $identifier
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
     * @param string $identifier
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

    /**
     * @param string $identifier
     *
     * @return string
     *
     * @throws UriException
     */
    public function getBookUrl(string $identifier): string
    {
        $uriTemplate = new UriTemplate('bibs/{identifier}');

        return (string) $uriTemplate->expand([
            'identifier' => $identifier,
        ]);
    }

    /**
     * @param string $identifier
     * @param string $userId
     *
     * @return string
     *
     * @throws InvalidIdentifierException
     * @throws UriException
     */
    public function getBookLoanPostUrl(string $identifier, string $userId): string
    {
        [$mmsId, $holdingId, $itemPid] = $this->extractBookOfferID($identifier);

        $uriTemplate = new UriTemplate('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}/loans{?user_id}');

        return (string) $uriTemplate->expand([
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
            'user_id' => $userId,
        ]);
    }

    /**
     * @param string $identifier
     *
     * @return string
     *
     * @throws InvalidIdentifierException
     * @throws UriException
     */
    public function getBookOfferUrl(string $identifier): string
    {
        [$mmsId, $holdingId, $itemPid] = $this->extractBookOfferID($identifier);

        $uriTemplate = new UriTemplate('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}');

        return (string) $uriTemplate->expand([
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
        ]);
    }

    /**
     * @param string $identifier
     *
     * @return string
     *
     * @throws InvalidIdentifierException
     * @throws UriException
     */
    public function getBookLoanUrl(string $identifier): string
    {
        [$mmsId, $holdingId, $itemPid, $loanId] = $this->extractBookLoanID($identifier);

        $uriTemplate = new UriTemplate('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}/loans/{loanId}');

        return (string) $uriTemplate->expand([
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
            'loanId' => $loanId,
        ]);
    }

    /**
     * @param string $identifier
     * @param string $library "F" + number of institution (e.g. F1390)
     *
     * @return string
     *
     * @throws InvalidIdentifierException
     * @throws UriException
     */
    public function getReturnBookOfferUrl(string $identifier, $library = ''): string
    {
        [$mmsId, $holdingId, $itemPid] = $this->extractBookOfferID($identifier);

        $uriTemplate = new UriTemplate('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}?op=scan&library={library}&circ_desk=DEFAULT_CIRC_DESK');

        return (string) $uriTemplate->expand([
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
            'library' => $library,
        ]);
    }

    /**
     * @param string $identifier
     *
     * @return string
     *
     * @throws InvalidIdentifierException
     * @throws UriException
     */
    public function getBookOfferLoansUrl(string $identifier): string
    {
        [$mmsId, $holdingId, $itemPid] = $this->extractBookOfferID($identifier);

        $uriTemplate = new UriTemplate('bibs/{mmsId}/holdings/{holdingId}/items/{itemPid}/loans');

        return (string) $uriTemplate->expand([
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'itemPid' => $itemPid,
        ]);
    }

    /**
     * @param string $userId
     * @param int $limit
     * @param int $offset
     *
     * @return string
     *
     * @throws UriException
     */
    public function getLoansByUserIdUrl(string $userId, int $limit = 100, int $offset = 0): string
    {
        // see: https://developers.exlibrisgroup.com/alma/apis/docs/users/R0VUIC9hbG1hd3MvdjEvdXNlcnMve3VzZXJfaWR9L2xvYW5z/
        $uriTemplate = new UriTemplate('users/{userId}/loans{?limit,offset}');

        return (string) $uriTemplate->expand([
            'userId' => $userId,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * @param string $barcode
     *
     * @return string
     *
     * @throws UriException
     */
    public function getBarcodeBookOfferUrl(string $barcode): string
    {
        $uriTemplate = new UriTemplate('items{?item_barcode}');

        return (string) $uriTemplate->expand([
            'item_barcode' => $barcode,
        ]);
    }

    /**
     * @param BookOffer $bookOffer
     *
     * @return string
     *
     * @throws InvalidIdentifierException
     * @throws UriException
     */
    public function getBookOfferLocationsIdentifierUrl(BookOffer $bookOffer): string
    {
        [$mmsId, $holdingId] = $this->extractBookOfferID($bookOffer->getIdentifier());

        // see: https://developers.exlibrisgroup.com/alma/apis/docs/bibs/R0VUIC9hbG1hd3MvdjEvYmlicy97bW1zX2lkfS9ob2xkaW5ncy97aG9sZGluZ19pZH0vaXRlbXM=/
        // TODO: we are not allowed to use the field chronology_i any more, so sorting is currently broken
        $uriTemplate = new UriTemplate('bibs/{mmsId}/holdings/{holdingId}/items{?current_library,order_by,limit}');

        return (string) $uriTemplate->expand([
            'mmsId' => $mmsId,
            'holdingId' => $holdingId,
            'current_library' => $bookOffer->getLibrary(),
            'order_by' => 'chron_i',
            'limit' => '100',
        ]);
    }

    /**
     * @param Organization $organization
     * @param string $resumptionToken
     *
     * @return string
     *
     * @throws UriException
     */
    public function getBookOfferAnalyticsUrl(Organization $organization, $resumptionToken = ''): string
    {
        $institute = $organization->getAlternateName();
        $limit = 1000;
//        $limit = 25;

        $uriTemplate = new UriTemplate('analytics/reports?path={path}&filter={filter}&col_names=true&limit={limit}&token={token}');

        return (string) $uriTemplate->expand([
            'path' => '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Bestand-Institute-pbeke',
            'filter' => '<sawx:expr xsi:type="sawx:comparison" op="equal" xmlns:saw="com.siebel.analytics.web/report/v1.1" xmlns:sawx="com.siebel.analytics.web/expression/v1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  xmlns:xsd="http://www.w3.org/2001/XMLSchema"><sawx:expr xsi:type="sawx:sqlExpression">"Location"."Library Code"</sawx:expr><sawx:expr xsi:type="xsd:string"><![CDATA['.$institute.']]></sawx:expr></sawx:expr>',
            'limit' => $limit,
            'token' => $resumptionToken,
        ]);
    }

    /**
     * @param Organization $organization
     * @param string $resumptionToken
     *
     * @return string
     *
     * @throws UriException
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

        $uriTemplate = new UriTemplate('analytics/reports?path={path}&filter={filter}&col_names=true&limit={limit}&token={token}');

        return (string) $uriTemplate->expand([
            'path' => '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Ausleihen-Institute-pbeke',
            'filter' => $filter,
            'limit' => $limit,
            'token' => $resumptionToken,
        ]);
    }

    /**
     * @param Organization $organization
     * @param string $resumptionToken
     *
     * @return string
     *
     * @throws UriException
     */
    public function getBookOrderAnalyticsUrl(Organization $organization, $resumptionToken = ''): string
    {
        $institute = $organization->getAlternateName().'MON';
        $limit = 1000;
//        $limit = 25;

        $uriTemplate = new UriTemplate('analytics/reports?path={path}&filter={filter}&col_names=true&limit={limit}&token={token}');

        return (string) $uriTemplate->expand([
            'path' => '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/PO-List-pbeke_bearb_SF_6c',
            'filter' => '<sawx:expr xsi:type="sawx:comparison" op="equal" xmlns:saw="com.siebel.analytics.web/report/v1.1" xmlns:sawx="com.siebel.analytics.web/expression/v1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  xmlns:xsd="http://www.w3.org/2001/XMLSchema"><sawx:expr xsi:type="sawx:sqlExpression">"Fund Ledger"."Fund Ledger Code"</sawx:expr><sawx:expr xsi:type="xsd:string"><![CDATA['.$institute.']]></sawx:expr></sawx:expr>',
            'limit' => $limit,
            'token' => $resumptionToken,
        ]);
    }

    /**
     * @return string
     *
     * @throws UriException
     */
    public function getBudgetMonetaryAmountAnalyticsUrl(): string
    {
        $limit = 1000;
        $uriTemplate = new UriTemplate('analytics/reports?path={path}&col_names=true&limit={limit}');

        return (string) $uriTemplate->expand([
            'path' => '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Funds-List-SF_2',
            'limit' => $limit,
        ]);
    }

    /**
     * @return string
     *
     * @throws UriException
     */
    public function getAnalyticsUpdatesAnalyticsUrl(): string
    {
        $uriTemplate = new UriTemplate('analytics/reports?path={path}&col_names=true');

        return (string) $uriTemplate->expand([
            'path' => '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Analytics-Updates',
        ]);
    }
}
