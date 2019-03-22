<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CachedSyncObjectRepository")
 */
class CachedSyncObject
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $transferWiseId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $smartAccountsId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Job", inversedBy="cachedSyncObjects")
     */
    private $job;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $twTransaction;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $twProfileId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $twBorderlessAccountId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransferWiseId(): ?string
    {
        return $this->transferWiseId;
    }

    public function setTransferWiseId(string $transferWiseId): self
    {
        $this->transferWiseId = $transferWiseId;

        return $this;
    }

    public function getSmartAccountsId(): ?string
    {
        return $this->smartAccountsId;
    }

    public function setSmartAccountsId(?string $smartAccountsId): self
    {
        $this->smartAccountsId = $smartAccountsId;

        return $this;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(?Job $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function __toString()
    {
        return $this->transferWiseId . ' ' . $this->smartAccountsId;
    }

    public function getTwTransaction(): ?string
    {
        return $this->twTransaction;
    }

    public function setTwTransaction(?string $twTransaction): self
    {
        $this->twTransaction = $twTransaction;

        return $this;
    }

    public function getTwProfileId(): ?string
    {
        return $this->twProfileId;
    }

    public function setTwProfileId(?string $twProfileId): self
    {
        $this->twProfileId = $twProfileId;

        return $this;
    }

    public function getTwBorderlessAccountId(): ?string
    {
        return $this->twBorderlessAccountId;
    }

    public function setTwBorderlessAccountId(?string $twBorderlessAccountId): self
    {
        $this->twBorderlessAccountId = $twBorderlessAccountId;

        return $this;
    }


}
