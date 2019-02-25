<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:33
 */

namespace AppBundle\Service;


use AppBundle\Entity\Reference;

class AuthorService
{

    protected $accChars = "àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ";

    public function parse($src) {
        // Does the source contain et al.
        $etAl = (strpos($src," et al.") !== false);

        $noRound = preg_replace("/\(([^()]*+|(?R))*\)/","", $src);

        $noSquare = preg_replace("/\[([^\[\]]*+|(?R))*\]/","", $noRound);

        // Are there any authors found in the desired format?
        preg_match_all("/((((([A-Z]?[-]?[A-Z" . $this->accChars . "]{1}[a-z]?\.[ ]?){1,3}) ([A-Z" . $this->accChars . "]{1}[a-z" . $this->accChars . "\- ]+)*))( [\(\[][^\)\]]+\)\]?)?)/u",$noSquare, $matches);

        if (count($matches[1]) == 0) {
            return ["authors"=>[], "text"=>$src];
        }

        $authors = $matches[1];
        $cleanedAuthors = [];

        foreach ($authors as $author) {

            // Clean up the authors name
            $author = trim($author);
            if (substr($author, 0, 4) == "and ") {
                $author = substr($author, 0, 4);
            }

            // Do not include any duplicates
            if (!in_array($author, $cleanedAuthors)) {
                $cleanedAuthors[] = trim($author);
            }
        }

        $results = [];
        foreach ($cleanedAuthors as $author) {
            // only include the author if it is the correct format.
            if (preg_match_all("/^(([A-Z]?[-]?[A-Z" . $this->accChars . "]{1}[a-z]?\.[ ]?){1,3}) ([A-Z" . $this->accChars . "]{1}[a-z" . $this->accChars . "\- ]+?)*$/u",$author, $matches) == true) {
                $initials = explode(".",$matches[1][0]);
                $parts = array_map(function($initial){
                    if (trim($initial) == "") {
                        return "";
                    }
                    return (trim($initial)[0] == '-' ? "" : " ") . trim($initial) . ".";
                }, $initials);

                $staging = trim(implode("" , array_slice($parts,0,-1))) . " " . trim($matches[3][0]);
                $results[] = $staging;
                //$results[] = trim($matches[0][0]);
            }
        }



        if (count($results) == 0) {
            return array(
                "authors" => [],
                "text" => $src,
                "etAl" => $etAl
            );
        }
        $firstAuthor = $results[0];
        // Reform author text to be only the required content.
        if (count($results) > 6 || $etAl) {
            $text = $firstAuthor . " et al.";
        } else {
            if (count($results) <= 2) {
                $text = implode(" and ", $results);
            } else {
                // oxford comma
                $text = implode(', ', array_slice($results, 0, -1)) . ', and ' . end($results);
            }
        }

        return array(
            "authors" => $results,
            "text" => $text,
            "etAl" => $etAl
        );
    }
}