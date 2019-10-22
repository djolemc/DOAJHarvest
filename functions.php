<?php


function getId($journal)
{
    return $journal->id;
}

function getData($journal, $data)
{
    if (isset($journal->bibjson->$data)) {
        return $journal->bibjson->$data;
    } else return null;
}
function getJournalData($journal, $data)
{
    if (isset($journal->bibjson->journal->$data)) {
        return $journal->bibjson->journal->$data;
    } else return null;
}
function getIdentifier($journal, $id)
{
    if (isset($journal->bibjson->identifier)) {
        $idn = $journal->bibjson->identifier;

        foreach ($idn as $object) {

            if ($object->type == $id) {
                return $object->id;
            }
        }
    } else
        return null;

}
function getFullTextLink($journal, $id)
{
    if (isset($journal->bibjson->link)) {
        $link = $journal->bibjson->link;

        foreach ($link as $object) {

            if ($object->type == $id) {
                return $object->url;
            }
        }
    } else
        return null;

}
function getKeywords($journal)
{
    if (isset($journal->bibjson->keywords)) {
        $result = [];
        $keywords = $journal->bibjson->keywords;
        foreach ($keywords as $keyword) {
            array_push($result, $keyword);
        }

        return $result;

    } else return null;
}

function prepareKeywords ($keywords) {
    $result ='';
    foreach ($keywords as $word) {
        $result .=$word ." ";
        }
    return $result;


}


function getAuthors($journal)
{
    if (isset($journal->bibjson->author)) {
        $result = [];
        $authors = $journal->bibjson->author;
        $i = 0;
        foreach ($authors as $author) {


            $result[$i][0] = mb_convert_case($author->name, MB_CASE_TITLE, "UTF-8");
            if (isset($author->affiliation)) {
                $result[$i][1] = $author->affiliation;
            }
            $i++;
        }
        return $result;
    }
}
function createArticleId($pissn = null, $eissn = null, $year = null, $number = null, $start_page = null, $author_first, $db)
{

    $id = '';
    $issn = checkIssn($pissn, $eissn);
    $year = getYear($year);
    $number = getNumber($number);
    $page = getPage((int)$start_page);
    $author_first = getAuthorFirst($author_first);
    $id .= $issn . $year . $number . $page . $author_first;
    return $id;

}
function checkIssn($pissn, $eissn)
{
    if ($pissn === NULL && $eissn === NULL) {
        return "NULL-NULL";
    } else
        if ($pissn === null) {
            return $eissn;
        } else return $pissn;
}

function getYear($year){
    If (isset($year)) {

        $year = str_split($year);
        $short_year = '';
        foreach ($year as $item) {
            if (is_numeric($item)) {

                $short_year .= $item;
            }
        }
        return substr($short_year, 2, 2);
    }
     else return "YY";

}

function getNumber($number) {

    $re = '/(\d+)/m';
    $str = $number;
    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);

   if (isset($matches[0][0])) {
       $number = $matches[0][0];
       $number = substr($number, -2);
       $number = str_pad($number, 2, '0', STR_PAD_LEFT);
       return $number;
   } else {
       return "NN";
   }

}

//function getNumber($number)
//{
//    if (!is_numeric($number)) {
//        return "NN";
//    } else {
//        $number = substr($number, -2);
//        $number = str_pad($number, 2, '0', STR_PAD_LEFT);
//        return $number;
//    }
//}



function getPage($start_page)
{

    if (!is_numeric($start_page)) {
        return '000';
    } else {
        $page = substr($start_page, -3);
        $page = str_pad($page, 3, '0', STR_PAD_LEFT);
        return $page;
    }

}

function getAuthorFirst($author_first)
{

    $first_author = $author_first[0][0];


    if ($first_author === null || $first_author == '') {

        return "X";

    } else {

        $first_author = $first_author[0];
        if (!preg_match('/[^A-Za-z]+/', $first_author)) {
            return ucfirst($first_author);
        } else
            return "Q";
    }


}

function checkID($id, $db)
{
//    $id = $db->fetch("select * from ArticlesTempDOAJ where ArticleID='$id';");

    $db->query("SELECT * FROM ArticlesTempDOAJ where ArticleID=:id");
    $db->bind(':id',$id);
    $id=  $db->single();
    return $id;


}

function scan_dir()
{
    $ignored = array('.', '..', '.svn', '.htaccess', 'attachment', 'revision', 'test.json');
    $files = array();


    foreach (scandir('data') as $file) {

        if (in_array($file, $ignored)) continue;
        $files[$file] = filemtime('data' . '/' . $file);
    }


    $files = array_keys($files);
   natsort($files);
    return ($files) ? $files : false;
}

function insertData($podaci, $authors, $keywords, $articleId, $db, $language,$titleLanguage, $abstractLanguage, $kwdLanguage )
{

    foreach ($podaci as $key => $attr) {
        if (isset($attr) && strlen($attr) > 0) {
//            $db->insert("INSERT INTO ArticlesTempDOAJ (ArticleID, Position, AttributeID, LanguageID, GroupIndex, ContentValue) VALUES ('$articleId', '0', '$key', '','0','$attr')");

            if ($key=='TI') {
                $lang=$titleLanguage;
            } else if ($key=='AB') {
                $lang=$abstractLanguage;
            } else $lang=$language;

            if ($lang==null || strlen($lang)<2) {
              //  $lang = "ENG";
            }


            $db->query('INSERT INTO ArticlesTempDOAJ (ArticleID, Position, AttributeID, LanguageID, GroupIndex, ContentValue) VALUES (:ArticleID, :position ,:AttributeID, :LanguageID, :GroupIndex, :ContentValue)' );
            $db->bind(':ArticleID',$articleId);
            $db->bind(':position','0');
            $db->bind(':AttributeID',$key);
            $db->bind(':LanguageID', $lang);
            $db->bind(':GroupIndex','0');
            $db->bind(':ContentValue',trim($attr));

            $db->execute();

        }
    }

    $pos = 0;
    foreach ($authors as $author) {
        if (isset($author[0]) && strlen($author[0]) > 2) {
          //  $db->insert("INSERT INTO ArticlesTempDOAJ (ArticleID, Position, AttributeID, LanguageID, GroupIndex, ContentValue) VALUES ('$articleId', '$pos', 'AU', '','0','$author[0]')");

            $db->query('INSERT INTO ArticlesTempDOAJ (ArticleID, Position, AttributeID, LanguageID, GroupIndex, ContentValue) VALUES (:ArticleID, :position ,:AttributeID, :LanguageID, :GroupIndex, :ContentValue)' );
            $db->bind(':ArticleID',$articleId);
            $db->bind(':position', $pos);
            $db->bind(':AttributeID','AU');
            $db->bind(':LanguageID',$language);
            $db->bind(':GroupIndex','0');
            $db->bind(':ContentValue',trim($author[0]));

            $db->execute();
        }
        if (isset($author[1]) && $author[1] != '') {
//            $db->insert("INSERT INTO ArticlesTempDOAJ (ArticleID, Position, AttributeID, LanguageID, GroupIndex, ContentValue) VALUES ('$articleId', '$pos', 'AF', '','0','$author[1]')");
            $db->query('INSERT INTO ArticlesTempDOAJ (ArticleID, Position, AttributeID, LanguageID, GroupIndex, ContentValue) VALUES (:ArticleID, :position ,:AttributeID, :LanguageID, :GroupIndex, :ContentValue)' );
            $db->bind(':ArticleID',$articleId);
            $db->bind(':position', $pos);
            $db->bind(':AttributeID',"AF");
            $db->bind(':LanguageID',$language);
            $db->bind(':GroupIndex',"0");
            $db->bind(':ContentValue',trim($author[1]));

            $db->execute();

        }

        $pos++;
    }

    $kwdpos = 0;
    if (isset($keywords)) {
        foreach ($keywords as $keyword) {
            if (strlen($keyword)>1) {

                if ($kwdLanguage==null || strlen($kwdLanguage)<2) {
                    $kwdLanguage = "ENG";
                }

//                $db->insert("INSERT INTO ArticlesTempDOAJ (ArticleID, Position, AttributeID, LanguageID, GroupIndex, ContentValue) VALUES ('$articleId', '$kwdpos', 'KW', '','0','$keyword')");
                $db->query('INSERT INTO ArticlesTempDOAJ (ArticleID, Position, AttributeID, LanguageID, GroupIndex, ContentValue) VALUES (:ArticleID, :position ,:AttributeID, :LanguageID, :GroupIndex, :ContentValue)' );
                $db->bind(':ArticleID',$articleId);
                $db->bind(':position', $kwdpos);
                $db->bind(':AttributeID',"KW");
                $db->bind(':LanguageID', $kwdLanguage);
                $db->bind(':GroupIndex',"0");
                $db->bind(':ContentValue',trim($keyword));

                $db->execute();



                $kwdpos++;


            }
        }
    }

}

function findISSN($pissn, $eissn, $db)
{

//    $result = $db->fetch("select * from JournalsDOAJ where pISSN='$pissn' or pISSN='$eissn' or eISSN='$eissn' or eISSN='$pissn';");


        $db->query('select * from JournalsDOAJ where pISSN=:pissn or pISSN=:issn or eISSN=:eissn or eISSN=:issn1');
        $db->bind(':pissn', $pissn);
        $db->bind(':eissn', $eissn);
        $db->bind(':issn', $eissn);
        $db->bind(':issn1', $pissn);

       $result= $db->resultset();
       return $result;
}

