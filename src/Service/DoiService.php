<?php

namespace App\Service;

use App\Entity\Reference;

class DoiService
{
    public function check(Reference $reference) {
        $valid = false;
        if ($reference->doi() !== false) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $reference->doi());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            if (!curl_errno($ch)) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http_code == 302) {
                    $valid = true;
                }
            }
            curl_close($ch);
        }
        return $valid;
    }
}
