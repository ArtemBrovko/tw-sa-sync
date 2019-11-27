<?php


namespace App\Utils;


use Symfony\Component\Console\Helper\Table;
use Symfony\Component\PropertyAccess\PropertyAccessor;

trait PrintTableTrait
{
    private function printTable(Table $table, $json)
    {
        $propertyAccessor = new PropertyAccessor();
        if (count($json)) {
            $table->setHeaders(array_keys(get_object_vars($json[0])));
            foreach ($json as $row) {
                if ($propertyAccessor->isReadable($row, 'details')) {
                    $row->details = implode(';', array_values(get_object_vars($row->details)));
                }
                $table->addRow(array_values(get_object_vars($row)));
            }
            $table->render();
        }
    }

}