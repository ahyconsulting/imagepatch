<?php
/**
 *  Patch to render images from non cached directories
 *  Harpreet Singh
 */

namespace Magento\Catalog\Model\View\Asset;

use Magento\Catalog\Model\Product\Media\ConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\View\Asset\ContextInterface;
use Magento\Framework\View\Asset\LocalInterface;

class Image implements LocalInterface
{
    /**
     * Image type of image (thumbnail,small_image,image,swatch_image,swatch_thumb)
     *
     * @var string
     */
    private $sourceContentType;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $contentType = 'image';

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * Misc image params depend on size, transparency, quality, watermark etc.
     *
     * @var array
     */
    private $miscParams;

    /**
     * @var ConfigInterface
     */
    private $mediaConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Image constructor.
     *
     * @param ConfigInterface $mediaConfig
     * @param ContextInterface $context
     * @param EncryptorInterface $encryptor
     * @param string $filePath
     * @param array $miscParams
     */
    public function __construct(
        ConfigInterface $mediaConfig,
        ContextInterface $context,
        EncryptorInterface $encryptor,
        $filePath,
        array $miscParams
    ) {
        if (isset($miscParams['image_type'])) {
            $this->sourceContentType = $miscParams['image_type'];
            unset($miscParams['image_type']);
        } else {
            $this->sourceContentType = $this->contentType;
        }
        $this->mediaConfig = $mediaConfig;
        $this->context = $context;
        $this->filePath = $filePath;
        $this->miscParams = $miscParams;
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        $url = $this->context->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getImageInfo();
        $imageFile = $this->context->getPath() . DIRECTORY_SEPARATOR . $this->getImageInfo();
        if (!file_exists($imageFile)) {
            $url = $this->context->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getImageInfoWithoutCache();
        }
        return $url;
    }

    private function getImageInfoWithoutCache()
    {
        $path = $this->getFilePath();
        return preg_replace('|\Q' . DIRECTORY_SEPARATOR . '\E+|', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Generate path from image info
     *
     * @return string
     */
    private function getImageInfo()
    {
        $path = $this->getModule()
            . DIRECTORY_SEPARATOR . $this->getMiscPath()
            . DIRECTORY_SEPARATOR . $this->getFilePath();
        return preg_replace('|\Q' . DIRECTORY_SEPARATOR . '\E+|', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function getModule()
    {
        return 'cache';
    }

    /**
     * Retrieve part of path based on misc params
     *
     * @return string
     */
    private function getMiscPath()
    {
        return $this->encryptor->hash(
            implode('_', $this->convertToReadableFormat($this->miscParams)),
            Encryptor::HASH_VERSION_MD5
        );
    }

    /**
     * Converting bool into a string representation
     * @param $miscParams
     * @return array
     */
    private function convertToReadableFormat($miscParams)
    {
        $miscParams['image_height'] = 'h:' . ($miscParams['image_height'] ?? 'empty');
        $miscParams['image_width'] = 'w:' . ($miscParams['image_width'] ?? 'empty');
        $miscParams['quality'] = 'q:' . ($miscParams['quality'] ?? 'empty');
        $miscParams['angle'] = 'r:' . ($miscParams['angle'] ?? 'empty');
        $miscParams['keep_aspect_ratio'] = (isset($miscParams['keep_aspect_ratio']) ? '' : 'non') . 'proportional';
        $miscParams['keep_frame'] = (isset($miscParams['keep_frame']) ? '' : 'no') . 'frame';
        $miscParams['keep_transparency'] = (isset($miscParams['keep_transparency']) ? '' : 'no') . 'transparency';
        $miscParams['constrain_only'] = (isset($miscParams['constrain_only']) ? 'do' : 'not') . 'constrainonly';
        $miscParams['background'] = isset($miscParams['background'])
            ? 'rgb' . implode(',', $miscParams['background'])
            : 'nobackground';
        return $miscParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->context->getPath() . DIRECTORY_SEPARATOR . $this->getImageInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceFile()
    {
        return $this->mediaConfig->getBaseMediaPath()
            . DIRECTORY_SEPARATOR . ltrim($this->getFilePath(), DIRECTORY_SEPARATOR);
    }

    /**
     * Get source content type
     *
     * @return string
     */
    public function getSourceContentType()
    {
        return $this->sourceContentType;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @return ContextInterface
     */
    public function getContext()
    {
        return $this->context;
    }
}
