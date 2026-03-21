<?php

namespace App\Service;

use App\Utilities\Slugger;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const AGREEMENTS_PATH = 'agreements';
    const IMAGES_PATH = 'imgs';

    private string $uploadsPath;
    private string $thumbnailsPath;
    private RequestStackContext $requestStackContext;
    private string $uploadedAssetsBaseUrl;
    private string $uploadedAssetsDir;
    private string $thumbnailsAssetsDir;

    public function __construct(
        string $uploadsPath,
        string $thumbnailsPath,
        RequestStackContext $requestStackContext,
        string $uploadedAssetsBaseUrl,
        string $uploadedAssetsDir,
        string $thumbnailsAssetsDir,
    )
    {
        $this->uploadsPath = $uploadsPath;
        $this->thumbnailsPath = $thumbnailsPath;
        $this->requestStackContext = $requestStackContext;
        $this->uploadedAssetsBaseUrl = $uploadedAssetsBaseUrl;
        $this->uploadedAssetsDir = $uploadedAssetsDir;
        $this->thumbnailsAssetsDir = $thumbnailsAssetsDir;
    }

    public function uploadAttachment(UploadedFile $uploadedFile): array
    {
        $destination = $this->uploadsPath . '/' . self::AGREEMENTS_PATH;

        $originalFileName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $verifiedExtension = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_EXTENSION);

        $newFileName = Slugger::slugify($originalFileName) . '-' . uniqid() . '.' . $verifiedExtension;
        $uploadedFile->move($destination, $newFileName);

        return [
            'newFileName' => $newFileName,
            'originalFileName' => $originalFileName,
            'extension' => $verifiedExtension
        ];
    }

    public function getRelativePath(string $path): string
    {
        return $this->requestStackContext->getBasePath() . '/' . $this->uploadedAssetsDir . '/' . $path;
    }

    public function getPublicPath(string $path): string
    {
        return $this->uploadedAssetsBaseUrl . $this->getRelativePath($path);
    }

    public function getPublicPathThumbnail(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
            case 'png':
                $thumbnailFileName = $this->generateImageThumbnail($path);
                if (!$thumbnailFileName) {
                    $resultPath = $this->getAssetUrl() . 'imgs/other.svg';
                    break;
                }
                $resultPath = $this->getAssetUrl() . $this->thumbnailsAssetsDir . '/' . $thumbnailFileName;
                break;
            default:
                $thumbPath = $this->thumbnailsPath . '/';
                $iconFileName = strtolower($extension) . '.svg';
                if (!file_exists($thumbPath . $iconFileName)) {
                    if (!$this->generateFileTypeThumbnail($thumbPath, $iconFileName)) {
                        $resultPath = $this->getAssetUrl() . 'imgs/other.svg';
                        break;
                    }
                }
                $resultPath = $this->getAssetUrl() . $this->thumbnailsAssetsDir . '/' . $iconFileName;
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
        if (isset($data['name']) && isset($data['tmp_name'])) {
            $values = array_values($data);
            if (!isset($values[0])) {
                return $filesCollection;
            }
            for ($i = 0; $i < count($values[0]); $i++) {
                $file = new UploadedFile(
                    $data['tmp_name'][$i],
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

    /**
     * Generuje miniaturę obrazu w stałym folderze thumbs
     * Używa GD do tworzenia miniatury 200x200px
     *
     * @param string $path Relatywna ścieżka do oryginalnego obrazu (np. "agreements/file.jpg")
     * @return string|null      Nazwa pliku miniatury
     */
    private function generateImageThumbnail(string $path): ?string
    {
        $sourceFilePath = $this->uploadsPath . '/' . $path;

        // Generuj nazwę dla miniatury
        $pathInfo = pathinfo($path);
        $thumbnailFileName = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        $thumbnailFilePath = $this->thumbnailsPath . '/' . $thumbnailFileName;

        // Upewnij się że folder thumbs istnieje
        if (!is_dir($this->thumbnailsPath)) {
            mkdir($this->thumbnailsPath, 0755, true);
        }

        // Sprawdź czy plik źródłowy istnieje
        if (!file_exists($sourceFilePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Utwórz obiekt obrazu źródłowego
        $sourceImage = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($sourceFilePath),
            'png' => @imagecreatefrompng($sourceFilePath),
            default => null
        };

        if (!$sourceImage) {
            return null;
        }

        // Pobierz wymiary oryginalnego obrazu
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Oblicz wymiary miniatury (200x200, tryb outbound)
        $thumbSize = 200;
        $sourceRatio = $sourceWidth / $sourceHeight;

        if ($sourceRatio > 1) {
            $cropWidth = $sourceHeight;
            $cropHeight = $sourceHeight;
            $cropX = ($sourceWidth - $sourceHeight) / 2;
            $cropY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = $sourceWidth;
            $cropX = 0;
            $cropY = ($sourceHeight - $sourceWidth) / 2;
        }

        $thumbnail = imagecreatetruecolor($thumbSize, $thumbSize);

        // Zachowaj przezroczystość dla PNG
        if ($extension === 'png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $thumbSize, $thumbSize, $transparent);
        }

        // Przeskaluj i przytnij obraz
        imagecopyresampled(
            $thumbnail,
            $sourceImage,
            0, 0,
            $cropX, $cropY,
            $thumbSize, $thumbSize,
            $cropWidth, $cropHeight
        );

        // Zapisz miniaturę
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumbnail, $thumbnailFilePath, 85);
                break;
            case 'png':
                imagepng($thumbnail, $thumbnailFilePath, 8);
                break;
        }

        // Zwolnij pamięć
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);

        return $thumbnailFileName;
    }

    private function generateFileTypeThumbnail(
        string $path,
        string $fileName,
        string $color = '#4e73df',
        int $size = 128
    ): bool
    {
        [$name] = explode('.', $fileName);
        $svg = $this->buildSvg($name, $color, $size);

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($path . $fileName, $svg) !== false;
    }

    /**
     * @param string $type Nazwa typu pliku (np. "PDF", "XLSX", "MP4")
     * @param string $color Kolor ikony w formacie hex (domyślnie '#1a1a2e')
     * @param int $size Rozmiar w pikselach (domyślnie 128)
     * @return string         Zawartość SVG
     */
    private function buildSvg(string $type, string $color = '#4e73df', int $size = 128): string
    {
        $type = strtoupper(trim($type));
        $isLong = strlen($type) > 3;
        $fontSize = $isLong ? 13 : 15;

        $color = htmlspecialchars($color, ENT_XML1);
        $type = htmlspecialchars($type, ENT_XML1);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 52 64">
          <defs>
            <style>.lbl { font-family: Arial, Helvetica, sans-serif; font-weight: 900; }</style>
          </defs>
          <path d="M4 6 Q4 2 8 2 L34 2 L48 16 L48 58 Q48 62 44 62 L8 62 Q4 62 4 58 Z"
            fill="none" stroke="{$color}" stroke-width="2.5" stroke-linejoin="round"/>
          <path d="M34 2 L34 16 L48 16"
            fill="none" stroke="{$color}" stroke-width="2.5" stroke-linejoin="round"/>
          <rect x="0" y="24" width="52" height="16" fill="{$color}"/>
          <text class="lbl" x="26" y="32" text-anchor="middle" dominant-baseline="central"
            fill="white" font-size="{$fontSize}">{$type}</text>
        </svg>
        SVG;
    }

    /**
     * @return string
     */
    private function getAssetUrl(): string
    {
        return $this->uploadedAssetsBaseUrl
            . $this->requestStackContext->getBasePath() . '/';
    }
}

