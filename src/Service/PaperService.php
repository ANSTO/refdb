<?php

namespace App\Service;

use App\Entity\Reference;

class PaperService
{
    public function check(Reference $reference) {
        $needsUpdate = false;
        if (is_null($reference->getPaperUrl())) {

            $baseUrl = $reference->getConference()->getBaseUrl();
            $paperId = $reference->getPaperId();

            if (!is_null($baseUrl) && !is_null($paperId)) {
                $url = $baseUrl . "papers/" . $paperId . ".pdf";
                $valid = $this->testUrl($url);

                if ($valid) {
                    $reference->setPaperUrl($url);
                    $needsUpdate = true;
                }
            }
        }
        return $needsUpdate;
    }

    private function testUrl($url) {
        $valid = false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER  , true);
        curl_setopt($ch, CURLOPT_NOBODY  , true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_exec($ch);
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code == 200) {
                $valid = true;
            }
        }
        curl_close($ch);

        return $valid;
    }
}
