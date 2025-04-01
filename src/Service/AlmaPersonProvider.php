<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Service;

use Dbp\Relay\BasePersonBundle\API\PersonProviderInterface;
use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;

class AlmaPersonProvider
{
    private PersonProviderInterface $personProvider;
    private string $emailAttribute;
    private string $almaIdAttribute;

    public function __construct(PersonProviderInterface $personProvider)
    {
        $this->personProvider = $personProvider;
    }

    public function setConfig(array $config): void
    {
        $personLocalDataAttributes = $config['person_local_data_attributes'];
        $this->emailAttribute = $personLocalDataAttributes['email'];
        $this->almaIdAttribute = $personLocalDataAttributes['alma_id'];
    }

    public function getCurrentPerson(bool $addInternalAttributes): ?Person
    {
        $options = [];
        $attributes = [$this->emailAttribute];
        if ($addInternalAttributes) {
            $attributes[] = $this->almaIdAttribute;
        }

        Options::requestLocalDataAttributes($options, $attributes);

        return $this->personProvider->getCurrentPerson($options);
    }

    public function getPersonForAlmaId(string $almaId, bool $addInternalAttributes): ?Person
    {
        $options = [];
        // filter: get person(s) whose Alma user ID matches the ID
        $filter = FilterTreeBuilder::create()->equals('localData.'.$this->almaIdAttribute, $almaId)->createFilter();
        Options::setFilter($options, $filter);

        $attributes = [$this->emailAttribute];
        if ($addInternalAttributes) {
            $attributes[] = $this->almaIdAttribute;
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
        $attributes = [$this->emailAttribute];
        if ($addInternalAttributes) {
            $attributes[] = $this->almaIdAttribute;
        }

        $options = [];
        Options::requestLocalDataAttributes($options, $attributes);

        return $this->personProvider->getPerson($personIdentifier, $options);
    }

    public function getAlmaId(Person $person): ?string
    {
        return $person->getLocalDataValue($this->almaIdAttribute);
    }
}
