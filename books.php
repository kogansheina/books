<?php header('Content-Type: text/html; application/json; Content-Encoding: "DEFLATE"; charset=utf-8;Access-Control-Allow-Origin: *;');
/*
the server side of an application which may add/chg/delete/display/search into a table with some 
text fields (possible in some language - besides english, and some fields of options
  
*/
define("DBSERVER","localhost");
define("DBPASSWORD",""); 
define("DBUSER","root");
define("MYDB","mybooks");
define("MYTABLE","books");
// names of language dependent fields
$GLOBALS['MYFIELDS'] = array('author','title','shortd');

// names of not language dependent fields
$GLOBALS['MYNOLANGFIELDS'] = array('type');

// server names of languages fields
$GLOBALS['MYLANGFIELDS'] = array('langa','langt','langs');

define("DEFAULTLANG","en");

// selection values for used languages
$GLOBALS['MYLANGUAGES'] = array('en','ro','ru','fr','he');
	
/* compare alphabetically the second field of the a row */    
function compare_title($a, $b) 
{ 
    if (($a[$GLOBALS['MYLANGFIELDS'][1]] == DEFAULTLANG) && ($b[$GLOBALS['MYLANGFIELDS'][1]] == DEFAULTLANG))
        return strnatcmp($a[$GLOBALS['MYFIELDS'][1]], $b[$GLOBALS['MYFIELDS'][1]]);
    if ($a[$GLOBALS['MYLANGFIELDS'][1]] == DEFAULTLANG)
        return 1;
    if ($b[$GLOBALS['MYLANGFIELDS'][1]] == DEFAULTLANG)
        return -1;
    
    return strcmp_from_utf8($a[$GLOBALS['MYFIELDS'][1]], $b[$GLOBALS['MYFIELDS'][1]]); 
} 
/* compare alphabetically the first field of the a row */    
function compare_author($a, $b) 
{ 
    if (($a[$GLOBALS['MYLANGFIELDS'][0]] == DEFAULTLANG) && ($b[$GLOBALS['MYLANGFIELDS'][0]] == DEFAULTLANG))
        return strnatcmp($a[$GLOBALS['MYFIELDS'][0]], $b[$GLOBALS['MYFIELDS'][0]]);
    if ($a[$GLOBALS['MYLANGFIELDS'][0]] == DEFAULTLANG)
        return 1;
    if ($b[$GLOBALS['MYLANGFIELDS'][0]] == DEFAULTLANG)
        return -1;
    
    return strcmp_from_utf8($a[$GLOBALS['MYFIELDS'][0]], $b[$GLOBALS['MYFIELDS'][0]]); 
}
function str_pad_unicode($str, $pad_len, $dir = STR_PAD_RIGHT) 
{
    $str_len = mb_strlen($str);
    $result = null;
    $repeat = $pad_len - $str_len;
    if ($dir == STR_PAD_RIGHT) 
    {
        $result = $str.str_repeat(" ", $repeat);
    } 
    else if ($dir == STR_PAD_LEFT) 
    {
        $result = str_repeat(" ", $repeat).$str;
    }
    
    return $result;
}
 
/*
    sel1 - first filter :
    - title = all books sorted by title
    - author = all books sorted by author
    - k.... = all books with type = ... sorted by author
    - l... = all books with lang = ... sorted by author
    sel2 - second filter :
    - none - does not exist
    - k.... = all books with type = ... sorted by author
    - l... = all books with lang = ... sorted by author
    store = 1 => make a file
       file name will be mybooks[X].cvs
        X - does not exist for sel1 = title or author
        X - [language][type] ex. mybooksennovel.cvs contains all books in english which are novels
          = 0 => no file
*/
function displaybooks($sel1, $sel2, $store)
{
    $conn = mysqli_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysqli_select_db($conn,MYDB);
    if (!$db_selected)
    {
       exit('Error open ' .MYDB.' => '.mysqli_error($conn));		
    } 

    $sql="SELECT * FROM ".MYTABLE.";";    
    $rs=mysqli_query($conn,$sql);
    if (!$rs) 
    {
        exit("Error in ".MYTABLE." => ".mysqli_error($conn));
    }
    $array_to_sort = array();
    while ($row=mysqli_fetch_array($rs))
    {
        if (fit($row,$sel1,$sel2))
        {
            array_push($array_to_sort,$row);
        }
    }
    // sort alphabetically by name 
    if ($sel1 == 'title')
        usort($array_to_sort, 'compare_title');
    else
        usort($array_to_sort, 'compare_author');
    $J = array();
    if ($store == 1)
    {
        // format file name
        $f = "./my".MYTABLE;
        if (($sel1 != $GLOBALS['MYLANGFIELDS'][1]) && ($sel1 != $GLOBALS['MYLANGFIELDS'][0]))
        {
            if (($sel1 != 'title') && ($sel1 != 'author')) 
            {
                $f .= "_".substr($sel1,1);
                if ($sel2 != 'none')
                    $f .= "_".substr($sel2,1);
            }
            else
            {
                $f .= "_".$sel1;
            }
        }
        $f .= ".doc";
        if (file_exists($f))
        {
            chmod($f,0666); 
            unlink($f);
        }
        $file = fopen($f, "w+");
        fwrite($file, pack("CCC",0xef,0xbb,0xbf)); // for UTF-8 support
        fwrite($file, str_pad("|",72,"-")."|\n"); // top header
        $col1 = "|".str_pad(strtoupper($GLOBALS['MYFIELDS'][0]),35," ",STR_PAD_BOTH);
        $col2 = "|".str_pad(strtoupper($GLOBALS['MYFIELDS'][1]),35," ",STR_PAD_BOTH);
        $line = $col1.$col2."|\n";
        fwrite($file, $line); 
        fwrite($file, str_pad("|",72,"-")."|\n"); // bottom header
    }
    else
        $file = null;
    // for each record - check if it corresponds to the filter criteria
    // and store it into an associative array
    // if needed, write a record to the file
    for ($i=0; $i < count($array_to_sort); $i++)
    {
        $b = $array_to_sort[$i];
        $a = array('rowid'=>$b['id'],$GLOBALS['MYFIELDS'][1]=>$b[$GLOBALS['MYFIELDS'][1]],
                   $GLOBALS['MYLANGFIELDS'][1]=>$b[$GLOBALS['MYLANGFIELDS'][1]],
                   $GLOBALS['MYFIELDS'][0]=>$b[$GLOBALS['MYFIELDS'][0]],
                   $GLOBALS['MYLANGFIELDS'][0]=>$b[$GLOBALS['MYLANGFIELDS'][0]],
                   $GLOBALS['MYFIELDS'][2]=>$b[$GLOBALS['MYFIELDS'][2]],
                   $GLOBALS['MYLANGFIELDS'][2]=>$b[$GLOBALS['MYLANGFIELDS'][2]],
                   $GLOBALS['MYNOLANGFIELDS'][0]=>$b[$GLOBALS['MYNOLANGFIELDS'][0]]);
        array_push($J,$a);
        if ($file) 
        {
            $strt = "";
            $stra = "";
            $v = $b[$GLOBALS['MYFIELDS'][0]];
            if ($b[$GLOBALS['MYLANGFIELDS'][0]] == 'en')
                $stra = "| ".str_pad($v,34)."|";
            else if ($b[$GLOBALS['MYLANGFIELDS'][0]] == 'he')
                $stra = "| ".str_pad_unicode($v,34,STR_PAD_LEFT)." |";
            else
                $stra = "| ".str_pad_unicode($v,34)."|";
            //$v = $b[$GLOBALS['MYFIELDS'][1]];
            //if ($b[$GLOBALS['MYLANGFIELDS'][1]] == 'en')
            //    $strt = "| ".str_pad($v,34)."|";
            //else if ($b[$GLOBALS['MYLANGFIELDS'][1]] == 'he')
            //    $strt = "| ".str_pad_unicode($v,34,STR_PAD_LEFT)." |";
            //else
            //    $strt = "| ".str_pad_unicode($v,34)."|";
            fwrite($file,$stra.$strt."\n");
        }
    }
    // send as response the JSON string of the array of records
    echo json_encode($J);
    if ($file) 
    {
        fwrite($file, str_pad("|",72,"-")."|\n"); // bottom table
        fclose($file); 
    }
}
// check the filters
function fit($row, $sel1, $sel2)
{
    if (($sel1 == $GLOBALS['MYFIELDS'][0]) || ($sel1 == $GLOBALS['MYFIELDS'][1]))
        return true;
    
    if ($sel2 == 'none')
    { /* only one filter */
        if (substr($sel1,0,1) == 'k') /* all of the type */
        {
            if (substr($sel1,1) == $row[$GLOBALS['MYNOLANGFIELDS'][0]])
                return true;
        }
        else /* suppose language */
        {
            if (substr($sel1,1) == $row[$GLOBALS['MYLANGFIELDS'][0]])
                return true;
        }
    }
    else
    {
        if (substr($sel1,0,1) == 'k') /* all of the type */
        {
            if ((substr($sel1,1) == $row[$GLOBALS['MYNOLANGFIELDS'][0]]) && (substr($sel2,1) == $row[$GLOBALS['MYLANGFIELDS'][0]]))
                return true;
        }
        else
        {
            if ((substr($sel2,1) == $row[$GLOBALS['MYNOLANGFIELDS'][0]]) && (substr($sel1,1) == $row[$GLOBALS['MYLANGFIELDS'][0]]))
                return true;
        }
    }
    return false;
}
// add a new record
function addBook($name, $langt, $author, $langa, $short, $langs, $type)
{
    $conn = mysqli_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysqli_select_db($conn,MYDB);
    if (!$db_selected)
    {
       mysqli_query($conn,"CREATE DATABASE IF NOT EXISTS ".MYDB.";");
       $db_selected = mysqli_select_db($conn,MYDB);
    }
    
    if (!$db_selected)
    {
        exit('Error select '.MYDB.' database: ' .mysqli_error($conn) );		
    }
    $sql = "CREATE TABLE IF NOT EXISTS ".MYTABLE." (
        id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,".$GLOBALS['MYFIELDS'][0].
        " TEXT NOT NULL,".$GLOBALS['MYLANGFIELDS'][0].
        " TINYTEXT,".$GLOBALS['MYFIELDS'][1].
        " TEXT,".$GLOBALS['MYLANGFIELDS'][1].
        " TINYTEXT,".$GLOBALS['MYFIELDS'][2].
        " TEXT,".$GLOBALS['MYLANGFIELDS'][2].
        " TINYTEXT,".$GLOBALS['MYNOLANGFIELDS'][0].
        " TINYTEXT
    )engine=myisam ;";
    if (!mysqli_query($conn,$sql))
    {
        exit('Error creating '.MYTABLE.' table: ' .mysqli_error($conn) );		
    }
    $names = array($author,$name,$short);
    $langs = array($langa,$langt,$langs);
    $strn = '';
    $strv = '';
    for ($i=0; $i < count($GLOBALS['MYFIELDS']); $i++)
    {
        $strn .= $GLOBALS['MYFIELDS'][$i].",";
        $strv .= "'".$names[$i]."',";
    }
    for ($i=0; $i < count($GLOBALS['MYLANGFIELDS']); $i++)
    {
        $strn .= $GLOBALS['MYLANGFIELDS'][$i].",";
        $strv .= "'".$langs[$i]."',";
    }
    $strn .= $GLOBALS['MYNOLANGFIELDS'][0];
    $strv .= "'".$type."'";
    $sql = "INSERT INTO ".MYTABLE." (".$strn.") VALUES (".$strv.");";
    $err = mysqli_query($conn,$sql);
    if (!$err) 
    {
        exit("Error Insert MySQL : ".mysqli_error($conn)." ".$sql."<br>");
    }
    // return as response the mySQL index of the latest inserted record
    echo mysqli_insert_id($conn);
}
// delete the record with the given id
function delBook($id)
{
    $conn = mysqli_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysqli_select_db($conn,MYDB);
    if (!$db_selected)
    {
       exit('Error open '.MYDB.' : ' .mysqli_error($conn));		
    } 
    if (!mysqli_query($conn,"DELETE FROM ".MYTABLE." WHERE id=$id;"))
       exit("Error Delete MySQL : ".mysqli_error($conn)." ".$id."<br>");
}
// change the record with the given id - set new values
function chgBook($id,$name, $langt, $author, $langa, $short, $langs, $type)
{
    $conn = mysqli_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysqli_select_db($conn,MYDB);
    if (!$db_selected)
    {
       exit('Error open '.MYDB.' : ' .mysqli_error($conn));		
    }
    $names = array($author,$name,$short);
    $langs = array($langa,$langt,$langs);
    $str = '';
    for ($i=0; $i < count($GLOBALS['MYFIELDS']); $i++)
    {
        $str .= $GLOBALS['MYFIELDS'][$i]."='".$names[$i]."',";
    }
    for ($i=0; $i < count($GLOBALS['MYLANGFIELDS']); $i++)
    {
        $str .= $GLOBALS['MYLANGFIELDS'][$i]."='".$langs[$i]."',";
    }
    $str .= $GLOBALS['MYNOLANGFIELDS'][0]."='".$type."'";
    if (!mysqli_query($conn,"UPDATE ".MYTABLE." SET ".$str." WHERE id=$id;"))
       exit("Error ".$str."<br>");
    
    echo $id;
}

// return an int as the value of the char and the next index in the string's array
function char_translate($u1)
{    
    $conv = array(0,0xffff,0,0xffff);   
    //echo "len=".mb_strlen($u1)." , ".bin2hex($u1)." , ".mb_decode_numericentity($u1,$conv)."<br>";
    switch (mb_strlen($u1))
    {
        case 1:
            $mask = 0x7f;
            break;
        case 2:
            $mask = 0x1f3f; /* U+80 - U+7ff : 110x-xxxx-10xx-xxxx */
            break;
        case 3:
            $mask = 0x0f3f3f; /* U+800 - U+ffff : 1110-xxxx-10xx-xxxx-10xx-xxxx */
            break;
        case 4:
            $mask = 0x073f3f3f; /* U+10000 - U+1fffff : 1111-0xxx-10xx-xxxx-10xx-xxxx-10xx-xxxx */
            break;
    }
    $a = intval(bin2hex($u1),16);
    $n = $a & $mask;
    $val = intval($n);            
    //var_dump($a);
    //var_dump($mask);
    //var_dump($n);
    return $val;
}
function utf8StringSplit($string) 
{
    $nums = array();
    $strlen = mb_strlen($string, 'UTF-8');
    for ($i = 0; $i < $strlen; $i++) 
    {
        array_push($nums, mb_substr($string, $i, 1, 'UTF-8'));
    }
    return $nums;
}
/* 
 compare to strings encoded in UTF-8 
 returns 
        0 if str1 = str2
        1 if str1 > str2
       -1 if str1 < str2 
*/
function strcmp_from_utf8($str1, $str2)
{
    $array_of_string1 = utf8StringSplit($str1);  
    $array_of_string2 = utf8StringSplit($str2);
    $i = 0; // index into str1
    $j = 0; // index into str2
    // for each char in str1
    while ($i < count($array_of_string1))
    {
        $val1 = char_translate($array_of_string1[$i]);
        $i ++;
        if ($val1 == 0) // error
            return 0;
        // if there are still chars in str2
        if ($j < count($array_of_string2)) 
        { 
            $val2 = char_translate($array_of_string2[$j]);
            $j ++;
            if ($val2 == 0) // error
                return 0;
            if ($val1 < $val2)
                return -1;
            else if ($val1 > $val2)
                return 1;  
        }
    }
    if ($j < count($array_of_string2)) // str2 longer that str1
        return -1;
    return 0; // equal
}
/*
look for the requested row - fullfill the criteria(in which field to look), 
text - what to look for, lang - which is the languages of the field 
*/
function searchBook($criteria,$text,$lang)
{
    $conn = mysqli_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysqli_select_db($conn,MYDB);
    if (!$db_selected)
    {
       exit('Error open '.MYDB.' : ' .mysqli_error($conn));		
    } 
    $sql="SELECT * FROM ".MYTABLE.";";    
    $rs=mysqli_query($conn,$sql);
    if (!$rs) 
    {
        exit("Error in ".MYTABLE." ".mysqli_error($conn));
    }
    $tocompare = '';
    $J = array();
    while ($b=mysqli_fetch_array($rs))
    {
        if (($criteria == $GLOBALS['MYFIELDS'][1]) && ($lang == $b[$GLOBALS['MYLANGFIELDS'][1]]))
            $tocompare = $b[$GLOBALS['MYFIELDS'][1]];
        else if (($criteria == $GLOBALS['MYFIELDS'][0]) && ($lang == $b[$GLOBALS['MYLANGFIELDS'][0]]))
            $tocompare = $b[$GLOBALS['MYFIELDS'][0]];
        else if (($criteria == $GLOBALS['MYFIELDS'][2]) && ($lang == $b[$GLOBALS['MYLANGFIELDS'][2]]))
            $tocompare = $b[$GLOBALS['MYFIELDS'][2]];
        else
            continue;
        if ($tocompare != '')
        {
                if (mb_strstr($tocompare,$text) == false)
                    continue;
                else
                {
                    /* found a row, format it into a json format and store it */
                    $a = array('rowid'=>$b['id'],$GLOBALS['MYFIELDS'][1]=>$b[$GLOBALS['MYFIELDS'][1]],
                               $GLOBALS['MYLANGFIELDS'][1]=>$b[$GLOBALS['MYLANGFIELDS'][1]],
                               $GLOBALS['MYFIELDS'][0]=>$b[$GLOBALS['MYFIELDS'][0]],
                               $GLOBALS['MYLANGFIELDS'][0]=>$b[$GLOBALS['MYLANGFIELDS'][0]],
                               $GLOBALS['MYFIELDS'][2]=>$b[$GLOBALS['MYFIELDS'][2]],
                               $GLOBALS['MYLANGFIELDS'][2]=>$b[$GLOBALS['MYLANGFIELDS'][2]],
                               $GLOBALS['MYNOLANGFIELDS'][0]=>$b[$GLOBALS['MYNOLANGFIELDS'][0]]);
                    array_push($J,$a);
                }
        }
    }
    // send as response the JSON string of the array of records
    echo json_encode($J);
}    
/* main entry of the server program
 check the request : addBook, delBook, chgBook,
     displaybooks which correspond to the given criteria,
     searchBook return only the lines corresponding to the criteria
*/
switch($_REQUEST['action'])
{
case 'displaybooks':
    displaybooks($_REQUEST['sel1'],$_REQUEST['sel2'],$_REQUEST['store']);
    break;
case 'addBook':
    addBook($_REQUEST[$GLOBALS['MYFIELDS'][1]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][1]],
    $_REQUEST[$GLOBALS['MYFIELDS'][0]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][0]],
    $_REQUEST[$GLOBALS['MYFIELDS'][2]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][2]],
    $_REQUEST[$GLOBALS['MYNOLANGFIELDS'][0]]);
    break;
case 'delBook':
    delBook($_REQUEST['rowid']);
    break;
case 'chgBook':
    chgBook($_REQUEST['rowid'],
    $_REQUEST[$GLOBALS['MYFIELDS'][1]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][1]],
    $_REQUEST[$GLOBALS['MYFIELDS'][1]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][0]],
    $_REQUEST[$GLOBALS['MYFIELDS'][2]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][2]],
    $_REQUEST[$GLOBALS['MYNOLANGFIELDS'][0]]); 
    break;
case 'searchBook':
    searchBook($_REQUEST['criteria'],$_REQUEST['text'],$_REQUEST['lang']);    
    break;
default:
    echo "Error : Wrong action : ".$_REQUEST['action']."<br>";
    break;
}
?>
