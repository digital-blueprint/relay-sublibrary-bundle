<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Service;

use Dbp\Relay\BasePersonBundle\API\PersonProviderInterface;
use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;

class AlmaPersonProvider
{
    private const EMAIL_ATTRIBUTE = 'email';
    private const ALMA_ID_ATTRIBUTE = 'almaId';

    private PersonProviderInterface $personProvider;

    public function __construct(PersonProviderInterface $personProvider)
    {
        $this->personProvider = $personProvider;
    }

    public function getCurrentPerson(bool $addInternalAttributes): ?Person
    {
        $options = [];
        $attributes = [self::EMAIL_ATTRIBUTE];
        if ($addInternalAttributes) {
            $attributes[] = self::ALMA_ID_ATTRIBUTE;
        }

        Options::requestLocalDataAttributes($options, $attributes);

        return $this->personProvider->getCurrentPerson($options);
    }

    public function getPersonForAlmaId(string $almaId, bool $addInternalAttributes): ?Person
    {
        $options = [];
        // filter: get person(s) whose Alma user ID matches the ID
        $filter = FilterTreeBuilder::create()->equals('localData.'.self::ALMA_ID_ATTRIBUTE, $almaId)->createFilter();
        Options::setFilter($options, $filter);

        $attributes = [self::EMAIL_ATTRIBUTE];
        if ($addInternalAttributes) {
            $attributes[] = self::ALMA_ID_ATTRIBUTE;
        }

        Options::requestLocalDataAttributes($options, $attributes);
        $persons = $this->personProvider->getPersons(1, 1, $options);
        if (count($persons) > 0) {
            return $persons[0];
        }

        return null;
    }

    public function getPerson(string $personIdentifier, bool $addInternalAttributes): ?Person
    {
        $attributes = [self::EMAIL_ATTRIBUTE];
        if ($addInternalAttributes) {
            $attributes[] = self::ALMA_ID_ATTRIBUTE;
        }

        $options = [];
        Options::requestLocalDataAttributes($options, $attributes);

        return $this->personProvider->getPerson($personIdentifier, $options);
    }

    public function getAlmaId(Person $person): ?string
    {
        return $person->getLocalDataValue(self::ALMA_ID_ATTRIBUTE);
    }
}
