<?php
/**
 * @author Artem Brovko <brovko.artem@gmail.com>
 * @copyright 2019 Artem Brovko
 */

namespace ArtemBro\SmartAccountsApiBundle\Model;

trait WriteObjectTrait
{
    public function getWriteObject()
    {
        return get_object_vars($this);
    }
}