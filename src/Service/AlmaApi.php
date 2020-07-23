<?php
/**
 * Alma API wrapper service
 */

namespace DBP\API\AlmaBundle\Service;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\AlmaBundle\Entity\Book;
use DBP\API\AlmaBundle\Entity\BookLoan;
use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\AlmaBundle\Entity\BookOrder;
use DBP\API\AlmaBundle\Entity\BookOrderItem;
use DBP\API\AlmaBundle\Entity\DeliveryEvent;
use DBP\API\AlmaBundle\Entity\EventStatusType;
use App\Entity\Person;
use DBP\API\AlmaBundle\Entity\ParcelDelivery;
use App\Entity\TUGOnline\Organization;
use App\Exception\ItemNotLoadedException;
use App\Exception\ItemNotStoredException;
use App\Exception\ItemNotUsableException;
use App\Helpers\JsonException;
use App\Helpers\Tools;
use App\Service\AuditLogger;
use App\Service\GuzzleLogger;
use App\Service\PersonProviderInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;

class AlmaApi
{
    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    /**
     * @var Security
     */
    private $security;

    private $clientHandler;
    private $apiKey;
    private $analyticsApiKey;
    private $apiUrl;
    private $readonly;
    private $urls;
    private $logger;
    private $container;
    private $guzzleLogger;
    private $analyticsUpdatesHash = "";

    // 30h caching for Analytics, they will expire when there is a new Analytics Update
    private const ANALYTICS_CACHE_TTL = 108000;

    // 1h caching for the Analytics Updates
    private const ANALYTICS_UPDATES_CACHE_TTL = 3600;

    const ROLE_BIB = "ROLE_F_BIB_F";

    public function __construct(ContainerInterface $container, PersonProviderInterface $personProvider, Security $security, AuditLogger $logger, GuzzleLogger $guzzleLogger)
    {
        $this->security = $security;
        $this->personProvider = $personProvider;
        $this->clientHandler = null;
        $this->urls = new AlmaUrlApi();
        $this->logger = $logger;
        $this->container = $container;
        $this->guzzleLogger = $guzzleLogger;

        $config = $container->getParameter('dbp_api.alma.config');
        $this->apiKey = $config['api_key'] ?? '';
        $this->analyticsApiKey = $config['analytics_api_key'] ?? $this->apiKey;
        $this->apiUrl = $config['api_url'] ?? '';
        $this->readonly = $config['readonly'];
    }

    public function setApiKey(string $key)
    {
        $this->apiKey = $key;
    }

    public function setAnalyticsApiKey(string $key)
    {
        $this->analyticsApiKey = $key;
    }

    /**
     * Replace the guzzle client handler for testing
     *
     * @param object $handler
     */
    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    private function getClient() : Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        $base_uri = $this->apiUrl;
        if (substr($base_uri, -1) !== '/')
            $base_uri .= '/';

        $client_options = [
            'base_uri' => $base_uri,
            'handler' => $stack,
            'headers' => ['Authorization' => 'apikey ' . $this->apiKey],
        ];

        $stack->push($this->guzzleLogger->getClientHandler());

        $client = new Client($client_options);
        return $client;
    }

    private function getAnalyticsClient() : Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        $base_uri = $this->apiUrl;
        if (substr($base_uri, -1) !== '/')
            $base_uri .= '/';

        $client_options = [
            'base_uri' => $base_uri,
            'handler' => $stack,
            'headers' => ['Authorization' => 'apikey ' . $this->analyticsApiKey],
        ];

        $stack->push($this->guzzleLogger->getClientHandler());

        $guzzeCachePool = $this->getCachePool();
        $cacheMiddleWare = new CacheMiddleware(
            new GreedyCacheStrategy(
                new Psr6CacheStorage($guzzeCachePool),
                self::ANALYTICS_CACHE_TTL,
                new KeyValueHttpHeader(['Authorization', 'X-Request-Counter', 'X-Analytics-Updates-Hash'])
            )
        );

        $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
        $stack->push($cacheMiddleWare);

        $client = new Client($client_options);

        return $client;
    }

    private function getCachePool() : CacheItemPoolInterface
    {
        $guzzeCachePool = $this->container->get('dbp_api.cache.alma.analytics');
        assert($guzzeCachePool instanceof CacheItemPoolInterface);
        return $guzzeCachePool;
    }

    private function getAnalyticsUpdatesClient() : Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        $base_uri = $this->apiUrl;
        if (substr($base_uri, -1) !== '/')
            $base_uri .= '/';

        $client_options = [
            'base_uri' => $base_uri,
            'handler' => $stack,
            'headers' => ['Authorization' => 'apikey ' . $this->analyticsApiKey],
        ];

        $stack->push($this->guzzleLogger->getClientHandler());

        $guzzeCachePool = $this->getCachePool();
        $cacheMiddleWare = new CacheMiddleware(
            new GreedyCacheStrategy(
                new Psr6CacheStorage($guzzeCachePool),
                self::ANALYTICS_UPDATES_CACHE_TTL,
                new KeyValueHttpHeader(['Authorization'])
            )
        );

        $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
        $stack->push($cacheMiddleWare);

        $client = new Client($client_options);

        return $client;
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     * @throws ItemNotLoadedException
     */
    private function decodeResponse(ResponseInterface $response)
    {
        $body = (string)$response->getBody();
        try {
            return Tools::decodeJSON($body, true);
        } catch (JsonException $e) {
            throw new ItemNotLoadedException(sprintf("Invalid json: %s", Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * Handle json and xml Alma errors
     *
     * @param RequestException $e
     * @return string
     */
    private function getRequestExceptionMessage(RequestException $e) : string {
        if (!$e->hasResponse()) {
            return Tools::filterErrorMessage($e->getMessage());
        }

        $response = $e->getResponse();
        $body = $response->getBody();
        $content = $body->getContents();

        // try to handle xml errors
        if (strpos($content, "<?xml") === 0) {
            try {
                $xml = new \SimpleXMLElement($content);

                return Tools::filterErrorMessage($xml->errorList->error->errorMessage);
            } catch (\Exception $xmlException) {
                return Tools::filterErrorMessage($content);
            }
        }

        // try to handle json errors
        try {
            $decoded = Tools::decodeJSON($body, true);
        } catch (JsonException $e) {
            return Tools::filterErrorMessage($e->getMessage());
        }
        // If we get proper json we try to include the whole content
        $message = explode("\n", $e->getMessage())[0];
        $message .= "\n" .  json_encode($decoded);

        return Tools::filterErrorMessage($message);
    }

    /**
     * @param string $identifier
     * @return array
     * @throws ItemNotLoadedException
     */
    private function getBookOfferJsonData(string $identifier): array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept'     => 'application/json',
            ]
        ];

        try {
            $url = $this->urls->getBookOfferUrl($identifier);
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        }

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            return $dataArray;
        } catch (RequestException $e) {
            if ($e->getCode() == 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray["errorList"]["error"][0]["errorCode"];

                if ($errorCode === 401683) {
                    throw new ItemNotFoundException(
                        sprintf("LibraryBookOffer with id '%s' could not be found!", $identifier)
                    );
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(
                sprintf("LibraryBookOffer with id '%s' could not be loaded! Message: %s", $identifier, $message)
            );
        }
    }

    /**
     * @param array $filter
     * @return array|null
     * @throws ItemNotLoadedException
     */
    private function getBookOffersJsonData(array $filter): ?array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept'     => 'application/json',
            ]
        ];

        if (isset($filter["barcode"])) {
            $barcode = $filter["barcode"];
            $url = $this->urls->getBarcodeBookOfferUrl($barcode);

            try {
                $response = $client->request('GET', $url, $options);
                $dataArray = $this->decodeResponse($response);
                return [$dataArray];
            } catch (RequestException $e) {
                if ($e->getCode() == 400) {
                    $dataArray = $this->decodeResponse($e->getResponse());
                    $errorCode = (int) $dataArray["errorList"]["error"][0]["errorCode"];

                    if ($errorCode === 401689) {
                        return [];
                    }
                }

                $message = $this->getRequestExceptionMessage($e);
                throw new ItemNotLoadedException(sprintf("LibraryBookOffer with barcode '%s' could not be loaded! Message: %s", $barcode, $message));
            } catch (GuzzleException $e) {}
        } else {
            throw new ItemNotFoundException("barcode missing");
        }

        return null;
    }

    /**
     * @param string $identifier
     * @return array|null
     * @throws ItemNotLoadedException
     */
    public function getBookJsonData(string $identifier): ?array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept'     => 'application/json',
            ]
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $this->urls->getBookUrl($identifier), $options);

            $dataArray = $this->decodeResponse($response);

            return $dataArray;
        } catch (RequestException $e) {
            if ($e->getCode() == 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray["errorList"]["error"][0]["errorCode"];

                switch ($errorCode) {
                    case 401683:
                        throw new ItemNotFoundException(
                            sprintf("LibraryBook with id '%s' could not be found!", $identifier)
                        );
                        break;
                    case 402203:
                        throw new ItemNotFoundException(
                            sprintf("LibraryBook with id '%s' could not be found! Id is not valid.", $identifier)
                        );
                        break;
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBook with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {}

        return null;
    }

    /**
     * @param string $identifier
     * @return array|null
     * @throws ItemNotLoadedException
     */
    public function getBookLoanJsonData(string $identifier): ?array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept'     => 'application/json',
            ]
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $this->urls->getBookLoanUrl($identifier), $options);

            $dataArray = $this->decodeResponse($response);

            return $dataArray;
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            if ($e->getCode() == 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray["errorList"]["error"][0]["errorCode"];

                if ($errorCode === 401683) {
                    throw new ItemNotFoundException(
                        sprintf("LibraryBookLoan with id '%s' could not be found!", $identifier)
                    );
                }

            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(
                sprintf("LibraryBookLoan with id '%s' could not be loaded! Message: %s", $identifier, $message)
            );
        } catch (GuzzleException $e) {}

        return null;
    }

    /**
     * see: https://developers.exlibrisgroup.com/console/?url=/wp-content/uploads/alma/openapi/bibs.json#/Catalog/get/almaws/v1/bibs/{mms_id}/holdings/{holding_id}/items/{item_pid}
     *
     * @param array $item
     * @return BookLoan
     * @throws ItemNotLoadedException
     */
    public function bookLoanFromJsonItem(array $item): BookLoan
    {
        $bookLoan = new BookLoan();
        $bookLoan->setIdentifier("{$item['mms_id']}-{$item['holding_id']}-{$item['item_id']}-{$item['loan_id']}");

        try {
            $bookLoan->setStartTime(new \DateTime($item['loan_date']));
            $bookLoan->setEndTime(new \DateTime($item['due_date']));
        } catch (\Exception $e) {}

        $bookLoan->setLoanStatus($item['loan_status']);

        $userId = $item['user_id'];

        try {
            $person = $this->personProvider->getPersonForExternalService("ALMA", $userId);
            $bookLoan->setBorrower($person);
        } catch (ItemNotFoundException $e) {
            // this happens if no person was found in LDAP by AlmaUserId, must be handled in the frontend
            // catching the exception has the advantage that we can return the book even if no person was found
        }

        // we need to fetch the book offer for the loan because the loan data provided by Alma doesn't contain all information we need
        $bookOffer = $this->getBookOffer("{$item['mms_id']}-{$item['holding_id']}-{$item['item_id']}");
        $bookLoan->setObject($bookOffer);

        return $bookLoan;
    }

    /**
     * see: https://developers.exlibrisgroup.com/console/?url=/wp-content/uploads/alma/openapi/bibs.json#/Catalog/get/almaws/v1/bibs/{mms_id}/holdings/{holding_id}/items/{item_pid}
     *
     * @param array $item
     * @return BookOffer
     */
    private function bookOfferFromJsonItem(array $item): BookOffer
    {
        $holdingData = $item['holding_data'];
        $bibData = $item['bib_data'];
        $itemData = $item['item_data'];

        $bookOffer = new BookOffer();
        $bookOffer->setIdentifier("{$bibData['mms_id']}-{$holdingData['holding_id']}-{$itemData['pid']}");
        $bookOffer->setBarcode($itemData['barcode'] ?? '');
        $bookOffer->setLocationIdentifier($itemData['alternative_call_number'] ?? '');
        $bookOffer->setLibrary($itemData['library']['value'] ?? '');
        $bookOffer->setLocation($itemData['location']['value'] ?? '');
        $bookOffer->setDescription($itemData['description'] ?? '');

        try {
            $bookOffer->setAvailabilityStarts(new DateTime($itemData['inventory_date']));
        } catch (\Exception $e) {}

        $book = $this->bookFromJsonItem($bibData);
        $bookOffer->setBook($book);

        return $bookOffer;
    }

    /**
     * see: https://developers.exlibrisgroup.com/console/?url=/wp-content/uploads/alma/openapi/bibs.json#/Catalog/get/almaws/v1/bibs/{mms_id}
     *
     * @param array $item
     * @return Book
     */
    public function bookFromJsonItem(array $item): Book
    {
        $book = new Book();
        $book->setIdentifier($item['mms_id'] ?? '');
        $book->setTitle($item['title'] ?? 'Unknown title');
        $book->setAuthor($item['author'] ?? '');
        $book->setPublisher($item['publisher_const'] ?? '');

        try {
            $publicationYear = (int) $item['date_of_publication'];
            $book->setDatePublished(new DateTime("${publicationYear}-01-01"));
        } catch (\Exception $e) {}

        return $book;
    }

    /**
     * @param string $id
     * @return BookOffer
     * @throws ItemNotLoadedException
     */
    public function getBookOffer(string $id): BookOffer
    {
        $data = $this->getBookOfferJsonData($id);
        $bookOffer = $this->bookOfferFromJsonItem($data);

        return $bookOffer;
    }

    /**
     * @param array $filters
     * @return BookOffer[]
     * @throws ItemNotLoadedException
     */
    public function getBookOffers(array $filters) : array {
        $bookOffersData = $this->getBookOffersJsonData($filters);
        $bookOffers = [];

        // if there is a library filter set we want to use it
        $library = $filters["library"] ?? "";

        foreach ($bookOffersData as $bookOfferData)
        {
            $bookOffer = $this->bookOfferFromJsonItem($bookOfferData);

            if (in_array($library, ["", $bookOffer->getLibrary()], true)) {
                $bookOffers[] = $bookOffer;
            }
        }
        return $bookOffers;
    }

    /**
     * Updates an item in Alma
     *
     * @param BookOffer $bookOffer
     * @param string $library the library the current user wants to make his request for ("F" + number of institution, e.g. F1390)
     * @return BookOffer
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function updateBookOffer(BookOffer $bookOffer, $library = "")
    {
        $this->checkReadOnlyMode();

        // check if the current user has permissions to a book offer with a certain library
        $this->checkBookOfferPermissions($bookOffer);

        $identifier = $bookOffer->getIdentifier();
        $jsonData = $this->getBookOfferJsonData($identifier);

        // only updating of the alternative_call_number is supported
        $locationIdentifier = $bookOffer->getLocationIdentifier();
        $jsonData["item_data"]["alternative_call_number"] = $locationIdentifier;

        // alternative_call_number_type is just needed internally for the library
        $jsonData["item_data"]["alternative_call_number_type"]["value"] = $locationIdentifier != "" ? "8" : "";

        // we want to save a "modified date" to be able to sort by it in \App\Service\AlmaUrlApi::getBookOfferLocationsIdentifierUrl
        // see: https://developers.exlibrisgroup.com/alma/apis/docs/bibs/R0VUIC9hbG1hd3MvdjEvYmlicy97bW1zX2lkfS9ob2xkaW5ncy97aG9sZGluZ19pZH0vaXRlbXM=/
        // 20200114 [wrussm]: unfortunately we are not allowed to use this field any more since it is used by Primo
        // $jsonData["item_data"]["chronology_i"] = date("c", time());

        $client = $this->getClient();
        $options = [
            'json' => $jsonData,
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('PUT', $this->urls->getBookOfferUrl($identifier), $options);

            $data = $this->decodeResponse($response);
            $bookOffer = $this->bookOfferFromJsonItem($data);

            $this->log("Book offer <{$identifier}> ({$bookOffer->getName()}) was updated",
                ["alternative_call_number" => $locationIdentifier]);

            return $bookOffer;
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotStoredException(
                sprintf("LibraryBookOffer with id '%s' could not be stored! Message: %s", $identifier, $message)
            );
        } catch (GuzzleException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        }
    }

    /**
     * Creates a loan in Alma
     * See: https://developers.exlibrisgroup.com/alma/apis/docs/bibs/UE9TVCAvYWxtYXdzL3YxL2JpYnMve21tc19pZH0vaG9sZGluZ3Mve2hvbGRpbmdfaWR9L2l0ZW1zL3tpdGVtX3BpZH0vbG9hbnM=/
     *
     * @param BookOffer $bookOffer
     * @param array $bodyData
     * @return BookLoan
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws ItemNotUsableException
     */
    public function createBookLoan(BookOffer &$bookOffer, array $bodyData)
    {
        $this->checkReadOnlyMode();

        // "F" + number of institution (e.g. F1390)
        $library = $bodyData["library"];

        // check if the current user has permissions to a book offer with a certain library
        $this->checkBookOfferPermissions($bookOffer);

        // See: https://developers.exlibrisgroup.com/alma/apis/docs/xsd/rest_item_loan.xsd/
        $jsonData = [
            "circ_desk" => ["value" => "DEFAULT_CIRC_DESK"],
            "library" => ["value" => $library],
        ];

        $personId = $bodyData["borrower"];
        $person = $this->personProvider->getPerson($personId, true);
        $userId = $person->getAlmaId();

        if ($userId === null || $userId === "") {
            throw new ItemNotUsableException(
                sprintf("LibraryBookOffer '%s' cannot be loaned by %s! Person not registered in Alma!", $bookOffer->getName(), $person->getName())
            );
        }

        $client = $this->getClient();
        $options = [
            'json' => $jsonData,
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        try {
            $identifier = $bookOffer->getIdentifier();

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $this->urls->getBookLoanPostUrl($identifier, $userId), $options);

            $data = $this->decodeResponse($response);
            $bookLoan = $this->bookLoanFromJsonItem($data);

            $this->log("Loan was created for book offer <{$identifier}> ({$bookOffer->getName()}) for <{$person->getIdentifier()}> ({$person->getName()})",
                ["library" => $library, "userId" => $userId]);

            return $bookLoan;
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            if ($e->getCode() == 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray["errorList"]["error"][0]["errorCode"];

                switch ($errorCode)
                {
                    case 401158:
                        throw new ItemNotStoredException(
                            sprintf("LibraryBookOffer '%s' is currently on loan by another person!", $bookOffer->getName())
                        );
                    case 401198:
                        throw new ItemNotStoredException(
                            sprintf("LibraryBookOffer with similar name as the one of '%s' is currently on loan by another person!", $bookOffer->getName())
                        );
                    case 401153:
                        throw new ItemNotStoredException(
                            sprintf("LibraryBookOffer '%s' cannot be loaned from this circulation desk!", $bookOffer->getName())
                        );
                    case 401164:
                    case 401651:
                        throw new ItemNotStoredException(
                            sprintf("LibraryBookOffer '%s' is not loanable!", $bookOffer->getName())
                        );
                    case 401168:
                        throw new ItemNotStoredException(
                            sprintf("LibraryBookOffer '%s' cannot be loaded by %s! Patrons card has expired!", $bookOffer->getName(), $person->getName())
                        );
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotStoredException(
                sprintf("LibraryBookLoan for BookOffer '%s' could not be stored! Message: %s", $bookOffer->getName(), $message)
            );
        } catch (GuzzleException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        }
    }

    /**
     * @param Organization $organization
     * @param ArrayCollection $collection
     * @param array $resumptionData
     * @throws ItemNotLoadedException
     */
    public function addAllBookLoansByOrganizationToCollection(Organization $organization, ArrayCollection &$collection, $resumptionData = [])
    {
        // we need to set a request counter for caching (otherwise the requests would all be the same)
        $resumptionData["request-counter"] = $resumptionData["request-counter"] ?? 0;
        $resumptionData["request-counter"]++;

        $xml = $this->getBookLoanAnalyticsXMLByOrganization($organization, $resumptionData);

        $resumptionData["mapping"] = $resumptionData["mapping"] ?? AlmaUtils::getColumnMapping($xml);
        $mapping = $resumptionData["mapping"];
        if (empty($mapping))
            throw new \RuntimeException("Missing mapping");

        // we only get a ResumptionToken at the first request, but we need to add the token to every subsequent request
        $resumptionData["token"] = $resumptionData["token"] ?? (string) $xml->ResumptionToken;

        $isFinished = ((string) $xml->IsFinished) != "false";
        $rows = $xml->xpath("ResultXml/rowset/Row");

        /** @var SimpleXMLElement $row */
        foreach ($rows as $row) {
            $values = AlmaUtils::mapRowColumns($row, $mapping);
            $mmsId = $values["Bibliographic Details::MMS Id"];
            $loanId = $values["Loan Details::Item Loan Id"];
            $itemId = $values["Physical Item Details::Item Id"];
            $holdingId = $values["Holding Details::Holding Id"];

            if ($mmsId === "" || $loanId === "" || $itemId === "" || $holdingId === "") {
                continue;
            }

            $bookLoan = new BookLoan();
            $bookLoan->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}-{$loanId}");

            // Loan Date / Loan Time
            $loanDate = $values["Loan Date::Loan Date"];
            $loanTime = $values["Loan Date::Loan Time"];
            if ($loanDate !== "" && $loanTime !== "") {
                try {
                    $bookLoan->setStartTime(new DateTime($loanDate . " " . $loanTime));
                } catch (\Exception $e) {}
            }

            // Due Date / Due Time
            $dueDate = $values["Loan Details::Due Date"];
            $dueDateTime = $values["Loan Details::Due DateTime"];
            if ($dueDate !== "" && $dueDateTime !== "") {
                try {
                    $bookLoan->setEndTime(new DateTime($dueDate . " " . $dueDateTime));
                } catch (\Exception $e) {}
            }

            // Return Date / Return Time
            $returnDate = $values["Return Date::Return Date"];
            $returnTime = $values["Return Date::Return Time"];

            if ($returnDate !== "" && $returnTime !== "") {
                try {
                    $bookLoan->setReturnTime(new DateTime($returnDate . " " . $returnTime));
                } catch (\Exception $e) {}
            }

            $bookOffer = new BookOffer();
            $bookOffer->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}");
            $bookOffer->setBarcode($values["Loan Details::Barcode"]);
            $bookOffer->setDescription($values["Physical Item Details::Description"] ?? "");
            $bookOffer->setLocationIdentifier($values["Physical Item Details::Item Call Number"] ?? "");
            // Library Code
            // TODO: is this the correct column?
            $bookOffer->setLibrary($values["Item Location at time of loan::Library Code"]);

            $book = new Book();
            $book->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}");
            $book->setTitle($values["Bibliographic Details::Title"]);

            $author = $values["Bibliographic Details::Author"] ?? "";

            if ($author == "") {
                $author = $values["Bibliographic Details::Publisher"] ?? "";

                if ($author != "") {
                    $author = trim(explode(";", $author)[0]);
                }
            }

            $book->setAuthor($author);
            $bookOffer->setBook($book);

            $bookLoan->setObject($bookOffer);

            $person = new Person();
            // TODO: fetch Person by AlmaId? takes long!
            $person->setIdentifier("unknown");
            $person->setGivenName($values["Borrower Details::First Name"]);
            $person->setFamilyName($values["Borrower Details::Last Name"]);
            $person->setAlmaId($values["Borrower Details::User Id"]);

            $bookLoan->setBorrower($person);

            $collection->add($bookLoan);
        }

        // conserve memory
        unset($rows);
        unset($xml);

        if (!$isFinished) {
            $this->addAllBookLoansByOrganizationToCollection($organization, $collection, $resumptionData);
        }
    }

    /**
     * Posts a book offer return (sign-in) in Alma
     * See: https://developers.exlibrisgroup.com/alma/apis/docs/bibs/UE9TVCAvYWxtYXdzL3YxL2JpYnMve21tc19pZH0vaG9sZGluZ3Mve2hvbGRpbmdfaWR9L2l0ZW1zL3tpdGVtX3BpZH0=/
     *
     * @param BookOffer $bookOffer
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function returnBookOffer(BookOffer &$bookOffer)
    {
        $this->checkReadOnlyMode();

        // check if the current user has permissions to a book offer with a certain library
        $this->checkBookOfferPermissions($bookOffer);

        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        $identifier = $bookOffer->getIdentifier();
        $library = $bookOffer->getLibrary();

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $client->request('POST', $this->urls->getReturnBookOfferUrl($identifier, $library), $options);

            $this->log("Book offer <{$identifier}> ({$bookOffer->getName()}) was returned", ["library" => $library]);
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);

            if ($e->getCode() == 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray["errorList"]["error"][0]["errorCode"];

                switch ($errorCode)
                {
                    case 40166410:
                        throw new ItemNotStoredException(
                            sprintf("Invalid institution: %s", $message)
                        );
                }
            }

            throw new ItemNotStoredException(
                sprintf("LibraryBookOffer id '%s' could not be returned! Message: %s", $identifier, $message)
            );
        } catch (GuzzleException $e) {}
    }

    /**
     * Updates a loan in Alma
     *
     * @param BookLoan $bookLoan
     * @return BookLoan
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function updateBookLoan(BookLoan $bookLoan)
    {
        $this->checkReadOnlyMode();

        $identifier = $bookLoan->getIdentifier();
        $jsonData = $this->getBookLoanJsonData($identifier);

        // check if the current user has permissions to the book loan
        $bookOffer = $bookLoan->getObject();
        $this->checkBookOfferPermissions($bookOffer);

        $jsonData["loan_status"] = $bookLoan->getLoanStatus();
        $jsonData["due_date"] = $bookLoan->getEndTime()->format("c");

        $client = $this->getClient();
        $options = [
            'json' => $jsonData,
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('PUT', $this->urls->getBookLoanUrl($identifier), $options);

            $data = $this->decodeResponse($response);
            $bookLoan = $this->bookLoanFromJsonItem($data);

            $this->log("Book loan <{$identifier}> ({$bookOffer->getName()}) was updated",
                [
                    "loan_status" => $bookLoan->getLoanStatus(),
                    "due_date" => $bookLoan->getEndTime()->format("c"),
                ]
            );

            return $bookLoan;
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            if ($e->getCode() == 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray["errorList"]["error"][0]["errorCode"];

                switch ($errorCode) {
                    case 401681:
                        throw new ItemNotStoredException(
                            sprintf("LibraryBookLoan with id '%s' could not be stored! End time may not be in the past!", $identifier)
                        );
                        break;
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotStoredException(
                sprintf("LibraryBookLoan with id '%s' could not be stored! Message: %s", $identifier, $message)
            );
        } catch (GuzzleException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        }
    }

    public function checkPermissions()
    {
        if (!$this->security->isGranted(self::ROLE_BIB)) {
            throw new AccessDeniedHttpException('Only library officers can access the library api!');
        }
    }

    /**
     * Retrieves all book offers with in the same holding and with the same location as $bookOffer
     *
     * TODO: We are not allowed to use the field chronology_i any more, so this function is currently broken since the results are not sorted in the way we need it
     *
     * @param BookOffer $bookOffer
     * @return ArrayCollection
     * @throws ItemNotLoadedException
     */
    public function locationIdentifiersByBookOffer(BookOffer $bookOffer) :ArrayCollection
    {
        $collection = new ArrayCollection();
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept'     => 'application/json',
            ]
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $this->urls->getBookOfferLocationsIdentifierUrl($bookOffer), $options);

            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray["item"])) {
                return $collection;
            }

            $results = $dataArray["item"];

            array_walk($results, function (&$item) {
                $item = isset($item["item_data"]) && isset($item["item_data"]["alternative_call_number"]) ?
                    $item["item_data"]["alternative_call_number"] : "";
            });

            $results = array_unique($results);

            foreach($results as $result) {
                $collection->add($result);
            }
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {} catch (GuzzleException $e) {}

        return $collection;
    }

    /**
     * @param BookOffer $bookOffer
     * @return array|null
     * @throws ItemNotLoadedException
     */
    public function getBookLoansJsonDataByBookOffer(BookOffer $bookOffer): ?array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept'     => 'application/json',
            ]
        ];

        $identifier = $bookOffer->getIdentifier();

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $this->urls->getBookOfferLoansUrl($identifier), $options);
            $dataArray = $this->decodeResponse($response);

            return isset($dataArray["item_loan"]) ? $dataArray["item_loan"] : [];
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookLoans of BookOffer with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {}

        return null;
    }

    /**
     * Gets all book loans for a person
     *
     * @param Person $person
     * @return array|null
     * @throws ItemNotLoadedException
     * @throws ItemNotUsableException
     */
    public function getBookLoansJsonDataByPerson(Person $person): ?array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept'     => 'application/json',
            ]
        ];

        $identifier = $person->getIdentifier();
        $userId = $person->getAlmaId();

        if ($userId === null || $userId === "") {
            throw new ItemNotUsableException(
                sprintf("LibraryBookLoans cannot be fetched for %s! Person not registered in Alma!", $person->getName())
            );
        }

        try {
            $resultList = [];
            $loopCount = 0;
            $limit = 100;
            $offset = 0;

            // do as many requests as necessary to get all loans by the user
            do {
                // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
                $response = $client->request('GET', $this->urls->getLoansByUserIdUrl($userId, $limit, $offset), $options);
                $dataArray = $this->decodeResponse($response);
                $totalCount = (int) ($dataArray["total_record_count"] ?? 0);
                $resultList = array_merge($resultList, $dataArray["item_loan"] ?? []);
                $resultListCount = count($resultList);
                $offset += $limit;
                $loopCount++;
            } while ($resultListCount < $totalCount && $loopCount < 50);

            return $resultList;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookLoans of Person with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {}

        return null;
    }

    /**
     * Checks if the current user has permissions to a book offer with a certain library
     *
     * @param BookOffer $bookOffer
     * @param bool $throwException
     * @return bool
     * @throws AccessDeniedHttpException
     */
    public function checkBookOfferPermissions(BookOffer &$bookOffer, $throwException = true): bool
    {
        $person = $this->personProvider->getCurrentPerson();
        $institutes = $person->getInstitutesForGroup("F_BIB");
        $bookOfferLibrary = $bookOffer->getLibrary();

        // check if current user has F_BIB permissions to the institute of the book offer
        if (!in_array($bookOfferLibrary, $institutes)) {
            // throw an exception if we want to
            if ($throwException) {
                throw new AccessDeniedHttpException(
                    sprintf("Person '%s' is not allowed to work with library '%s'!", $person->getIdentifier(), $bookOfferLibrary)
                );
            }
        } else {
            // return true if we are not throwing an exception
            if (!$throwException) {
                return true;
            }
        }

        // return false if we are not throwing an exception, otherwise true
        return $throwException;
    }

    /**
     * @param Organization $organization
     * @param array $resumptionData
     * @return SimpleXMLElement|null
     * @throws ItemNotLoadedException
     */
    public function getBookOffersAnalyticsXMLByOrganization(Organization $organization, $resumptionData = []): ?SimpleXMLElement
    {
        $client = $this->getAnalyticsClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'X-Request-Counter' => $resumptionData["request-counter"],
                'X-Analytics-Updates-Hash' => $this->getAnalyticsUpdatesHash(),
            ]
        ];

        $identifier = $organization->getIdentifier();
        $resumptionToken = $resumptionData["token"] ?? "";

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $url = $this->urls->getBookOfferAnalyticsUrl($organization, $resumptionToken);
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray["anies"][0])) {
                throw new ItemNotLoadedException(sprintf("LibraryBookOffers of Organization with id '%s' were not valid!", $identifier));
            }

            // we need to remove the encoding attribute, because the string in reality is UTF-8 encoded,
            // otherwise the XML parsing will fail
            $analyticsData = str_replace('encoding="UTF-16"', '', $dataArray["anies"][0]);

            // SimpleXMLElement shows no warnings and may just fail, so we are using simplexml_load_string
            $xml = simplexml_load_string($analyticsData);

            return $xml;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookOffers of Organization with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {}

        return null;
    }

    /**
     * @param Organization $organization
     * @param array $resumptionData
     * @return SimpleXMLElement|null
     * @throws ItemNotLoadedException
     */
    public function getBookLoanAnalyticsXMLByOrganization(Organization $organization, $resumptionData = []): ?SimpleXMLElement
    {
        $client = $this->getAnalyticsClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'X-Request-Counter' => $resumptionData["request-counter"],
                'X-Analytics-Updates-Hash' => $this->getAnalyticsUpdatesHash(),
            ]
        ];

        $identifier = $organization->getIdentifier();
        $resumptionToken = $resumptionData["token"] ?? "";

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $url = $this->urls->getBookLoanAnalyticsUrl($organization, $resumptionToken);
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray["anies"][0])) {
                throw new ItemNotLoadedException(sprintf("LibraryBookLoans of Organization with id '%s' were not valid!", $identifier));
            }

            // we need to remove the encoding attribute, because the string in reality is UTF-8 encoded,
            // otherwise the XML parsing will fail
            $analyticsData = str_replace('encoding="UTF-16"', '', $dataArray["anies"][0]);

            // SimpleXMLElement shows no warnings and may just fail, so we are using simplexml_load_string
            $xml = simplexml_load_string($analyticsData);

            return $xml;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookLoans of Organization with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {}

        return null;
    }

    /**
     * @param Organization $organization
     * @param array $resumptionData
     * @return SimpleXMLElement|null
     * @throws ItemNotLoadedException
     */
    public function getBookOrdersAnalyticsXMLByOrganization(Organization $organization, $resumptionData = []): ?SimpleXMLElement
    {
        $client = $this->getAnalyticsClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'X-Request-Counter' => $resumptionData["request-counter"],
                'X-Analytics-Updates-Hash' => $this->getAnalyticsUpdatesHash(),
            ]
        ];

        $identifier = $organization->getIdentifier();

        $resumptionToken = $resumptionData["token"] ?? "";

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $url = $this->urls->getBookOrderAnalyticsUrl($organization, $resumptionToken);
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray["anies"][0])) {
                throw new ItemNotLoadedException(sprintf("LibraryBookOrders of Organization with id '%s' were not valid!", $identifier));
            }

            // we need to remove the encoding attribute, because the string in reality is UTF-8 encoded,
            // otherwise the XML parsing will fail
            $analyticsData = str_replace('encoding="UTF-16"', '', $dataArray["anies"][0]);
            // SimpleXMLElement shows no warnings and may just fail, so we are using simplexml_load_string
            $xml = simplexml_load_string($analyticsData);

            return $xml;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookOrders of Organization with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {}

        return null;
    }

    /**
     * Fetches the AnalyticsUpdates Analytics to check if our Analytics data was updated
     *
     * @return SimpleXMLElement|null
     * @throws ItemNotLoadedException
     */
    public function getAnalyticsUpdatesAnalyticsXML(): ?SimpleXMLElement
    {
        $client = $this->getAnalyticsUpdatesClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $url = $this->urls->getAnalyticsUpdatesAnalyticsUrl();
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray["anies"][0])) {
                throw new ItemNotLoadedException("AnalyticsUpdates were not valid!");
            }

            // we need to remove the encoding attribute, because the string in reality is UTF-8 encoded,
            // otherwise the XML parsing will fail
            $analyticsData = str_replace('encoding="UTF-16"', '', $dataArray["anies"][0]);
            // SimpleXMLElement shows no warnings and may just fail, so we are using simplexml_load_string
            $xml = simplexml_load_string($analyticsData);

            return $xml;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("AnalyticsUpdates could not be loaded! Message: %s", $message));
        } catch (GuzzleException $e) {}

        return null;
    }

    /**
     * @param Organization $organization
     * @param ArrayCollection $collection
     * @param array $resumptionData
     * @throws ItemNotLoadedException
     */
    public function addAllBookOffersByOrganizationToCollection(Organization $organization, ArrayCollection &$collection, $resumptionData = [])
    {
        // we need to set a request counter for caching (otherwise the requests would all be the same)
        $resumptionData["request-counter"] = $resumptionData["request-counter"] ?? 0;
        $resumptionData["request-counter"]++;

        $xml = $this->getBookOffersAnalyticsXMLByOrganization($organization, $resumptionData);

        $resumptionData["mapping"] = $resumptionData["mapping"] ?? AlmaUtils::getColumnMapping($xml);
        $mapping = $resumptionData["mapping"];
        if (empty($mapping))
            throw new \RuntimeException("Missing mapping");

        // we only get a ResumptionToken at the first request, but we need to add the token to every subsequent request
        $resumptionData["token"] = $resumptionData["token"] ?? (string) $xml->ResumptionToken;

        $isFinished = ((string) $xml->IsFinished) != "false";
        $rows = $xml->xpath("ResultXml/rowset/Row");

        /** @var SimpleXMLElement $row */
        foreach ($rows as $row) {
            $values = AlmaUtils::mapRowColumns($row, $mapping);
            $mmsId = $values["Bibliographic Details::MMS Id"];
            $holdingId = $values["Holding Details::Holding Id"];
            $itemId = $values["Physical Item Details::Item Id"];

            if ($mmsId === "" || $holdingId === "" || $itemId === "") {
                continue;
            }

            $bookOffer = new BookOffer();
            $bookOffer->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}");
            $bookOffer->setBarcode($values["Physical Item Details::Barcode"]);
            $bookOffer->setDescription($values["Physical Item Details::Description"] ?? "");
            // Item Call Number (we would need the alternative_call_number, but it seems ok)
            $bookOffer->setLocationIdentifier($values["Physical Item Details::Item Call Number"]);
            // Location Code
            $bookOffer->setLocation($values["Location::Location Code"]);
            // Library Code
            $bookOffer->setLibrary($values["Location::Library Code"]);

            $inventoryDate = $values["Physical Item Details::Inventory Date"];
            if ($inventoryDate !== "") {
                try {
                    $bookOffer->setAvailabilityStarts(new DateTime($inventoryDate));
                } catch (\Exception $e) {}
            }

            $book = new Book();
            $book->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}");
            $book->setTitle($values["Bibliographic Details::Title"]);
            $book->setAuthor($values["Bibliographic Details::Author"]);
            $book->setPublisher($values["Bibliographic Details::Publisher"]);

            $publicationDate = $values["Bibliographic Details::Publication Date"];
            if ($publicationDate !== "") {
                try {
                    $publicationYear = (int) $publicationDate;
                    $book->setDatePublished(new DateTime("${publicationYear}-01-01"));
                } catch (\Exception $e) {}
            }

            $bookOffer->setBook($book);

            $collection->add($bookOffer);
        }

        // conserve memory
        unset($rows);
        unset($xml);

        if (!$isFinished) {
            $this->addAllBookOffersByOrganizationToCollection($organization, $collection, $resumptionData);
        }
    }

    /**
     * @param Organization $organization
     * @param ArrayCollection $collection
     * @param array $resumptionData
     * @throws ItemNotLoadedException
     */
    public function addAllBookOrdersByOrganizationToCollection(Organization $organization, ArrayCollection &$collection, $resumptionData = [])
    {
        // we need to set a request counter for caching (otherwise the requests would all be the same)
        $resumptionData["request-counter"] = $resumptionData["request-counter"] ?? 0;
        $resumptionData["request-counter"]++;

        $xml = $this->getBookOrdersAnalyticsXMLByOrganization($organization, $resumptionData);

        $resumptionData["mapping"] = $resumptionData["mapping"] ?? AlmaUtils::getColumnMapping($xml);
        $mapping = $resumptionData["mapping"];
        if (empty($mapping))
            throw new \RuntimeException("Missing mapping");

        // we only get a ResumptionToken at the first request, but we need to add the token to every subsequent request
        $resumptionData["token"] = $resumptionData["token"] ?? (string) $xml->ResumptionToken;

        $isFinished = ((string) $xml->IsFinished) != "false";
        $rows = $xml->xpath("ResultXml/rowset/Row");

        // FIXME: We get duplicated entries where Invoice Line-Currency/Invoice-Currency/Invoice Line Total Price
        // are missing. Since we don't use them right now just ignore those duplicates.
        // TODO: Figure out what's wrong with the Analytics
        $alreadySeen = [];

        /** @var SimpleXMLElement $row */
        foreach ($rows as $row) {
            $values = AlmaUtils::mapRowColumns($row, $mapping);

            $poNumber = $values["PO Line::PO Line Reference"];
            if ($poNumber === "") {
                continue;
            }

            // FIXME
            if (key_exists($poNumber, $alreadySeen))
                continue;
            $alreadySeen[$poNumber] = true;

            $bookOrder = new BookOrder();
            // PO Number
            $identifierData = explode("-", $poNumber);

            // "o" stands for Organization
            $identifier = "o-" . $organization->getIdentifier() . "-" . $identifierData[0];
            $bookOrder->setIdentifier($identifier);

            $bookOrder->setOrderStatus($values["PO Line::Status (Active)"]);
            $bookOrder->setOrderNumber($poNumber);
            $bookOrder->setReceivingNote($values["PO Line::Receiving Note"]);

            $poCreationDate = $values["PO Line::PO Creation Date"];
            if ($poCreationDate !== "") {
                try {
                    // PO Creation Date
                    $bookOrder->setOrderDate(new DateTime($poCreationDate));
                } catch (\Exception $e) {}
            }

            $eventStatus = new EventStatusType();
            $eventStatus->setIdentifier($identifier);
            $eventStatus->setName(strtolower($values["PO Line::Status"]));

            $deliveryEvent = new DeliveryEvent();
            $deliveryEvent->setIdentifier($identifier);
            $deliveryEvent->setEventStatus($eventStatus);

            $claimingDate = $values["PO Line::Claiming Date"];
            if ($claimingDate !== "") {
                try {
                    // Claiming Date
                    $deliveryEvent->setAvailableFrom(new DateTime($claimingDate));
                } catch (\Exception $e) {}
            }

            $parcelDelivery = new ParcelDelivery();
            $parcelDelivery->setIdentifier($identifier);
            $parcelDelivery->setDeliveryStatus($deliveryEvent);

            $book = new Book();
            $book->setIdentifier($values["Bibliographic Details::MMS Id"]);
            $book->setTitle($values["Bibliographic Details::Title"]);
            $book->setAuthor($values["Bibliographic Details::Author"]);
            $book->setISBN($values["PO Line::PO Line Identifier"] ?? "");

            $bookOrderItem = new BookOrderItem();
            $bookOrderItem->setIdentifier($identifier);
            $bookOrderItem->setOrderDelivery($parcelDelivery);
            $bookOrderItem->setOrderedItem($book);

            // "Invoice Line Total Price" should be used if status is closed, otherwise "PO Line Total Price" should be used
            $price = ($eventStatus->getName() == "closed") ?
                ($values["Fund Transactions::Invoice Line Total Price"] ?? 0) :
                ($values["Fund Transactions::PO Line Total Price"] ?? 0);
            $bookOrderItem->setPrice((float) $price);
            $bookOrderItem->setPriceCurrency($values["Fund Ledger::Currency"]);

            $bookOrder->setOrderedItem($bookOrderItem);

            $collection->add($bookOrder);
        }

        // conserve memory
        unset($rows);
        unset($xml);

        if (!$isFinished) {
            $this->addAllBookOrdersByOrganizationToCollection($organization, $collection, $resumptionData);
        }
    }

    /**
     * Returns a hash to use for caching Analytics requests to check if our Analytics data was updated
     */
    private function getAnalyticsUpdatesHash()
    {
        if ($this->analyticsUpdatesHash != "") {
            return $this->analyticsUpdatesHash;
        }

        try {
            $xml = $this->getAnalyticsUpdatesAnalyticsXML();
        } catch (ItemNotLoadedException $e) {
            return $this->getFallbackAnalyticsUpdatesHash();
        }

        $rows = $xml->xpath("ResultXml/rowset/Row");

        if (count($rows) == 0) {
            return $this->getFallbackAnalyticsUpdatesHash();
        }

        /** @var SimpleXMLElement $data */
        $data = $rows[0];

        return $this->analyticsUpdatesHash = sha1($data->asXML());
    }

    /**
     * Returns the hash array of Analytics
     *
     * @return array|null
     */
    public function getAnalyticsUpdatesData()
    {
        try {
            $xml = $this->getAnalyticsUpdatesAnalyticsXML();
        } catch (ItemNotLoadedException $e) {
            return null;
        }

        $rows = $xml->xpath("ResultXml/rowset/Row");

        if (count($rows) == 0) {
            return null;
        }

        /** @var SimpleXMLElement $row */
        $row = $rows[0];

        try {
            $values = AlmaUtils::mapRowColumns($row, AlmaUtils::getColumnMapping($xml));
        } catch (\Exception $e) {
            return null;
        }

        return $values;
    }

    /**
     * Returns the datetime when the Analytics where last updated
     *
     * @return DateTime|null
     */
    public function getAnalyticsUpdateDate()
    {
        $values = $this->getAnalyticsUpdatesData();

        if ($values == null) {
            return null;
        }

        $dateString = $values["Institution::Data Updated As Of"] . " " . $values["Institution::Institution Timezone"];
        try {
            $datetime = new DateTime($dateString);
        } catch (\Exception $e) {
            return null;
        }

        return $datetime;
    }

    /**
     * Set the HTTP header for when the Analytics where last updated
     */
    public function setAnalyticsUpdateDateHeader()
    {
        $datetime = $this->getAnalyticsUpdateDate();

        if ($datetime != null) {
            header("X-Analytics-Update-Date: " . $datetime->format(DateTime::ATOM));
        }
    }

    /**
     * @return string
     */
    private function getFallbackAnalyticsUpdatesHash(): string
    {
        return $this->analyticsUpdatesHash = sha1(rand(0, 10000) + time());
    }

    /**
     * @param string $message
     * @param array|string|mixed|null $data
     */
    private function log(string $message, ?array $data = null)
    {
        $this->logger->log("Alma", $message, $data);
    }

    /**
     * @return bool
     */
    private function isReadOnlyMode(): bool
    {
        return $this->readonly;
    }

    private function checkReadOnlyMode()
    {
        if ($this->isReadOnlyMode()) {
            throw new AccessDeniedHttpException(
                sprintf("The Alma API currently is in read-only mode!")
            );
        }
    }
}
