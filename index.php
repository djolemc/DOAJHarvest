<?php

include_once('vendor/autoload.php');
include 'config.php';
include 'Database.php';
include 'functions.php';


use Stichoza\GoogleTranslate\GoogleTranslate;
use \DetectLanguage\DetectLanguage;
$db = new Database(DB_HOST, DB_USER, DB_PASS, DB_NAME, DRIVER);
DetectLanguage::setApiKey("fd19f65a4ddde522b79de84398e82c3a");



$files = scan_dir();
//var_dump($files);

foreach ($files as $filename) {


//$file = file_get_contents('data/test.json');

//$file = file_get_contents('data/article_batch_1.json'); done

$file =file_get_contents("data/$filename");



    $data = json_decode($file);


    $counter = 1;
    $time_start = microtime(true);

    foreach ($data as $journal) {

        $language = '';
        $kwdLanguage = "ENG";
        $abstractLanguage = "ENG";
        $titleLanguage = "ENG";

        echo $filename;
        echo $break;
        echo "Zapis br: " . $counter;
        echo $break;

        echo "pissn: " . $pissn = getIdentifier($journal, 'pissn');
        echo $break;
        echo "eissn: " . $eissn = getIdentifier($journal, 'eissn');
        echo $break;


        if (findISSN($pissn, $eissn, $db)) {

            echo "ID:" . $id = getId($journal);
            echo $break;
            echo "Start Page:" . $start_page = getData($journal, 'start_page');
            echo $break;
            echo "End Page:" . $end_page = getData($journal, 'end_page');
            echo $break;
            echo "Abstract:" . $abstract = strip_tags(getData($journal, 'abstract'));

            if (strlen($abstract) < 5) {
                $abstract = null;
            }

            echo $break;
            echo "Title:" . $title = getData($journal, 'title');
            echo $break;
            echo "Volume: " . $volume = getJournalData($journal, 'volume');
            echo $break;
            echo "Number: " . $number = getJournalData($journal, 'number');
            echo $break;
            echo "Year: " . $year = getData($journal, 'year');
            echo $break;
            echo "Doi: " . $doi = getIdentifier($journal, 'doi');
            echo $break;
            echo "Full Text Link: " . $ftlink = getFullTextLink($journal, 'fulltext');
            echo $break;
            $keywords = getKeywords($journal);
            if (isset($keywords)) {
                foreach ($keywords as $kwd) {
                    echo "Keyword:" . $kwd;
                    echo $break;
                }
            }
            echo "Authors:";
            $authors = getAuthors($journal);
            echo $break;
            if (isset($authors)) {
                foreach ($authors as $author) {
                    echo "Ime: " . $author[0];
                    echo $break;
                    if (isset($author[1])) {
                        echo "Afilijacija: " . $author[1];
                        echo $break;
                    }

                }
            }

            echo "ArticleId:" . $articleId = createArticleId($pissn, $eissn, $year, $number, $start_page, $authors, $db);

            //Proverava da li postoji ArticleID u bazi
            $page = $start_page;
            while (checkID($articleId, $db)) {
                $articleId = createArticleId($pissn, $eissn, $year, $number, (int)$page + 1, $authors, $db);
                $page = (int)$page + 1;
                //  echo $articleId;
            }

            echo $break;

            $abstract = str_replace("{", "(", $abstract);
            $abstract = str_replace("}", ")", $abstract);


            //Provera jezika TI, AB i KW
//        if (isset($title)) {
//            try {
//                $titleLanguage = DetectLanguage::simpleDetect($title);}
//                catch (Exception $e) {}
//        }
//        if (isset($abstract) && strlen($abstract) > 5) {
//            try {
//                $text = substr($abstract, 0, 200);
//                $abstractLanguage = DetectLanguage::simpleDetect($text);
//            } catch (Exception $e) {            }
//        }
//        if (isset($keywords)) {
//            try {
//                $text = prepareKeywords($keywords);
//                $kwdLanguage = DetectLanguage::simpleDetect($text);} catch (Exception $e) {}
//        }


            echo $break;
            echo "Jezik naslova: " . $titleLanguage;
            echo $break;
            echo "Jezik abstrakta: " . $abstractLanguage;
            echo $break;
            echo "Jezik KW: " . $kwdLanguage;


            echo $break;
            echo "****************************************************************************************************************************";
            //  sleep(1);


            $podaci = [
                "DID" => $id,
                "EP" => $end_page,
                "BP" => $start_page,
                "AB" => $abstract,
                "TI" => $title,
                "DVO" => $volume,
                "DNO" => $number,
                "DYR" => $year,
                "DPI" => $pissn,
                "DEI" => $eissn,
                "DI" => $doi,
                "DFT" => $ftlink
            ];


            insertData($podaci, $authors, $keywords, $articleId, $db, $language, $titleLanguage, $abstractLanguage, $kwdLanguage);

        } else {

            echo "Nije u bazi";

            echo $break;
            echo $break;
            $counter++;
            continue;

        }
        $counter++;

    }

    $time_end = microtime(true);

    $execution_time = ($time_end - $time_start) / 60;

    $execution_time = round($execution_time, 2);

    echo $break . 'Total Execution Time: ' . $execution_time . ' Mins';

}