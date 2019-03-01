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
                foreach ($data as &$item) {
                    $encoding = mb_detect_encoding($item, 'UTF-8, ISO-8859-2');
                    if ($encoding !== "UTF-8") {
                        $item = mb_convert_encoding($item, "UTF-8", $encoding);
                    }
                }
                $contents[] = $data;
            }
        }
        return $contents;
    }
}