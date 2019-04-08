<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:33
 */

namespace AppBundle\Service;

class AuthorService
{
    /** @var string Some common euro characters for people with hectic names */
    protected $accChars = "żàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœš";

    public function parse($src) {
        // Does the source contain et al.
        $etAl = (strpos($src," et al") !== false);

        $src = preg_replace("/ et al\.?/","", $src);

        $noRound = preg_replace("/\(([^()]*+|(?R))*\)/",",", $src);

        $noSquare = preg_replace("/\[([^\[\]]*+|(?R))*\]/",",", $noRound);

        // Are there any authors found in the desired format?
        preg_match_all("/((((([A-Z]?[-]?[A-Z" . $this->accChars . "]{1}[a-z]?\.[ ]?){1,3}) (([A-Za-z" . $this->accChars . "\- ']+)*)))( [\(\[][^\)\]]+\)\]?)?)/u",$noSquare, $matches);

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
            $cleaned = $this->cleanAuthor($author);
            if ($cleaned !== null) {
                $results[] = $cleaned;
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

    public function cleanAuthor($author) {
        $author = preg_replace("/ et al\.?/","", $author);
        if (preg_match_all("/^(([A-Z]?[-]?[A-Z" . $this->accChars . "]{1}[a-z]?\.[ ]?){1,3}) (([A-Za-z" . $this->accChars . "\- ']+?)*)$/u",$author, $matches) == true) {
            $initials = explode(".",$matches[1][0]);
            $parts = array_map(function($initial){
                if (trim($initial) == "") {
                    return "";
                }
                return (trim($initial)[0] == '-' ? "" : " ") . trim($initial) . ".";
            }, $initials);

            $name = trim(implode("" , array_slice($parts,0,-1))) . " " . trim($matches[3][0]);
            return $name;
        }
        return null;
    }
}
