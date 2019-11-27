<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */

namespace ArtemBro\SmartAccountsApiBundle\Model;

class Client
{
    use WriteObjectTrait;

    private $name;
    private $vatPc;
    private $bankAccount;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getVatPc()
    {
        return $this->vatPc;
    }

    /**
     * @param mixed $vatPc
     */
    public function setVatPc($vatPc): void
    {
        $this->vatPc = $vatPc;
    }

    /**
     * @return mixed
     */
    public function getBankAccount()
    {
        return $this->bankAccount;
    }

    /**
     * @param mixed $bankAccount
     */
    public function setBankAccount($bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }
}