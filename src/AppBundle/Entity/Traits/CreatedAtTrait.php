<?php
/**
 * Created by PhpStorm.
 * User: bycrea
 * Date: 2019-05-01
 * Time: 16:03
 */

namespace AppBundle\Entity\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait CreatedAtTrait
{
    /**
     * La création d'un Trait PHP nous permet d'implémenter plus facilement et rapidement
     * notre propriété 'createdAt' dans toutes les entités ou nous en avons besoin
     */

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * Set createdAt
     *
     * @param DateTime $createdAt
     *
     * @return mixed
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}