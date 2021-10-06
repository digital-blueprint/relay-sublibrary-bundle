<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Serializer;

use DBP\API\BaseBundle\Entity\Person;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class PersonNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'LDAP_PERSON_ATTRIBUTE_NORMALIZER_LIBRARY_ALREADY_CALLED';

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        if ($this->security->isGranted('ROLE_LIBRARY_MANAGER')) {
            $context['groups'][] = 'BasePerson:extended-access';
            // Only for backwards compatibility. Remove once the
            // base bundle has the renaming to BasePerson complete.
            $context['groups'][] = 'Person:extended-access';
        }

        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        // Make sure we're not called twice
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Person;
    }
}
