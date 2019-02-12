<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\JobRepository")
 */
class Job
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $started;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $finished;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $log;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CachedSyncObject", mappedBy="job")
     */
    private $cachedSyncObjects;

    public function __construct()
    {
        $this->cachedSyncObjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStarted(): ?\DateTimeInterface
    {
        return $this->started;
    }

    public function setStarted(\DateTimeInterface $started): self
    {
        $this->started = $started;

        return $this;
    }

    public function getFinished(): ?\DateTimeInterface
    {
        return $this->finished;
    }

    public function setFinished(?\DateTimeInterface $finished): self
    {
        $this->finished = $finished;

        return $this;
    }

    public function getLog(): ?string
    {
        return $this->log;
    }

    public function setLog(?string $log): self
    {
        $this->log = $log;

        return $this;
    }

    public function addToLog(?string $message): self
    {
        $this->log .= "\n" . $message;

        return $this;
    }

    /**
     * @return Collection|CachedSyncObject[]
     */
    public function getCachedSyncObjects(): Collection
    {
        return $this->cachedSyncObjects;
    }

    public function addCachedSyncObject(CachedSyncObject $cachedSyncObject): self
    {
        if (!$this->cachedSyncObjects->contains($cachedSyncObject)) {
            $this->cachedSyncObjects[] = $cachedSyncObject;
            $cachedSyncObject->setJob($this);
        }

        return $this;
    }

    public function removeCachedSyncObject(CachedSyncObject $cachedSyncObject): self
    {
        if ($this->cachedSyncObjects->contains($cachedSyncObject)) {
            $this->cachedSyncObjects->removeElement($cachedSyncObject);
            // set the owning side to null (unless already changed)
            if ($cachedSyncObject->getJob() === $this) {
                $cachedSyncObject->setJob(null);
            }
        }

        return $this;
    }
}
