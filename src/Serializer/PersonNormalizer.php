<?php
namespace DBP\API\AlmaBundle\Serializer;

use DBP\API\CoreBundle\Entity\Person;
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
        if ($this->security->isGranted('ROLE_F_BIB_F')) {
            $context['groups'][] = 'birthdate_access';
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