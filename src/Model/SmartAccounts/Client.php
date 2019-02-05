<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Model\SmartAccounts;

use App\Model\WriteObjectTrait;

class Client
{
    use WriteObjectTrait;

    private $name;
    private $vatPc;

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
}