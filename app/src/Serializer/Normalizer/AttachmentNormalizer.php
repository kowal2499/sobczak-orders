<?php

namespace App\Serializer\Normalizer;

use App\Entity\Attachment;
use App\Service\UploaderHelper;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AttachmentNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;
    private $uploaderHelper;

    public function __construct(ObjectNormalizer $normalizer, UploaderHelper $uploaderHelper)
    {
        $this->normalizer = $normalizer;
        $this->uploaderHelper = $uploaderHelper;
    }

    /**
     * @param Attachment $object
     * @param null $format
     * @param array $context
     * @return array
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        // Dodaj ścieżki przez kontroler zamiast bezpośrednich URLi
        $data['id'] = $object->getId();
        $data['path'] = '/attachments/' . $object->getId() . '/download';
        $data['viewPath'] = '/attachments/' . $object->getId() . '/view';
        $data['thumbnail'] = $this->uploaderHelper->getPublicPathThumbnail($object->getPath());

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Attachment;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
