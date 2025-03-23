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

        // Here: add, edit, or delete some data
        $data['path'] = $this->uploaderHelper->getPublicPath($object->getPath());
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
