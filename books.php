<?php 
header('Content-Type: text/html; application/json; Content-Encoding: "DEFLATE"; charset=utf-8');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    //header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Origin: *");
    //header('Access-Control-Allow-Credentials: true');    
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); 
}   
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers:{$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
} 
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
$GLOBALS['MYFIELDS'] = array('author','title');

// names of not language dependent fields
$GLOBALS['MYNOLANGFIELDS'] = array('type');

// server names of languages fields
$GLOBALS['MYLANGFIELDS'] = array('langa','langt');

define("DEFAULTLANG","he");

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
function compare_date($a, $b) 
{ 
    return strnatcmp($a['date'], $b['date']);
}
function str_pad_unicode($str1, $pad_len1, $str2, $pad_len2,$dir = STR_PAD_RIGHT) 
{
	$str_new1 = mb_convert_encoding($str1, "UTF-8");
    $str_len1 = mb_strlen($str_new1);
    $str_new2 = mb_convert_encoding($str2, "UTF-8");
    $str_len2 = mb_strlen($str_new2);
    $pad_str = mb_convert_encoding(' ', "UTF-8");
 
    if ($pad_len1 <= $str_len1 || $pad_len2 <= $str_len2) {
       return $str_new1.$str_new2;
    }

    $result = null;
    $repeat1 = $pad_len1 - $str_len1;
    $repeat2 = $pad_len2 - $str_len2;
    if ($dir == STR_PAD_RIGHT) 
    {
		$result = $str_new1;
        $result .= str_repeat($pad_str, $repeat1);
        $result .= "| ".$str_new2;
        $result .= str_repeat($pad_str, $repeat2);
    } 
    else 
    {
		$result = str_repeat($pad_str, $repeat1);
		$result .= $str_new2;
        $result .= str_repeat($pad_str, $repeat2);
        $result .= "| ".$str_new1;
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
    $typ1 = $sel1;
    $typ2 = $sel2;
    if ($sel2 == 'none')
    { 
		if (substr($sel1,0,5) == 'type.')
		{	
			$typ1 = substr($sel1,5,strlen($sel1) - 5);
			$sql="SELECT * FROM ".MYTABLE." where type='".$typ1."';";
		}
		else if (substr($sel1,0,5) == 'lang.')
		{
			$typ1 = substr($sel1,5,strlen($sel1) - 5);
			$sql="SELECT * FROM ".MYTABLE." where langa='".$typ1."';";
		}
		else $sql="SELECT * FROM ".MYTABLE.";"; 
	}
	else if ($sel1 == 'date') $sql="SELECT * FROM ".MYTABLE.";";
	else
	{
		$typ1 = substr($sel1,5,strlen($sel1) - 5);
		$typ2 = substr($sel2,5,strlen($sel2) - 5);
		if (substr($sel1,0,5) == 'type.')	
			$sql="SELECT * FROM ".MYTABLE." where type='".$typ1."' and langa='".$typ2."';";
		else
		    $sql="SELECT * FROM ".MYTABLE." where langa='".$typ1."' and type='".$typ2."';";		
	}
	//echo "sql=".$sql."<br>";   
    $rs=mysqli_query($conn,$sql);
    if (!$rs) 
    {
        exit("Error in ".MYTABLE." => ".mysqli_error($conn));
    }
    $array_to_sort = array();
    $array_to_fetch = array();
    while ($row=mysqli_fetch_array($rs))
    {
        if (fit($row,$sel1,$sel2))
            array_push($array_to_sort,$row);
        else
			array_push($array_to_fetch,$row);
    }
    //echo "count=".count($array_to_sort)." sel1=".$sel1."<br>";
    // sort alphabetically by name 
    if ($sel1 == 'title')
       usort($array_to_sort, 'compare_title');
    else if ($sel1 == 'author')
        usort($array_to_sort, 'compare_author');
    else  
		usort($array_to_sort, 'compare_date');
	if (count($array_to_sort) == 0)
		$array_to_sort = $array_to_fetch;
	if ($sel2 == 'down') 
		$array_to_sort = array_reverse($array_to_sort);	
    $J = array();
    if ($store == 1)
    {
        // format file name
        $f = "./my".MYTABLE;
        if (($sel1 != $GLOBALS['MYLANGFIELDS'][1]) && ($sel1 != $GLOBALS['MYLANGFIELDS'][0]))
        {
            if (($sel1 != 'title') && ($sel1 != 'author')) 
            {
                $f .= "_".$sel1;
                if ($sel2 != 'none')
                    $f .= "_".$sel2;
            }
            else
            {
                $f .= "_".$sel1;
            }
        }
        $f .= ".csv";  // sheina
        if (file_exists($f))
        {
            chmod($f,0666); 
            unlink($f);
        }
        $stra = "";
		$stra .= strtoupper($GLOBALS['MYFIELDS'][0]).",".strtoupper($GLOBALS['MYFIELDS'][1])."\n";       
        //$file = fopen($f, "w");
        /*
        fwrite($file, pack("CCC",0xef,0xbb,0xbf)); // for UTF-8 support
        fwrite($file, str_pad("|",78,"=")."|\n"); // top header
        $col1 = "|".str_pad(strtoupper($GLOBALS['MYFIELDS'][0]),38," ",STR_PAD_BOTH);
        $col2 = "|".str_pad(strtoupper($GLOBALS['MYFIELDS'][1]),38," ",STR_PAD_BOTH);
        $line = $col1.$col2."|\n";
        fwrite($file, $line); 
        fwrite($file, str_pad("|",78,"=")."|\n"); // bottom header
        */
    }
    //else
    //    $file = null;
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
                   $GLOBALS['MYNOLANGFIELDS'][0]=>$b[$GLOBALS['MYNOLANGFIELDS'][0]],
                   'date'=>$b['date']);
        array_push($J,$a);
        if ($store == 1)
        //if ($file)
         
        {
			$stra .= $b[$GLOBALS['MYFIELDS'][0]].",".$b[$GLOBALS['MYFIELDS'][1]]."\n";
			/*
            if ($b[$GLOBALS['MYLANGFIELDS'][0]] == 'he')
            {
				
				$stra = "|".str_pad_unicode($b[$GLOBALS['MYFIELDS'][0]],37,
                                            $b[$GLOBALS['MYFIELDS'][1]],37, 
                                             STR_PAD_LEFT)." |\n";   
			}
            else
            {
                $stra = "| ".str_pad_unicode($b[$GLOBALS['MYFIELDS'][0]],37,
                                             $b[$GLOBALS['MYFIELDS'][1]],37, 
                                             STR_PAD_RIGHT)."|\n";                
			}
			fwrite($file,$stra);
            fwrite($file, str_pad("|",78,"-")."|\n");
            */ 
        }
    }
    // send as response the JSON string of the array of records
    echo json_encode($J);
    if ($store == 1)
    //if ($file)
    { 
		//echo $stra;
		file_put_contents($f, $stra);
        //fclose($file);
	} 
}
// check the filters
function fit($row, $sel1, $sel2)
{
	if ($sel1 == 'date') return true;
    if (($sel1 == $GLOBALS['MYFIELDS'][0]) || ($sel1 == $GLOBALS['MYFIELDS'][1]))
        return true;    
    return false;
}
// add a new record
function addBook($name, $langt, $author, $langa, $type)
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
		id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,".$GLOBALS['MYFIELDS'][0]." TEXT NOT NULL,"
		.$GLOBALS['MYLANGFIELDS'][0]." TINYTEXT,"
		.$GLOBALS['MYFIELDS'][1]." TEXT,"
		.$GLOBALS['MYLANGFIELDS'][1]." TINYTEXT,"
		.$GLOBALS['MYNOLANGFIELDS'][0]." TINYTEXT,date date
    )engine=myisam ;";
    if (!mysqli_query($conn,$sql))
    {
        exit('Error creating '.MYTABLE.' table: ' .mysqli_error($conn) );		
    }
    $names = array($author,$name);
    $langs = array($langa,$langt);
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
    $strn .= $GLOBALS['MYNOLANGFIELDS'][0].',date';
    $strv .= "'".$type."','".date('Y-m-d')."'";
    $sql = "INSERT INTO ".MYTABLE." (".$strn.") VALUES (".$strv.");";
    $err = mysqli_query($conn,$sql);
    if (!$err) 
    {
        exit("Error Insert MySQL : ".mysqli_error($conn)." ".$sql."<br>");
    }
    // return as response the mySQL index of the latest inserted record
    echo mysqli_insert_id($conn);
    //echo $sql."<br>";
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
function chgBook($id,$name, $langt, $author, $langa, $type)
{
    $conn = mysqli_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysqli_select_db($conn,MYDB);
    if (!$db_selected)
    {
       exit('Error open '.MYDB.' : ' .mysqli_error($conn));		
    }
    $names = array($author,$name);
    $langs = array($langa,$langt);
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
    $sql="SELECT * FROM ".MYTABLE;     
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
			$tocompare = strtolower($b[$GLOBALS['MYFIELDS'][1]]);
		else if (($criteria == $GLOBALS['MYFIELDS'][0]) && ($lang == $b[$GLOBALS['MYLANGFIELDS'][0]]))
			$tocompare = strtolower($b[$GLOBALS['MYFIELDS'][0]]);
		else if ($criteria == 'date')
		{
			if ($b['date'] == $text)
			{
				/* found a row, format it into a json format and store it */
				$a = array('rowid'=>$b['id'],$GLOBALS['MYFIELDS'][1]=>$b[$GLOBALS['MYFIELDS'][1]],
							$GLOBALS['MYLANGFIELDS'][1]=>$b[$GLOBALS['MYLANGFIELDS'][1]],
							$GLOBALS['MYFIELDS'][0]=>$b[$GLOBALS['MYFIELDS'][0]],
							$GLOBALS['MYLANGFIELDS'][0]=>$b[$GLOBALS['MYLANGFIELDS'][0]],
							$GLOBALS['MYNOLANGFIELDS'][0]=>$b[$GLOBALS['MYNOLANGFIELDS'][0]],
							"date"=>$b['date']);
				array_push($J,$a);
			}
			else
				continue;
		}
		else
			continue;
		if ($tocompare != '')
		{
			if (($text[0] != '*') && ($text[strlen($text)-1] != '*'))
			{
				if (mb_strstr($tocompare,strtolower($text)) == false)
					continue;
				else
				{
					/* found a row, format it into a json format and store it */
					$a = array('rowid'=>$b['id'],$GLOBALS['MYFIELDS'][1]=>$b[$GLOBALS['MYFIELDS'][1]],
							$GLOBALS['MYLANGFIELDS'][1]=>$b[$GLOBALS['MYLANGFIELDS'][1]],
							$GLOBALS['MYFIELDS'][0]=>$b[$GLOBALS['MYFIELDS'][0]],
							$GLOBALS['MYLANGFIELDS'][0]=>$b[$GLOBALS['MYLANGFIELDS'][0]],
							$GLOBALS['MYNOLANGFIELDS'][0]=>$b[$GLOBALS['MYNOLANGFIELDS'][0]],
							"date"=>$b['date']);
					array_push($J,$a);
				}
			}
			else
			{
				if ($text[0] == '*')
					$text = substr($text,1,strlen($text)-1);
				if ($text[strlen($text)-1] == '*')
					$text = substr($text,0,strlen($text)-2);
				//echo $tocompare."=>".$text."<br>";
				if (mb_strpos($tocompare,strtolower($text)) === false)
					continue;
				else
				{
					/* found a row, format it into a json format and store it */
					$a = array('rowid'=>$b['id'],$GLOBALS['MYFIELDS'][1]=>$b[$GLOBALS['MYFIELDS'][1]],
							$GLOBALS['MYLANGFIELDS'][1]=>$b[$GLOBALS['MYLANGFIELDS'][1]],
							$GLOBALS['MYFIELDS'][0]=>$b[$GLOBALS['MYFIELDS'][0]],
							$GLOBALS['MYLANGFIELDS'][0]=>$b[$GLOBALS['MYLANGFIELDS'][0]],
							$GLOBALS['MYNOLANGFIELDS'][0]=>$b[$GLOBALS['MYNOLANGFIELDS'][0]],
							"date"=>$b['date']);
					array_push($J,$a);
				}	
			}
		}
    }
    // send as response the JSON string of the array of records
    echo json_encode($J);
} 
function backup($d)
{
    $conn = mysqli_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysqli_select_db($conn,$d);
    if (!$db_selected)
    {
       exit('Error open '.$d.' : ' .mysqli_error($conn));		
    }
	$path = "./save_".$d."_".date("Y-m-d-H-i");
	$sql="SELECT * FROM ".MYTABLE;     
	$rs=mysqli_query($conn,$sql);
	if (!$rs) 
	{
		exit("Error in ".$d." ".mysqli_error($conn));
	}

	$fp = fopen ($path,"w");
	while ($b=mysqli_fetch_array($rs))
	{
		if ($b['langa'] == "he")
			$nf = "he;";
		else
			$nf = "nh;";
		$nf .= $b['author'].";";
		$nf .= $b['langa'].";";
		$nf .= $b['title'].";";
		$nf .= $b['langt'].";";
		$nf .= $b['type'].";";
		$nf .= $b['date'].PHP_EOL;
		fwrite ($fp,$nf);
	}
	
	fclose ($fp);
	echo "done";
}
function restore($f,$d)
{
	$conn = mysqli_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
	$db_selected = mysqli_select_db($conn,$d);
	if (!$db_selected)
	{
		mysqli_query($conn,"CREATE DATABASE IF NOT EXISTS ".$d.";");
		$db_selected = mysqli_select_db($conn,$d);
	}
    else
		mysqli_query($conn,"DROP TABLE books;");
	if (!$db_selected)
	{
		exit('Error select '.$d.' database: ' .mysqli_error($conn) );		
	}
    $sql = "CREATE TABLE IF NOT EXISTS ".MYTABLE." (
		id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,".$GLOBALS['MYFIELDS'][0]." TEXT NOT NULL,"
		.$GLOBALS['MYLANGFIELDS'][0]." TINYTEXT,"
		.$GLOBALS['MYFIELDS'][1]." TEXT,"
		.$GLOBALS['MYLANGFIELDS'][1]." TINYTEXT,"
		.$GLOBALS['MYNOLANGFIELDS'][0]." TINYTEXT,date date
    )engine=myisam ;";		
	if (!mysqli_query($conn,$sql))
	{
		exit('Error creating '.MYTABLE.' table: ' .mysqli_error($conn) );		
	}
	$ff = fopen($f, "r");
	if (!$ff)
		exit("Error open file : ".$f."<br>");
	$fline = fgets($ff);
	while (!feof($ff))
	{		
		$frow = explode(";",$fline);
		
		$five = explode(PHP_EOL,$frow[6]);
		$strv = "'".$frow[1]."','".$frow[2]."','".$frow[3]."','".$frow[4]."','".$five[0]."'".$five[5]."'";
	    $sql = "INSERT INTO ".MYTABLE." (author,langa,title,langt,type,date) VALUES (".$strv.");";
		$err = mysqli_query($conn,$sql);
		if (!$err) 
		{
			exit("Error Insert MySQL : ".mysqli_error($conn)." ".$sql."<br>");
		}
		$fline = fgets($ff);
	}
	fclose($ff);
	echo "done";
}   
/* main entry of the server program
 check the request : addBook, delBook, chgBook,
     displaybooks which correspond to the given criteria,
     searchBook return only the lines corresponding to the criteria
*/
mb_internal_encoding("utf-8");
switch($_REQUEST['action'])
{
case 'displaybooks':
    displaybooks($_REQUEST['sel1'],$_REQUEST['sel2'],$_REQUEST['store']);
    break;
case 'addBook':
    addBook($_REQUEST[$GLOBALS['MYFIELDS'][1]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][1]],
    $_REQUEST[$GLOBALS['MYFIELDS'][0]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][0]],
    $_REQUEST[$GLOBALS['MYNOLANGFIELDS'][0]]);
    break;
case 'delBook':
    delBook($_REQUEST['rowid']);
    break;
case 'chgBook':
    chgBook($_REQUEST['rowid'],
    $_REQUEST[$GLOBALS['MYFIELDS'][1]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][1]],
    $_REQUEST[$GLOBALS['MYFIELDS'][0]],$_REQUEST[$GLOBALS['MYLANGFIELDS'][0]],
    $_REQUEST[$GLOBALS['MYNOLANGFIELDS'][0]]); 
    break;
case 'searchBook':
    searchBook($_REQUEST['criteria'],$_REQUEST['text'],$_REQUEST['lang']);    
    break;
case 'backup':
	backup($_REQUEST['db']);
	break;
case 'restore':
	restore($_REQUEST['file'],$_REQUEST['db']);
	break;
default:
    echo "Error : Wrong action : ".$_REQUEST['action']."<br>";
    break;
}
?>
