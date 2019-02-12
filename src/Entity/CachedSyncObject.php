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
     * @ORM\Column(type="bigint")
     */
    private $transferWiseId;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $smartAccountsId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Job", inversedBy="cachedSyncObjects")
     */
    private $job;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransferWiseId(): ?int
    {
        return $this->transferWiseId;
    }

    public function setTransferWiseId(int $transferWiseId): self
    {
        $this->transferWiseId = $transferWiseId;

        return $this;
    }

    public function getSmartAccountsId(): ?int
    {
        return $this->smartAccountsId;
    }

    public function setSmartAccountsId(?int $smartAccountsId): self
    {
        $this->smartAccountsId = $smartAccountsId;

        return $this;
    }

    public function getJobId(): ?int
    {
        return $this->jobId;
    }

    public function setJobId(int $jobId): self
    {
        $this->jobId = $jobId;

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
}
