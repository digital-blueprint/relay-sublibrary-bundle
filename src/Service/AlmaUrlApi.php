<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Service;

use Dbp\Relay\SublibraryBundle\ApiPlatform\BookOffer;
use Dbp\Relay\SublibraryBundle\Entity\Sublibrary;
use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;

class AlmaUrlApi
{
    public function __construct(private ConfigurationService $config)
    {
    }

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

    /**
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
     * @param string $library "F" + number of institution (e.g. F1390)
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
     * @throws UriException
     */
    public function getLoansByUserIdUrl(string $userId, int $limit = 100, int $offset = 0): string
    {
        if ($limit < 0 || $limit > 100) {
            throw new \RuntimeException('Valid values are 0-100');
        }

        // see: https://developers.exlibrisgroup.com/alma/apis/docs/users/R0VUIC9hbG1hd3MvdjEvdXNlcnMve3VzZXJfaWR9L2xvYW5z/
        $uriTemplate = new UriTemplate('users/{userId}/loans{?limit,offset}');

        return (string) $uriTemplate->expand([
            'userId' => $userId,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
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
     * @param string $resumptionToken
     *
     * @throws UriException
     */
    public function getBookOfferAnalyticsUrl(Sublibrary $library, $resumptionToken = ''): string
    {
        $libraryCode = $library->getCode();
        $limit = 1000;
        //        $limit = 25;

        $uriTemplate = new UriTemplate('analytics/reports?path={path}&filter={filter}&col_names=true&limit={limit}&token={token}');

        return (string) $uriTemplate->expand([
            'path' => $this->config->getAnalyticsReportPath('book_offer'),
            'filter' => '<sawx:expr xsi:type="sawx:comparison" op="equal" xmlns:saw="com.siebel.analytics.web/report/v1.1" xmlns:sawx="com.siebel.analytics.web/expression/v1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  xmlns:xsd="http://www.w3.org/2001/XMLSchema"><sawx:expr xsi:type="sawx:sqlExpression">"Location"."Library Code"</sawx:expr><sawx:expr xsi:type="xsd:string"><![CDATA['.$libraryCode.']]></sawx:expr></sawx:expr>',
            'limit' => $limit,
            'token' => $resumptionToken,
        ]);
    }

    /**
     * @param string $resumptionToken
     *
     * @throws UriException
     */
    public function getBookLoanAnalyticsUrl(Sublibrary $library, $resumptionToken = ''): string
    {
        $limit = 1000;
        //        $limit = 25;
        $libraryCode = $library->getCode();
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
                    <sawx:expr xsi:type="xsd:string"><![CDATA['.$libraryCode.']]></sawx:expr>
                </sawx:expr>
                <sawx:expr xsi:type="sawx:comparison" op="null">
                    <sawx:expr xsi:type="sawx:sqlExpression">"Return Date"."Return Date"</sawx:expr>
                </sawx:expr>
            </sawx:expr>';

        $uriTemplate = new UriTemplate('analytics/reports?path={path}&filter={filter}&col_names=true&limit={limit}&token={token}');

        return (string) $uriTemplate->expand([
            'path' => $this->config->getAnalyticsReportPath('book_loan'),
            'filter' => $filter,
            'limit' => $limit,
            'token' => $resumptionToken,
        ]);
    }

    /**
     * @param string $resumptionToken
     *
     * @throws UriException
     */
    public function getBookOrderAnalyticsUrl(Sublibrary $library, $resumptionToken = ''): string
    {
        $libraryCode = $library->getCode();
        $libraryCode = $libraryCode.'MON';
        $limit = 1000;
        //        $limit = 25;

        $uriTemplate = new UriTemplate('analytics/reports?path={path}&filter={filter}&col_names=true&limit={limit}&token={token}');

        return (string) $uriTemplate->expand([
            'path' => $this->config->getAnalyticsReportPath('book_order'),
            'filter' => '<sawx:expr xsi:type="sawx:comparison" op="equal" xmlns:saw="com.siebel.analytics.web/report/v1.1" xmlns:sawx="com.siebel.analytics.web/expression/v1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  xmlns:xsd="http://www.w3.org/2001/XMLSchema"><sawx:expr xsi:type="sawx:sqlExpression">"Fund Ledger"."Fund Ledger Code"</sawx:expr><sawx:expr xsi:type="xsd:string"><![CDATA['.$libraryCode.']]></sawx:expr></sawx:expr>',
            'limit' => $limit,
            'token' => $resumptionToken,
        ]);
    }

    /**
     * @throws UriException
     */
    public function getBudgetMonetaryAmountAnalyticsUrl(): string
    {
        $limit = 1000;
        $uriTemplate = new UriTemplate('analytics/reports?path={path}&col_names=true&limit={limit}');

        return (string) $uriTemplate->expand([
            'path' => $this->config->getAnalyticsReportPath('budget'),
            'limit' => $limit,
        ]);
    }

    /**
     * @throws UriException
     */
    public function getAnalyticsUpdatesAnalyticsUrl(): string
    {
        $uriTemplate = new UriTemplate('analytics/reports?path={path}&col_names=true');

        return (string) $uriTemplate->expand([
            'path' => $this->config->getAnalyticsReportPath('update_check'),
        ]);
    }
}
