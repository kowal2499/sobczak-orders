<?php

namespace App\Entity\Authorization;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class GrantSelect extends Grant
{
    /** @ORM\Column(type="text", nullable=false) */
    private $options;

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options): void
    {
        $this->options = $options;
    }
}