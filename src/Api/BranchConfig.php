<?php

namespace PhpCsStash\Api;

class BranchConfig
{
    /**
     * @var string
     */
    private $branch;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var string
     */
    private $repo;

    /**
     * @param string $branch
     * @param string $slug
     * @param string $repo
     */
    public function __construct($branch, $slug, $repo)
    {

        $this->branch = $branch;
        $this->slug = $slug;
        $this->repo = $repo;
    }

    /**
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getRepo()
    {
        return $this->repo;
    }
}