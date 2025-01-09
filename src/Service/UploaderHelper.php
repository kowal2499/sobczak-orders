<?php


namespace App\Service;


use Gedmo\Sluggable\Util\Urlizer;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const AGREEMENTS_PATH = 'agreements';

    private $uploadsPath;
    private $requestStackContext;
    private $cacheManager;
    private $uploadedAssetsBaseUrl;
    private $uploadedAssetsDir;

    public function __construct(
        string $uploadsPath,
        RequestStackContext $requestStackContext,
        CacheManager $cacheManager,
        string $uploadedAssetsBaseUrl,
        string $uploadedAssetsDir
    )
    {
        $this->uploadsPath = $uploadsPath;
        $this->requestStackContext = $requestStackContext;
        $this->cacheManager = $cacheManager;
        $this->uploadedAssetsBaseUrl = $uploadedAssetsBaseUrl;
        $this->uploadedAssetsDir = $uploadedAssetsDir;
    }

    public function uploadAttachment(UploadedFile $uploadedFile): array
    {
        $destination = $this->uploadsPath . '/' . self::AGREEMENTS_PATH;

        $originalFileName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
//        $verifiedExtension = $uploadedFile->guessExtension();
        $verifiedExtension = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_EXTENSION);

        $newFileName = Urlizer::urlize($originalFileName) . '-' . uniqid() . '.' . $verifiedExtension;
        $uploadedFile->move($destination, $newFileName);

        return [
            'newFileName' => $newFileName,
            'originalFileName' => $originalFileName,
            'extension' => $verifiedExtension
        ];
    }

    public function getPublicPath(string $path): string
    {
        return $this->uploadedAssetsBaseUrl . $this->requestStackContext->getBasePath() . '/' . $this->uploadedAssetsDir . '/' . $path;
    }

    public function getPublicPathThumbnail(string $path): string
    {
        $resultPath = '';
        switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'jpg':
            case 'jpeg':
            case 'png':
                $resultPath = $this->cacheManager->getBrowserPath(
                        $this->requestStackContext->getBasePath() . '/' . $this->uploadedAssetsDir . '/' . $path,
                        'squared_thumbnail_small'
                    );
                    break;
            case 'pdf':
                $resultPath = $this->uploadedAssetsBaseUrl . $this->requestStackContext->getBasePath() . '/imgs/pdf.png';
                break;
        }

        return $resultPath;
    }

    /**
     * @param array $data
     * @param int|null $error
     * @return UploadedFile[]
     */
    public function getUploadedFiles(array $data, int $error = null): array
    {
        $filesCollection = [];
        if (isset($data['name']) && isset($data['full_path'])) {
            $values = array_values($data);
            if (!isset($values[0])) {
                return $filesCollection;
            }
            for ($i = 0; $i < count($values[0]); $i++) {
                $file = new UploadedFile(
                    $data['full_path'][$i],
                    $data['name'][$i],
                    $data['type'][$i] ?? null,
                    $error
                );
                $filesCollection[] = $file;
            }
        } else {
            foreach ($data as $file) {
                if ($file instanceof UploadedFile) {
                    $filesCollection[] = $file;
                }
            }
        }

        return $filesCollection;
    }
}