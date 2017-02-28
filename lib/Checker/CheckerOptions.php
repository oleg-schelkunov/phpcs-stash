<?php

namespace PhpCsStash\Checker;

class CheckerOptions
{
    /**
     * @var string
     */
    private $standard;
    /**
     * @var string
     */
    private $encoding;
    /**
     * @var string
     */
    private $installedPaths;

    /**
     * CheckerOptions constructor.
     * @param string $standard
     * @param string $encoding
     * @param string $installedPaths
     */
    public function __construct($standard, $encoding, $installedPaths)
    {
        $this->standard = $standard;
        $this->encoding = $encoding;
        $this->installedPaths = $installedPaths;
    }

    /**
     * @return string
     */
    public function getStandard()
    {
        return $this->standard;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @return string
     */
    public function getInstalledPaths()
    {
        return $this->installedPaths;
    }
}