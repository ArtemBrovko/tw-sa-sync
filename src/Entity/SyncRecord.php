<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SyncRecordRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SyncRecord
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $smartAccountsApiKeyPublic;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $smartAccountsApiKeyPrivate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $transferWiseApiToken;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSmartAccountsApiKeyPublic(): ?string
    {
        return $this->smartAccountsApiKeyPublic;
    }

    public function setSmartAccountsApiKeyPublic(string $smartAccountsApiKeyPublic): self
    {
        $this->smartAccountsApiKeyPublic = $smartAccountsApiKeyPublic;

        return $this;
    }

    public function getSmartAccountsApiKeyPrivate(): ?string
    {
        return $this->smartAccountsApiKeyPrivate;
    }

    public function setSmartAccountsApiKeyPrivate(string $smartAccountsApiKeyPrivate): self
    {
        $this->smartAccountsApiKeyPrivate = $smartAccountsApiKeyPrivate;

        return $this;
    }

    public function getTransferWiseApiToken(): ?string
    {
        return $this->transferWiseApiToken;
    }

    public function setTransferWiseApiToken(string $transferWiseApiToken): self
    {
        $this->transferWiseApiToken = $transferWiseApiToken;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->setCreated(new \DateTime());
    }

    /**
     * @ORM\PreUpdate()
     */
    public function updateTimestamps()
    {
        $this->setUpdated(new \DateTime());
    }
}
