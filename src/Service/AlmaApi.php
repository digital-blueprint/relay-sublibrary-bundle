<?php

declare(strict_types=1);
/**
 * Alma API wrapper service.
 */

namespace Dbp\Relay\SublibraryBundle\Service;

use DateTime;
use Dbp\Relay\BasePersonBundle\API\PersonProviderInterface;
use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\GuzzleTools;
use Dbp\Relay\SublibraryBundle\API\SublibraryProviderInterface;
use Dbp\Relay\SublibraryBundle\Entity\Book;
use Dbp\Relay\SublibraryBundle\Entity\BookLoan;
use Dbp\Relay\SublibraryBundle\Entity\BookOffer;
use Dbp\Relay\SublibraryBundle\Entity\BookOrder;
use Dbp\Relay\SublibraryBundle\Entity\BookOrderItem;
use Dbp\Relay\SublibraryBundle\Entity\BudgetMonetaryAmount;
use Dbp\Relay\SublibraryBundle\Entity\DeliveryEvent;
use Dbp\Relay\SublibraryBundle\Entity\EventStatusType;
use Dbp\Relay\SublibraryBundle\Entity\ParcelDelivery;
use Dbp\Relay\SublibraryBundle\Entity\Sublibrary;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotFoundException;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotLoadedException;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotStoredException;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotUsableException;
use Dbp\Relay\SublibraryBundle\Helpers\Tools;
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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class AlmaApi implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var PersonProviderInterface */
    private $personProvider;

    /** @var Security */
    private $security;

    /** @var SublibraryProviderInterface */
    private $libraryProvider;

    private $clientHandler;
    private $apiKey;
    private $analyticsApiKey;
    private $apiUrl;
    private $readonly;
    private $urls;
    private $analyticsUpdatesHash = '';
    private $ldapApi;

    private $cachePool;

    // 30h caching for Analytics, they will expire when there is a new Analytics Update
    private const ANALYTICS_CACHE_TTL = 108000;

    // 1h caching for the Analytics Updates
    private const ANALYTICS_UPDATES_CACHE_TTL = 3600;

    public function __construct(PersonProviderInterface $personProvider,
                                SublibraryProviderInterface $libraryProvider,
                                Security $security, LDAPApi $ldapApi)
    {
        $this->security = $security;
        $this->personProvider = $personProvider;
        $this->clientHandler = null;
        $this->urls = new AlmaUrlApi();
        $this->libraryProvider = $libraryProvider;
        $this->ldapApi = $ldapApi;

        $this->apiKey = '';
        $this->analyticsApiKey = '';
        $this->apiUrl = '';
        $this->readonly = false;
    }

    public function setConfig(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->analyticsApiKey = $config['analytics_api_key'] ?? $this->apiKey;
        $this->apiUrl = $config['api_url'] ?? '';
        $this->readonly = $config['readonly'];
    }

    public function setCache(?CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    /**
     * @return string[]
     */
    private static function budgetMonetaryAmountNames(): array
    {
        return [
            'Fund Transactions::Transaction Allocation Amount' => 'taa',
            'Fund Transactions::Transaction Allocation Amount - Transaction Cash Balance' => 'taa-tcb',
            'Fund Transactions::Transaction Available Balance' => 'tab',
            'Fund Transactions::Transaction Cash Balance' => 'tcb',
            'Fund Transactions::Transaction Cash Balance - Transaction Available Balance' => 'tcb-tab',
        ];
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
     * Replace the guzzle client handler for testing.
     *
     * @param object $handler
     */
    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    private function getClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        $base_uri = $this->apiUrl;
        if (substr($base_uri, -1) !== '/') {
            $base_uri .= '/';
        }

        $client_options = [
            'base_uri' => $base_uri,
            'handler' => $stack,
            'headers' => ['Authorization' => 'apikey '.$this->apiKey],
        ];

        if ($this->logger !== null) {
            $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));
        }

        $client = new Client($client_options);

        return $client;
    }

    public function checkConnection(): void
    {
        $client = $this->getClient();
        try {
            $client->get('bibs/test');
        } catch (RequestException $e) {
            throw new \RuntimeException($this->getRequestExceptionMessage($e));
        }
    }

    public function checkConnectionAnalytics(): void
    {
        $client = $this->getAnalyticsClient();
        try {
            $client->get('analytics/test');
        } catch (RequestException $e) {
            throw new \RuntimeException($this->getRequestExceptionMessage($e));
        }
    }

    private function getAnalyticsClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        $base_uri = $this->apiUrl;
        if (substr($base_uri, -1) !== '/') {
            $base_uri .= '/';
        }

        $client_options = [
            'base_uri' => $base_uri,
            'handler' => $stack,
            'headers' => ['Authorization' => 'apikey '.$this->analyticsApiKey],
        ];

        if ($this->logger !== null) {
            $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));
        }

        if ($this->cachePool !== null) {
            $cacheMiddleWare = new CacheMiddleware(
                new GreedyCacheStrategy(
                    new Psr6CacheStorage($this->cachePool),
                    self::ANALYTICS_CACHE_TTL,
                    new KeyValueHttpHeader(['Authorization', 'X-Request-Counter', 'X-Analytics-Updates-Hash'])
                )
            );

            $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
            $stack->push($cacheMiddleWare);
        }

        $client = new Client($client_options);

        return $client;
    }

    private function getAnalyticsUpdatesClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);
        $base_uri = $this->apiUrl;
        if (substr($base_uri, -1) !== '/') {
            $base_uri .= '/';
        }

        $client_options = [
            'base_uri' => $base_uri,
            'handler' => $stack,
            'headers' => ['Authorization' => 'apikey '.$this->analyticsApiKey],
        ];

        if ($this->logger !== null) {
            $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));
        }

        if ($this->cachePool !== null) {
            $cacheMiddleWare = new CacheMiddleware(
                new GreedyCacheStrategy(
                    new Psr6CacheStorage($this->cachePool),
                    self::ANALYTICS_UPDATES_CACHE_TTL,
                    new KeyValueHttpHeader(['Authorization'])
                )
            );

            $cacheMiddleWare->setHttpMethods(['GET' => true, 'HEAD' => true]);
            $stack->push($cacheMiddleWare);
        }

        $client = new Client($client_options);

        return $client;
    }

    /**
     * @return mixed
     *
     * @throws ItemNotLoadedException
     */
    private function decodeResponse(ResponseInterface $response, bool $assoc = true)
    {
        $body = $response->getBody();
        try {
            return Tools::decodeJSON((string) $body, $assoc);
        } catch (\JsonException $e) {
            throw new ItemNotLoadedException(sprintf('Invalid json: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * Handle json and xml Alma errors.
     */
    private function getRequestExceptionMessage(RequestException $e): string
    {
        if (!$e->hasResponse()) {
            return Tools::filterErrorMessage($e->getMessage());
        }

        $response = $e->getResponse();
        $body = $response->getBody();
        $content = $body->getContents();

        // try to handle xml errors
        if (strpos($content, '<?xml') === 0) {
            try {
                $xml = new \SimpleXMLElement($content);

                return Tools::filterErrorMessage((string) $xml->errorList->error->errorMessage);
            } catch (\Exception $xmlException) {
                return Tools::filterErrorMessage($content);
            }
        }

        // try to handle json errors
        try {
            $decoded = Tools::decodeJSON((string) $body, true);
        } catch (\JsonException $e) {
            return Tools::filterErrorMessage($e->getMessage());
        }
        // If we get proper json we try to include the whole content
        $message = explode("\n", $e->getMessage())[0];
        $message .= "\n".json_encode($decoded);

        return Tools::filterErrorMessage($message);
    }

    /**
     * @throws ItemNotLoadedException
     *
     * @return array|object|mixed
     */
    private function getBookOfferJsonData(string $identifier, bool $assoc = true)
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        try {
            $url = $this->urls->getBookOfferUrl($identifier);
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        }

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response, $assoc);

            return $dataArray;
        } catch (RequestException $e) {
            if ($e->getCode() === 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray['errorList']['error'][0]['errorCode'];

                if ($errorCode === 401683) {
                    throw new ItemNotFoundException(sprintf("LibraryBookOffer with id '%s' could not be found!", $identifier));
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookOffer with id '%s' could not be loaded! Message: %s", $identifier, $message));
        }
    }

    /**
     * @throws ItemNotLoadedException
     */
    private function getBookOffersJsonData(string $barcode): ?array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $url = $this->urls->getBarcodeBookOfferUrl($barcode);

        try {
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            return [$dataArray];
        } catch (RequestException $e) {
            if ($e->getCode() === 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray['errorList']['error'][0]['errorCode'];

                if ($errorCode === 401689) {
                    return [];
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookOffer with barcode '%s' could not be loaded! Message: %s", $barcode, $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * @throws ItemNotLoadedException
     */
    public function getBookJsonData(string $identifier): ?array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $this->urls->getBookUrl($identifier), $options);

            $dataArray = $this->decodeResponse($response);

            return $dataArray;
        } catch (RequestException $e) {
            if ($e->getCode() === 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray['errorList']['error'][0]['errorCode'];

                switch ($errorCode) {
                    case 401683:
                        throw new ItemNotFoundException(sprintf("LibraryBook with id '%s' could not be found!", $identifier));
                        break;
                    case 402203:
                        throw new ItemNotFoundException(sprintf("LibraryBook with id '%s' could not be found! Id is not valid.", $identifier));
                        break;
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBook with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * @throws ItemNotLoadedException
     *
     * @return array|object|mixed|null
     */
    public function getBookLoanJsonData(string $identifier, bool $assoc = true)
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $this->urls->getBookLoanUrl($identifier), $options);

            $dataArray = $this->decodeResponse($response, $assoc);

            return $dataArray;
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            if ($e->getCode() === 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray['errorList']['error'][0]['errorCode'];

                if ($errorCode === 401683) {
                    throw new ItemNotFoundException(sprintf("LibraryBookLoan with id '%s' could not be found!", $identifier));
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookLoan with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * see: https://developers.exlibrisgroup.com/console/?url=/wp-content/uploads/alma/openapi/bibs.json#/Catalog/get/almaws/v1/bibs/{mms_id}/holdings/{holding_id}/items/{item_pid}.
     *
     * @param array $item
     *
     * @return BookLoan
     *
     * @throws ItemNotLoadedException
     */
    public function bookLoanFromJsonItem(array $item): BookLoan
    {
        $bookLoan = new BookLoan();
        $bookLoan->setIdentifier("{$item['mms_id']}-{$item['holding_id']}-{$item['item_id']}-{$item['loan_id']}");

        try {
            $bookLoan->setStartTime(new DateTime($item['loan_date']));
            $bookLoan->setEndTime(new DateTime($item['due_date']));
        } catch (\Exception $e) {
        } catch (\TypeError $e) {
            // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
        }

        $bookLoan->setLoanStatus($item['loan_status']);

        $userId = $item['user_id'];

        try {
            $person = $this->ldapApi->getPersonByAlmaUserId($userId);
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
     * @see: https://developers.exlibrisgroup.com/console/?url=/wp-content/uploads/alma/openapi/bibs.json#/Catalog/get/almaws/v1/bibs/{mms_id}/holdings/{holding_id}/items/{item_pid}
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
        } catch (\Exception $e) {
        } catch (\TypeError $e) {
            // We needed a 2nd check, see https://gitlab.tugraz.at/dbp/middleware/api/-/issues/66
            // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
        }

        $book = $this->bookFromJsonItem($bibData);
        $bookOffer->setBook($book);

        return $bookOffer;
    }

    /**
     * see: https://developers.exlibrisgroup.com/console/?url=/wp-content/uploads/alma/openapi/bibs.json#/Catalog/get/almaws/v1/bibs/{mms_id}.
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
        } catch (\Exception $e) {
        } catch (\TypeError $e) {
            // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
        }

        return $book;
    }

    /**
     * @throws ItemNotLoadedException
     */
    public function getBookOffer(string $identifier): BookOffer
    {
        $data = $this->getBookOfferJsonData($identifier);

        return $this->bookOfferFromJsonItem($data);
    }

    /**
     * @return ArrayCollection
     *
     * @throws ItemNotLoadedException
     */
    public function getBookOffers(array $filters): ArrayCollection
    {
        $collection = new ArrayCollection();
        $library = null;

        $barcode = $filters['barcode'] ?? null;
        $libraryId = $filters['sublibrary'] ?? null;

        if (!empty($libraryId)) {
            $library = $this->libraryProvider->getSublibrary($libraryId);
            if ($library === null) {
                throw new ItemNotFoundException("Sublibrary with id '".$libraryId."' not found!");
            }
            $this->checkCurrentPersonLibraryPermissions($library);
        }

        if (!empty($barcode)) {
            $bookOffersData = $this->getBookOffersJsonData($barcode);
            $bookOffers = [];

            foreach ($bookOffersData as $bookOfferData) {
                $bookOffer = $this->bookOfferFromJsonItem($bookOfferData);

                // if there is a library filter set we want to use it
                if (!$library || in_array($library->getCode(), ['', $bookOffer->getLibrary()], true)) {
                    $bookOffers[] = $bookOffer;
                }
                $collection = new ArrayCollection($bookOffers);
            }
        } elseif ($library) {
            $this->setAnalyticsUpdateDateHeader();
            $this->addAllBookOffersByLibraryToCollection($library, $collection);
        }

        return $collection;
    }

    /**
     * @throws ItemNotLoadedException
     */
    public function getBookLoans(array $filters): ArrayCollection
    {
        $collection = new ArrayCollection();

        $borrowerId = $filters['borrower'] ?? null;
        $libraryId = $filters['sublibrary'] ?? null;

        $library = null;
        if (!empty($libraryId)) {
            $library = $this->libraryProvider->getSublibrary($libraryId);
            if ($library === null) {
                throw new ItemNotFoundException("Sublibrary with id '".$libraryId."' not found!");
            }
            $this->checkCurrentPersonLibraryPermissions($library);
        }

        if (!empty($borrowerId)) {
            $person = $this->personProvider->getPerson($borrowerId);
            $bookLoansData = $this->getBookLoansJsonDataByPerson($person);

            if ($library) {
                // TODO: this leads to up to date results,
                //       while searching for an organization only leads to "old" results
                //       -- how to deal with this?
                //       throw new \Exception('search for name and organization at the same time is forbidden');
                $bookLoansData = array_filter($bookLoansData, function ($item) use ($library) {
                    $bookLoan = $this->bookLoanFromJsonItem($item);

                    return $library->getCode() === $bookLoan->getLibrary();
                });
            }

            $bookLoans = [];
            foreach ($bookLoansData as $bookLoanData) {
                $bookLoans[] = $this->bookLoanFromJsonItem($bookLoanData);
            }

            // only return the ones the user has permissions to
            $bookLoans = $this->filterBookLoans($bookLoans);
            $collection = new ArrayCollection($bookLoans);
        } elseif ($library) {
            $this->setAnalyticsUpdateDateHeader();
            $this->addAllBookLoansByLibraryToCollection($library, $collection);
        }

        return $collection;
    }

    /**
     * Updates an item in Alma.
     *
     * @param string $library the library the current user wants to make his request for ("F" + number of institution, e.g. F1390)
     *
     * @return BookOffer
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     */
    public function updateBookOffer(BookOffer $bookOffer, $library = '')
    {
        $this->checkReadOnlyMode();

        // check if the current user has permissions to a book offer with a certain library
        $this->checkCurrentPersonBookOfferPermissions($bookOffer);

        $identifier = $bookOffer->getIdentifier();

        // get as object to avoid empty arrays, which are not supported
        $jsonData = $this->getBookOfferJsonData($identifier, false);

        // only updating of the alternative_call_number is supported
        $locationIdentifier = $bookOffer->getLocationIdentifier();
        $jsonData->item_data->alternative_call_number = $locationIdentifier;

        // alternative_call_number_type is just needed internally for the library
        $jsonData->item_data->alternative_call_number_type->value = $locationIdentifier !== '' ? '8' : '';

        // we want to save a "modified date" to be able to sort by it in \App\Service\AlmaUrlApi::getBookOfferLocationsIdentifierUrl
        // see: https://developers.exlibrisgroup.com/alma/apis/docs/bibs/R0VUIC9hbG1hd3MvdjEvYmlicy97bW1zX2lkfS9ob2xkaW5ncy97aG9sZGluZ19pZH0vaXRlbXM=/
        // 20200114 [wrussm]: unfortunately we are not allowed to use this field any more since it is used by Primo
        // $jsonData["item_data"]["chronology_i"] = date("c", time());

        $client = $this->getClient();
        $options = [
            'json' => $jsonData,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('PUT', $this->urls->getBookOfferUrl($identifier), $options);

            $data = $this->decodeResponse($response);
            $bookOffer = $this->bookOfferFromJsonItem($data);

            $this->log("Book offer <{$identifier}> ({$bookOffer->getName()}) was updated",
                ['alternative_call_number' => $locationIdentifier]);

            return $bookOffer;
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotStoredException(sprintf("LibraryBookOffer with id '%s' could not be stored! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        }
    }

    private function getPersonName(Person $person): string
    {
        return $person->getGivenName() ?? ''.' '.$person->getFamilyName() ?? '';
    }

    /**
     * Creates a loan in Alma
     * See: https://developers.exlibrisgroup.com/alma/apis/docs/bibs/UE9TVCAvYWxtYXdzL3YxL2JpYnMve21tc19pZH0vaG9sZGluZ3Mve2hvbGRpbmdfaWR9L2l0ZW1zL3tpdGVtX3BpZH0vbG9hbnM=/.
     *
     * @return BookLoan
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws ItemNotUsableException
     */
    public function createBookLoan(BookOffer &$bookOffer, array $bodyData)
    {
        $this->checkReadOnlyMode();

        // "F" + number of institution (e.g. F1390)
        $libraryCode = $bodyData['library'];

        // check if the current user has permissions to a book offer with a certain library
        $this->checkCurrentPersonBookOfferPermissions($bookOffer);

        // See: https://developers.exlibrisgroup.com/alma/apis/docs/xsd/rest_item_loan.xsd/
        $jsonData = [
            'circ_desk' => ['value' => 'DEFAULT_CIRC_DESK'],
            'library' => ['value' => $libraryCode],
        ];

        // XXX: Is there a better way to get an object for a API path?
        $personPath = $bodyData['borrower'];
        $res = preg_match('/^\/base\/people\/(.*)$/', $personPath, $match);
        if ($res !== 1) {
            throw new ItemNotFoundException('person not found');
        }
        $personId = $match[1];

        $person = $this->personProvider->getPerson($personId);
        $userId = $person->getExtraData('alma-id');

        if ($userId === null || $userId === '') {
            throw new ItemNotUsableException(sprintf("LibraryBookOffer '%s' cannot be loaned by %s! Person not registered in Alma!", $bookOffer->getName(), $this->getPersonName($person)));
        }

        $client = $this->getClient();
        $options = [
            'json' => $jsonData,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        try {
            $identifier = $bookOffer->getIdentifier();

            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('POST', $this->urls->getBookLoanPostUrl($identifier, $userId), $options);

            $data = $this->decodeResponse($response);
            $bookLoan = $this->bookLoanFromJsonItem($data);

            $this->log("Loan was created for book offer <{$identifier}> ({$bookOffer->getName()}) for <{$person->getIdentifier()}> ({$this->getPersonName($person)})",
                ['library' => $libraryCode, 'userId' => $userId]);

            return $bookLoan;
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            if ($e->getCode() === 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray['errorList']['error'][0]['errorCode'];

                switch ($errorCode) {
                    case 401158:
                        throw new ItemNotStoredException(sprintf("LibraryBookOffer '%s' is currently on loan by another person!", $bookOffer->getName()));
                    case 401198:
                        throw new ItemNotStoredException(sprintf("LibraryBookOffer with similar name as the one of '%s' is currently on loan by another person!", $bookOffer->getName()));
                    case 401153:
                        throw new ItemNotStoredException(sprintf("LibraryBookOffer '%s' cannot be loaned from this circulation desk!", $bookOffer->getName()));
                    case 401164:
                    case 401651:
                        throw new ItemNotStoredException(sprintf("LibraryBookOffer '%s' is not loanable!", $bookOffer->getName()));
                    case 401168:
                        throw new ItemNotStoredException(sprintf("LibraryBookOffer '%s' cannot be loaded by %s! Patrons card has expired!", $bookOffer->getName(), $this->getPersonName($person)));
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotStoredException(sprintf("LibraryBookLoan for BookOffer '%s' could not be stored! Message: %s", $bookOffer->getName(), $message));
        } catch (GuzzleException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        }
    }

    /**
     * @param Sublibrary $library
     * @param ArrayCollection $collection
     * @param array $resumptionData
     *
     * @throws ItemNotLoadedException
     * @throws \League\Uri\Contracts\UriException
     */
    public function addAllBookLoansByLibraryToCollection(Sublibrary $library, ArrayCollection &$collection, $resumptionData = [])
    {
        // we need to set a request counter for caching (otherwise the requests would all be the same)
        $resumptionData['request-counter'] = $resumptionData['request-counter'] ?? 0;
        ++$resumptionData['request-counter'];

        $xml = $this->getBookLoanAnalyticsXMLByOrganization($library, $resumptionData);

        $resumptionData['mapping'] = $resumptionData['mapping'] ?? AlmaUtils::getColumnMapping($xml);
        $mapping = $resumptionData['mapping'];
        if (empty($mapping)) {
            throw new \RuntimeException('Missing mapping');
        }
        // we only get a ResumptionToken at the first request, but we need to add the token to every subsequent request
        $resumptionData['token'] = $resumptionData['token'] ?? (string) $xml->ResumptionToken;

        $isFinished = ((string) $xml->IsFinished) !== 'false';
        $rows = $xml->xpath('ResultXml/rowset/Row');

        /** @var SimpleXMLElement $row */
        foreach ($rows as $row) {
            $values = AlmaUtils::mapRowColumns($row, $mapping);
            $mmsId = $values['Bibliographic Details::MMS Id'];
            $loanId = $values['Loan Details::Item Loan Id'];
            $itemId = $values['Physical Item Details::Physical Item Id'] ?? $values['Physical Item Details::Item Id'];
            $holdingId = $values['Holding Details::Holding Id'];

            if ($mmsId === '' || $loanId === '' || $itemId === '' || $holdingId === '') {
                continue;
            }

            $bookLoan = new BookLoan();
            $bookLoan->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}-{$loanId}");

            // Loan Date / Loan Time
            $loanDate = $values['Loan Date::Loan Date'];
            $loanTime = $values['Loan Date::Loan Time'];
            if ($loanDate !== '' && $loanTime !== '') {
                try {
                    $bookLoan->setStartTime(new DateTime($loanDate.' '.$loanTime));
                } catch (\Exception $e) {
                } catch (\TypeError $e) {
                    // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
                }
            }

            // Due Date / Due Time
            $dueDate = $values['Loan Details::Due Date'];
            $dueDateTime = $values['Loan Details::Due DateTime'];
            if ($dueDate !== '' && $dueDateTime !== '') {
                try {
                    $bookLoan->setEndTime(new DateTime($dueDate.' '.$dueDateTime));
                } catch (\Exception $e) {
                } catch (\TypeError $e) {
                    // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
                }
            }

            // Return Date / Return Time
            $returnDate = $values['Return Date::Return Date'];
            $returnTime = $values['Return Date::Return Time'];

            if ($returnDate !== '' && $returnTime !== '') {
                try {
                    $bookLoan->setReturnTime(new DateTime($returnDate.' '.$returnTime));
                } catch (\Exception $e) {
                } catch (\TypeError $e) {
                    // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
                }
            }

            $bookOffer = new BookOffer();
            $bookOffer->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}");
            $bookOffer->setBarcode($values['Loan Details::Barcode']);
            $bookOffer->setDescription($values['Physical Item Details::Description'] ?? '');
            $bookOffer->setLocationIdentifier($values['Physical Item Details::Item Call Number'] ?? '');
            // Library Code
            // TODO: is this the correct column?
            $bookOffer->setLibrary($values['Item Location at time of loan::Library Code']);

            $book = new Book();
            $book->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}");
            $book->setTitle($values['Bibliographic Details::Title']);

            $author = $values['Bibliographic Details::Author'] ?? '';

            if ($author === '') {
                $author = $values['Bibliographic Details::Publisher'] ?? '';

                if ($author !== '') {
                    $author = trim(explode(';', $author)[0]);
                }
            }

            $book->setAuthor($author);
            $bookOffer->setBook($book);

            $bookLoan->setObject($bookOffer);

            $person = new Person();
            // TODO: fetch Person by AlmaId? takes long!
            $person->setIdentifier('unknown');
            $person->setGivenName($values['Borrower Details::First Name']);
            $person->setFamilyName($values['Borrower Details::Last Name']);
            $person->setExtraData('alma-id', $values['Borrower Details::User Id']);

            $bookLoan->setBorrower($person);

            $collection->add($bookLoan);
        }

        // conserve memory
        unset($rows);
        unset($xml);

        if (!$isFinished) {
            $this->addAllBookLoansByLibraryToCollection($library, $collection, $resumptionData);
        }
    }

    /**
     * Posts a book offer return (sign-in) in Alma
     * See: https://developers.exlibrisgroup.com/alma/apis/docs/bibs/UE9TVCAvYWxtYXdzL3YxL2JpYnMve21tc19pZH0vaG9sZGluZ3Mve2hvbGRpbmdfaWR9L2l0ZW1zL3tpdGVtX3BpZH0=/.
     *
     * @param BookOffer $bookOffer
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws \League\Uri\Contracts\UriException
     */
    public function returnBookOffer(BookOffer &$bookOffer)
    {
        $this->checkReadOnlyMode();

        // check if the current user has permissions to a book offer with a certain library
        $this->checkCurrentPersonBookOfferPermissions($bookOffer);

        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $identifier = $bookOffer->getIdentifier();
        $libraryCode = $bookOffer->getLibrary();

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $client->request('POST', $this->urls->getReturnBookOfferUrl($identifier, $libraryCode), $options);

            $this->log("Book offer <{$identifier}> ({$bookOffer->getName()}) was returned", ['library' => $libraryCode]);
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);

            if ($e->getCode() === 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray['errorList']['error'][0]['errorCode'];

                switch ($errorCode) {
                    case 40166410:
                        throw new ItemNotStoredException(sprintf('Invalid institution: %s', $message));
                }
            }

            throw new ItemNotStoredException(sprintf("LibraryBookOffer id '%s' could not be returned! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {
        }
    }

    /**
     * Updates a loan in Alma.
     *
     * @param BookLoan $bookLoan
     *
     * @return BookLoan
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotStoredException
     * @throws \League\Uri\Contracts\UriException
     */
    public function updateBookLoan(BookLoan $bookLoan)
    {
        $this->checkReadOnlyMode();

        $identifier = $bookLoan->getIdentifier();
        $jsonData = $this->getBookLoanJsonData($identifier, false);

        // check if the current user has permissions to the book loan
        $bookOffer = $bookLoan->getObject();
        $this->checkCurrentPersonBookOfferPermissions($bookOffer);

        // XXX: Since 2023-01 release it returns "ACTIVE" but only allows titlecase "Active" when writing it back
        $jsonData->loan_status = ucwords(strtolower($bookLoan->getLoanStatus()));
        $jsonData->due_date = $bookLoan->getEndTime()->format('c');

        $client = $this->getClient();
        $options = [
            'json' => $jsonData,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('PUT', $this->urls->getBookLoanUrl($identifier), $options);

            $data = $this->decodeResponse($response);
            $bookLoan = $this->bookLoanFromJsonItem($data);

            $this->log("Book loan <{$identifier}> ({$bookOffer->getName()}) was updated",
                [
                    'loan_status' => $bookLoan->getLoanStatus(),
                    'due_date' => $bookLoan->getEndTime()->format('c'),
                ]
            );

            return $bookLoan;
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            if ($e->getCode() === 400) {
                $dataArray = $this->decodeResponse($e->getResponse());
                $errorCode = (int) $dataArray['errorList']['error'][0]['errorCode'];

                switch ($errorCode) {
                    case 401681:
                        throw new ItemNotStoredException(sprintf("LibraryBookLoan with id '%s' could not be stored! End time may not be in the past!", $identifier));
                        break;
                }
            }

            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotStoredException(sprintf("LibraryBookLoan with id '%s' could not be stored! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        }
    }

    public function checkPermissions()
    {
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }
        if (!$this->security->isGranted('ROLE_LIBRARY_MANAGER')) {
            throw new AccessDeniedException('Only library officers can access the library api!');
        }
    }

    /**
     * Retrieves all book offers with in the same holding and with the same location as $bookOffer.
     *
     * TODO: We are not allowed to use the field chronology_i any more, so this function is currently broken since the results are not sorted in the way we need it
     *
     * @param BookOffer $bookOffer
     *
     * @return ArrayCollection
     *
     * @throws ItemNotLoadedException
     * @throws \League\Uri\Contracts\UriException
     */
    public function locationIdentifiersByBookOffer(BookOffer $bookOffer): ArrayCollection
    {
        $collection = new ArrayCollection();
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $this->urls->getBookOfferLocationsIdentifierUrl($bookOffer), $options);

            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray['item'])) {
                return $collection;
            }

            $results = $dataArray['item'];

            array_walk($results, function (&$item) {
                $item = isset($item['item_data']) && isset($item['item_data']['alternative_call_number']) ?
                    $item['item_data']['alternative_call_number'] : '';
            });

            $results = array_unique($results);

            foreach ($results as $result) {
                $collection->add($result);
            }
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
        } catch (GuzzleException $e) {
        }

        return $collection;
    }

    /**
     * @param BookOffer $bookOffer
     *
     * @return array|null
     *
     * @throws ItemNotLoadedException
     * @throws \League\Uri\Contracts\UriException
     */
    public function getBookLoansJsonDataByBookOffer(BookOffer $bookOffer): ?array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $identifier = $bookOffer->getIdentifier();

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $this->urls->getBookOfferLoansUrl($identifier), $options);
            $dataArray = $this->decodeResponse($response);

            return $dataArray['item_loan'] ?? [];
        } catch (InvalidIdentifierException $e) {
            throw new ItemNotLoadedException(Tools::filterErrorMessage($e->getMessage()));
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookLoans of BookOffer with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * Gets all book loans for a person.
     *
     * @param Person $person
     *
     * @return array|null
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotUsableException
     * @throws \League\Uri\Contracts\UriException
     */
    public function getBookLoansJsonDataByPerson(Person $person): ?array
    {
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $identifier = $person->getIdentifier();
        $userId = $person->getExtraData('alma-id');

        if ($userId === null || $userId === '') {
            throw new ItemNotUsableException(sprintf('LibraryBookLoans cannot be fetched for %s! Person not registered in Alma!', $this->getPersonName($person)));
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
                $totalCount = (int) ($dataArray['total_record_count'] ?? 0);
                $resultList = array_merge($resultList, $dataArray['item_loan'] ?? []);
                $resultListCount = count($resultList);
                $offset += $limit;
                ++$loopCount;
            } while ($resultListCount < $totalCount && $loopCount < 50);

            return $resultList;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookLoans of Person with id '%s' could not be loaded! Message: %s", $identifier, $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * Checks if the current user has permissions to a book offer with a certain library.
     *
     * @param BookOffer $bookOffer
     *
     * @throws AccessDeniedException
     */
    public function checkCurrentPersonBookOfferPermissions(BookOffer &$bookOffer)
    {
        $person = $this->personProvider->getCurrentPerson();
        if (!$this->libraryProvider->isLibraryManagerByCode($person, $bookOffer->getLibrary())) {
            throw new AccessDeniedException(sprintf("Person '%s' is not allowed to work with library '%s'!", $person->getIdentifier(), $bookOffer->getLibrary()));
        }
    }

    /**
     * Return book loans where the user has permissions.
     *
     * @param BookLoan[] $bookLoans
     *
     * @return BookLoan[]
     */
    public function filterBookLoans(array $bookLoans): array
    {
        $currentPerson = $this->personProvider->getCurrentPerson();
        $libraryCodes = $this->libraryProvider->getSublibraryCodesByLibraryManager($currentPerson);

        $filtered = [];
        foreach ($bookLoans as $bookLoan) {
            if (in_array($bookLoan->getLibrary(), $libraryCodes, true)) {
                $filtered[] = $bookLoan;
            }
        }

        return $filtered;
    }

    /**
     * @param Sublibrary $sublibrary
     * @param array $resumptionData
     *
     * @return SimpleXMLElement|null
     *
     * @throws ItemNotLoadedException
     * @throws \League\Uri\Contracts\UriException
     */
    public function getBookOffersAnalyticsXMLByOrganization(Sublibrary $sublibrary, $resumptionData = []): ?SimpleXMLElement
    {
        $client = $this->getAnalyticsClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'X-Request-Counter' => $resumptionData['request-counter'],
                'X-Analytics-Updates-Hash' => $this->getAnalyticsUpdatesHash(),
            ],
        ];

        $libraryCode = $sublibrary->getCode();
        $resumptionToken = $resumptionData['token'] ?? '';

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $url = $this->urls->getBookOfferAnalyticsUrl($sublibrary, $resumptionToken);
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray['anies'][0])) {
                throw new ItemNotLoadedException(sprintf("LibraryBookOffers of library '%s' were not valid!", $libraryCode));
            }

            // we need to remove the encoding attribute, because the string in reality is UTF-8 encoded,
            // otherwise the XML parsing will fail
            $analyticsData = str_replace('encoding="UTF-16"', '', $dataArray['anies'][0]);

            // SimpleXMLElement shows no warnings and may just fail, so we are using simplexml_load_string
            $xml = simplexml_load_string($analyticsData);

            return $xml;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookOffers of library '%s' could not be loaded! Message: %s", $libraryCode, $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * @param Sublibrary $library
     * @param array $resumptionData
     *
     * @return SimpleXMLElement|null
     *
     * @throws ItemNotLoadedException
     * @throws \League\Uri\Contracts\UriException
     */
    public function getBookLoanAnalyticsXMLByOrganization(Sublibrary $library, $resumptionData = []): ?SimpleXMLElement
    {
        $client = $this->getAnalyticsClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'X-Request-Counter' => $resumptionData['request-counter'],
                'X-Analytics-Updates-Hash' => $this->getAnalyticsUpdatesHash(),
            ],
        ];

        $libraryCode = $library->getCode();
        $resumptionToken = $resumptionData['token'] ?? '';

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $url = $this->urls->getBookLoanAnalyticsUrl($library, $resumptionToken);

            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray['anies'][0])) {
                throw new ItemNotLoadedException(sprintf("LibraryBookLoans of library '%s' were not valid!", $libraryCode));
            }

            // we need to remove the encoding attribute, because the string in reality is UTF-8 encoded,
            // otherwise the XML parsing will fail
            $analyticsData = str_replace('encoding="UTF-16"', '', $dataArray['anies'][0]);

            // SimpleXMLElement shows no warnings and may just fail, so we are using simplexml_load_string
            $xml = simplexml_load_string($analyticsData);

            return $xml;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookLoans of library '%s' could not be loaded! Message: %s", $libraryCode, $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * @param Sublibrary $library
     * @param array $resumptionData
     *
     * @return SimpleXMLElement|null
     *
     * @throws ItemNotLoadedException
     * @throws \League\Uri\Contracts\UriException
     */
    public function getBookOrdersAnalyticsXMLByOrganization(Sublibrary $library, $resumptionData = []): ?SimpleXMLElement
    {
        $client = $this->getAnalyticsClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'X-Request-Counter' => $resumptionData['request-counter'],
                'X-Analytics-Updates-Hash' => $this->getAnalyticsUpdatesHash(),
            ],
        ];

        $libraryCode = $library->getCode();

        $resumptionToken = $resumptionData['token'] ?? '';

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $url = $this->urls->getBookOrderAnalyticsUrl($library, $resumptionToken);
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray['anies'][0])) {
                throw new ItemNotLoadedException(sprintf("LibraryBookOrders of library with id '%s' were not valid!", $libraryCode));
            }

            // we need to remove the encoding attribute, because the string in reality is UTF-8 encoded,
            // otherwise the XML parsing will fail
            $analyticsData = str_replace('encoding="UTF-16"', '', $dataArray['anies'][0]);
            // SimpleXMLElement shows no warnings and may just fail, so we are using simplexml_load_string
            $xml = simplexml_load_string($analyticsData);

            return $xml;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf("LibraryBookOrders of library '%s' could not be loaded! Message: %s", $libraryCode, $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * @return SimpleXMLElement|null
     *
     * @throws ItemNotLoadedException
     * @throws \League\Uri\Contracts\UriException
     */
    public function getBudgetMonetaryAmountAnalyticsXML(): ?SimpleXMLElement
    {
        $client = $this->getAnalyticsClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'X-Analytics-Updates-Hash' => $this->getAnalyticsUpdatesHash(),
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $url = $this->urls->getBudgetMonetaryAmountAnalyticsUrl();
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray['anies'][0])) {
                throw new ItemNotLoadedException('BudgetMonetaryAmounts were not valid!');
            }

            // we need to remove the encoding attribute, because the string in reality is UTF-8 encoded,
            // otherwise the XML parsing will fail
            $analyticsData = str_replace('encoding="UTF-16"', '', $dataArray['anies'][0]);

            // SimpleXMLElement shows no warnings and may just fail, so we are using simplexml_load_string
            return simplexml_load_string($analyticsData);
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf('BudgetMonetaryAmounts could not be loaded! Message: %s', $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * Returns the BudgetMonetaryAmounts for a Sublibrary.
     *
     * @param Sublibrary $library
     *
     * @return BudgetMonetaryAmount[]
     *
     * @throws ItemNotLoadedException
     * @throws \League\Uri\Contracts\UriException
     */
    public function getBudgetMonetaryAmountsByLibrary(Sublibrary $library): array
    {
        $xml = $this->getBudgetMonetaryAmountAnalyticsXML();
        $mapping = AlmaUtils::getColumnMapping($xml);
        $libraryCode = $library->getCode();
        $fundLedgerCode = $libraryCode.'MON';
        $rows = $xml->xpath('ResultXml/rowset/Row');

        if (count($rows) === 0) {
            return [];
        }

        $organizationBudgetList = [];
        foreach ($rows as $row) {
            try {
                $values = AlmaUtils::mapRowColumns($row, $mapping);

                if ($values['Funds::Fund Code'] === $fundLedgerCode) {
                    $names = self::budgetMonetaryAmountNames();

                    foreach (array_keys($names) as $key) {
                        self::addBudgetMonetaryAmountToList($organizationBudgetList, $values, $key, $library);
                    }

                    break;
                }
            } catch (\Exception $e) {
            }
        }

        return $organizationBudgetList;
    }

    /**
     * @param array $values
     * @param string $key
     * @param Sublibrary $library
     *
     * @return BudgetMonetaryAmount|null
     */
    private static function budgetMonetaryAmountFromAnalyticsRow(
        array $values,
        string $key,
        Sublibrary $library
    ): ?BudgetMonetaryAmount {
        $names = self::budgetMonetaryAmountNames();

        if (!array_key_exists($key, $names)) {
            return null;
        }

        $name = $names[$key];
        $organizationBudget = new BudgetMonetaryAmount();
        $organizationBudget->setIdentifier($library->getCode().'-'.$name);
        $organizationBudget->setName($name);
        // careful with decimal numbers and float :/
        $organizationBudget->setValue(((float) $values[$key]));
        $organizationBudget->setCurrency('EUR');

        return $organizationBudget;
    }

    /**
     * @param array $organizationBudgetList
     * @param array $values
     * @param string $key
     * @param Sublibrary $library
     */
    private static function addBudgetMonetaryAmountToList(
        array &$organizationBudgetList,
        array $values,
        string $key,
        Sublibrary $library
    ) {
        $budgetMonetaryAmount = self::budgetMonetaryAmountFromAnalyticsRow($values, $key, $library);

        if ($budgetMonetaryAmount !== null) {
            $organizationBudgetList[] = $budgetMonetaryAmount;
        }
    }

    /**
     * Fetches the AnalyticsUpdates Analytics to check if our Analytics data was updated.
     *
     * @throws ItemNotLoadedException
     */
    public function getAnalyticsUpdatesAnalyticsXML(): ?SimpleXMLElement
    {
        $client = $this->getAnalyticsUpdatesClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $url = $this->urls->getAnalyticsUpdatesAnalyticsUrl();
            $response = $client->request('GET', $url, $options);
            $dataArray = $this->decodeResponse($response);

            if (!isset($dataArray['anies'][0])) {
                throw new ItemNotLoadedException('AnalyticsUpdates were not valid!');
            }

            // we need to remove the encoding attribute, because the string in reality is UTF-8 encoded,
            // otherwise the XML parsing will fail
            $analyticsData = str_replace('encoding="UTF-16"', '', $dataArray['anies'][0]);
            // SimpleXMLElement shows no warnings and may just fail, so we are using simplexml_load_string
            $xml = simplexml_load_string($analyticsData);

            return $xml;
        } catch (RequestException $e) {
            $message = $this->getRequestExceptionMessage($e);
            throw new ItemNotLoadedException(sprintf('AnalyticsUpdates could not be loaded! Message: %s', $message));
        } catch (GuzzleException $e) {
        }

        return null;
    }

    /**
     * @param array $resumptionData
     *
     * @throws ItemNotLoadedException
     */
    public function addAllBookOffersByLibraryToCollection(Sublibrary $library, ArrayCollection &$collection, $resumptionData = [])
    {
        // we need to set a request counter for caching (otherwise the requests would all be the same)
        $resumptionData['request-counter'] = $resumptionData['request-counter'] ?? 0;
        ++$resumptionData['request-counter'];

        $xml = $this->getBookOffersAnalyticsXMLByOrganization($library, $resumptionData);

        $resumptionData['mapping'] = $resumptionData['mapping'] ?? AlmaUtils::getColumnMapping($xml);
        $mapping = $resumptionData['mapping'];
        if (empty($mapping)) {
            throw new \RuntimeException('Missing mapping');
        }
        // we only get a ResumptionToken at the first request, but we need to add the token to every subsequent request
        $resumptionData['token'] = $resumptionData['token'] ?? (string) $xml->ResumptionToken;

        $isFinished = ((string) $xml->IsFinished) !== 'false';
        $rows = $xml->xpath('ResultXml/rowset/Row');

        /** @var SimpleXMLElement $row */
        foreach ($rows as $row) {
            $values = AlmaUtils::mapRowColumns($row, $mapping);
            $mmsId = $values['Bibliographic Details::MMS Id'];
            $holdingId = $values['Holding Details::Holding Id'];
            $itemId = $values['Physical Item Details::Physical Item Id'] ?? $values['Physical Item Details::Item Id'];

            if ($mmsId === '' || $holdingId === '' || $itemId === '') {
                continue;
            }

            $bookOffer = new BookOffer();
            $bookOffer->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}");
            $bookOffer->setBarcode($values['Physical Item Details::Barcode']);
            $bookOffer->setDescription($values['Physical Item Details::Description'] ?? '');
            // Item Call Number (we would need the alternative_call_number, but it seems ok)
            $bookOffer->setLocationIdentifier($values['Physical Item Details::Item Call Number'] ?? '');
            // Location Code
            $bookOffer->setLocation($values['Location::Location Code']);
            // Library Code
            $bookOffer->setLibrary($values['Location::Library Code']);

            $inventoryDate = $values['Physical Item Details::Inventory Date'];
            if ($inventoryDate !== '') {
                try {
                    $bookOffer->setAvailabilityStarts(new DateTime($inventoryDate));
                } catch (\Exception $e) {
                } catch (\TypeError $e) {
                    // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
                }
            }

            $book = new Book();
            $book->setIdentifier("{$mmsId}-{$holdingId}-{$itemId}");
            $book->setTitle($values['Bibliographic Details::Title']);
            $book->setAuthor($values['Bibliographic Details::Author']);
            $book->setPublisher($values['Bibliographic Details::Publisher']);

            $publicationDate = $values['Bibliographic Details::Publication Date'];
            if ($publicationDate !== '') {
                try {
                    $publicationYear = (int) $publicationDate;
                    $book->setDatePublished(new DateTime("${publicationYear}-01-01"));
                } catch (\Exception $e) {
                } catch (\TypeError $e) {
                    // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
                }
            }

            $bookOffer->setBook($book);

            $collection->add($bookOffer);
        }

        // conserve memory
        unset($rows);
        unset($xml);

        if (!$isFinished) {
            $this->addAllBookOffersByLibraryToCollection($library, $collection, $resumptionData);
        }
    }

    /**
     * @param string $id
     *
     * @return BookOrder
     */
    public function getBookOrder(string $id): BookOrder
    {
        $matches = [];
        // TODO: CAUTION used to be pattern with compound org ID: '/^o-(\w+-F\w+)-(.+)$/i'
        if (!preg_match('/^o-(\w+)-(.+)$/i', $id, $matches)) {
            throw new ItemNotFoundException(sprintf("BookOrder with id '%s' could not be found!", $id));
        }

        $libraryId = $matches[1];
        $library = $this->libraryProvider->getSublibrary($libraryId);
        if ($library === null) {
            throw new ItemNotFoundException("library with id '".$libraryId."' not found!");
        }

        $this->checkCurrentPersonLibraryPermissions($library);

        // fetch all book orders of the organization
        $collection = new ArrayCollection();
        $this->addAllBookOrdersByLibraryToCollection($library, $collection);

        // search for the correct book order in the collection of book orders
        /** @var BookOrder $bookOrder */
        foreach ($collection as $bookOrder) {
            if ($bookOrder->getIdentifier() === $id) {
                return $bookOrder;
            }
        }

        throw new ItemNotFoundException(sprintf("BookOrder with id '%s' could not be found!", $id));
    }

    /**
     * @throws ApiError|ItemNotLoadedException
     */
    public function getBookOrders(array $filters): ArrayCollection
    {
        $libraryId = $filters['sublibrary'] ?? null;
        if (empty($libraryId)) {
            throw new ApiError(Response::HTTP_BAD_REQUEST, "parameter 'sublibrary' is mandatory!");
        }

        $library = $this->libraryProvider->getSublibrary($libraryId);
        if ($library === null) {
            throw new ItemNotFoundException("library with id '".$libraryId."' not found!");
        }
        $this->checkCurrentPersonLibraryPermissions($library);

        $this->setAnalyticsUpdateDateHeader();

        $collection = new ArrayCollection();
        $this->addAllBookOrdersByLibraryToCollection($library, $collection);

        return $collection;
    }

    /**
     * @param array $resumptionData
     *
     * @throws ItemNotLoadedException
     */
    public function addAllBookOrdersByLibraryToCollection(Sublibrary $library, ArrayCollection &$collection, $resumptionData = [])
    {
        // we need to set a request counter for caching (otherwise the requests would all be the same)
        $resumptionData['request-counter'] = $resumptionData['request-counter'] ?? 0;
        ++$resumptionData['request-counter'];

        $xml = $this->getBookOrdersAnalyticsXMLByOrganization($library, $resumptionData);

        $resumptionData['mapping'] = $resumptionData['mapping'] ?? AlmaUtils::getColumnMapping($xml);
        $mapping = $resumptionData['mapping'];
        if (empty($mapping)) {
            throw new \RuntimeException('Missing mapping');
        }
        // we only get a ResumptionToken at the first request, but we need to add the token to every subsequent request
        $resumptionData['token'] = $resumptionData['token'] ?? (string) $xml->ResumptionToken;

        $isFinished = ((string) $xml->IsFinished) !== 'false';
        $rows = $xml->xpath('ResultXml/rowset/Row');

        // FIXME: We get duplicated entries where Invoice Line-Currency/Invoice-Currency/Invoice Line Total Price
        // are missing. Since we don't use them right now just ignore those duplicates.
        // TODO: Figure out what's wrong with the Analytics
        $alreadySeen = [];

        /** @var SimpleXMLElement $row */
        foreach ($rows as $row) {
            $values = AlmaUtils::mapRowColumns($row, $mapping);

            $poNumber = $values['PO Line::PO Line Reference'];
            if ($poNumber === '') {
                continue;
            }

            // FIXME
//            if (key_exists($poNumber, $alreadySeen)) {
//                continue;
//            }
            $alreadySeen[$poNumber] = true;

            $bookOrder = new BookOrder();
            // PO Number
            $identifierData = explode('-', $poNumber);

            // TODO: "o" used to stand for Organization, maybe replace by "l"
            $identifier = 'o-'.$library->getIdentifier().'-'.urlencode($identifierData[0]);
            $bookOrder->setIdentifier($identifier);

            $bookOrder->setOrderStatus($values['PO Line::Status (Active)']);
            $bookOrder->setOrderNumber($poNumber);
            $bookOrder->setReceivingNote($values['PO Line::Receiving/Activation Note']);

            $poCreationDate = $values['PO Line::PO Creation Date'];
            if ($poCreationDate !== '') {
                try {
                    // PO Creation Date
                    $bookOrder->setOrderDate(new DateTime($poCreationDate));
                } catch (\Exception $e) {
                } catch (\TypeError $e) {
                    // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
                }
            }

            $eventStatus = new EventStatusType();
            $eventStatus->setIdentifier($identifier);
            $eventStatus->setName(strtolower($values['PO Line::Status']));

            $deliveryEvent = new DeliveryEvent();
            $deliveryEvent->setIdentifier($identifier);
            $deliveryEvent->setEventStatus($eventStatus);

            // 'PO Line::Claiming Date' is currently not set
            $claimingDate = $values['PO Line::Claiming Date'] ?? '';
            if ($claimingDate !== '') {
                try {
                    // Claiming Date
                    $deliveryEvent->setAvailableFrom(new DateTime($claimingDate));
                } catch (\Exception $e) {
                } catch (\TypeError $e) {
                    // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
                }
            }

            $parcelDelivery = new ParcelDelivery();
            $parcelDelivery->setIdentifier($identifier);
            $parcelDelivery->setDeliveryStatus($deliveryEvent);

            $book = new Book();
            $book->setIdentifier($values['Bibliographic Details::MMS Id']);
            $book->setTitle($values['Bibliographic Details::Title']);
            $book->setAuthor($values['Bibliographic Details::Author']);
            $book->setISBN($values['PO Line::PO Line Identifier'] ?? '');

            $bookOrderItem = new BookOrderItem();
            $bookOrderItem->setIdentifier($identifier);
            $bookOrderItem->setOrderDelivery($parcelDelivery);
            $bookOrderItem->setOrderedItem($book);
            $bookOrderItem->setPrice((float) $values['PO Line:: CASE  WHEN Invoice Status = \'No invoice\' AND Status = \'CLOSED\' THEN 0 ELSE  IFNULL(Invoice Line Total Price, PO Line Total Price) END']);
            $bookOrderItem->setPriceCurrency($values['Fund Transactions:: CASE  WHEN Invoice Line Total Price IS NULL  THEN Currency ELSE Invoice Line-Currency END']);

            $bookOrder->setOrderedItem($bookOrderItem);

            $collection->add($bookOrder);
        }

        // conserve memory
        unset($rows);
        unset($xml);

        if (!$isFinished) {
            $this->addAllBookOrdersByLibraryToCollection($library, $collection, $resumptionData);
        }
    }

    /**
     * Returns a hash to use for caching Analytics requests to check if our Analytics data was updated.
     */
    private function getAnalyticsUpdatesHash()
    {
        if ($this->analyticsUpdatesHash !== '') {
            return $this->analyticsUpdatesHash;
        }

        try {
            $xml = $this->getAnalyticsUpdatesAnalyticsXML();
        } catch (ItemNotLoadedException $e) {
            return $this->getFallbackAnalyticsUpdatesHash();
        }

        $rows = $xml->xpath('ResultXml/rowset/Row');

        if (count($rows) === 0) {
            return $this->getFallbackAnalyticsUpdatesHash();
        }

        /** @var SimpleXMLElement $data */
        $data = $rows[0];

        return $this->analyticsUpdatesHash = sha1($data->asXML());
    }

    /**
     * Returns the hash array of Analytics.
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

        $rows = $xml->xpath('ResultXml/rowset/Row');

        if (count($rows) === 0) {
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
     * Returns the datetime when the Analytics where last updated.
     *
     * @return DateTime|null
     */
    public function getAnalyticsUpdateDate()
    {
        $values = $this->getAnalyticsUpdatesData();

        if ($values === null) {
            return null;
        }

        $dateString = $values['Institution::Data Updated As Of'].' '.$values['Institution::Institution Timezone'];

        try {
            $datetime = new DateTime($dateString);
        } catch (\Exception $e) {
            return null;
        } catch (\TypeError $e) {
            // TypeError is no sub-class of Exception! See https://www.php.net/manual/en/class.typeerror.php
            return null;
        }

        return $datetime;
    }

    /**
     * Set the HTTP header for when the Analytics where last updated.
     */
    public function setAnalyticsUpdateDateHeader()
    {
        $datetime = $this->getAnalyticsUpdateDate();

        if ($datetime !== null) {
            header('X-Analytics-Update-Date: '.$datetime->format(DateTime::ATOM));
        }
    }

    private function getFallbackAnalyticsUpdatesHash(): string
    {
        return $this->analyticsUpdatesHash = sha1((string) (rand(0, 10000) + time()));
    }

    /**
     * @param mixed[] $context
     */
    private function log(string $message, array $context = [])
    {
        $context['service'] = 'Alma';
        if ($this->logger !== null) {
            $this->logger->notice('[{service}] '.$message, $context);
        }
    }

    private function isReadOnlyMode(): bool
    {
        return $this->readonly;
    }

    private function checkReadOnlyMode()
    {
        if ($this->isReadOnlyMode()) {
            throw new AccessDeniedException(sprintf('The Alma API currently is in read-only mode!'));
        }
    }

    /**
     * @throws AccessDeniedException
     */
    public function checkCurrentPersonLibraryPermissions(Sublibrary $library)
    {
        $person = $this->personProvider->getCurrentPerson();
        if (!$this->libraryProvider->isLibraryManagerById($person, $library->getIdentifier())) {
            throw new AccessDeniedException(sprintf("Person '%s' is not allowed to work with library '%s'!", $person->getIdentifier(), $library->getCode()));
        }
    }

    public function getCurrentPerson(): ?Person
    {
        return $this->personProvider->getCurrentPerson();
    }
}
