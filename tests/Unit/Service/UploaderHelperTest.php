<?php

namespace App\Tests\Unit\Service;

use App\Service\UploaderHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelperTest extends TestCase
{
    /** @var UploaderHelper */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new UploaderHelper(
            '',
            $this->createMock(RequestStackContext::class),
            $this->createMock(CacheManager::class),
            '',
            ''
        );
    }

    public function testShouldDenormalizeArrayIntoUploadedFileInstance(): void
    {
        // Given
        $attachmentsAsArray = [
            'name' => ['baic.png', 'Grafika-przykładowa.jpg'],
            'full_path' => ['/tmp/baic.png', '/tmp/Grafika-przykładowa.jpg'],
            'type' => ['image/png', 'image/jpeg'],
            'tmp_name' => ['/tmp/php7WAcl5', '/tmp/phpqa8cRv'],
            'error' => [],
            'size' => [12277, 33048]
        ];
        // When
        $result = $this->sut->getUploadedFiles($attachmentsAsArray, 1);

        // Then
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(UploadedFile::class, $result);
        $this->assertEquals('baic.png', $result[0]->getFilename());
        $this->assertEquals('/tmp/baic.png', $result[0]->getPathname());
        $this->assertEquals('image/png', $result[0]->getClientMimeType());

        $this->assertEquals('Grafika-przykładowa.jpg', $result[1]->getFilename());
        $this->assertEquals('/tmp/Grafika-przykładowa.jpg', $result[1]->getPathname());
        $this->assertEquals('image/jpeg', $result[1]->getClientMimeType());
    }

    public function testShouldHandleUploadedFiles(): void
    {
        // Given
        $attachmentsAsArray = [
            new UploadedFile('/tmp/baic.png', 'baic.png', 'image/png', 1),
            new UploadedFile('/tmp/Grafika-przykładowa.jpg', 'Grafika-przykładowa.jpg', 'image/jpeg', 1),
        ];
        // When
        $result = $this->sut->getUploadedFiles($attachmentsAsArray);

        // Then
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(UploadedFile::class, $result);
        $this->assertEquals('baic.png', $result[0]->getFilename());
        $this->assertEquals('/tmp/baic.png', $result[0]->getPathname());
        $this->assertEquals('image/png', $result[0]->getClientMimeType());

        $this->assertEquals('Grafika-przykładowa.jpg', $result[1]->getFilename());
        $this->assertEquals('/tmp/Grafika-przykładowa.jpg', $result[1]->getPathname());
        $this->assertEquals('image/jpeg', $result[1]->getClientMimeType());
    }
}