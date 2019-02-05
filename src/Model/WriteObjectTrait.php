<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Model;

trait WriteObjectTrait
{
    public function getWriteObject()
    {
        return get_object_vars($this);
    }
}