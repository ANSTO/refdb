<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:23
 */

namespace AppBundle\Service;


class CsvService
{

    public function open($filename, $headers = false) {
        $contents = [];
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 8000, ",")) !== FALSE) {
                $contents[] = $data;
            }
        }
        return $contents;
    }
}