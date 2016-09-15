/*****************
  configuration 
*****************/
// place of the server side of the application
var serverIP = "http://localhost/books/";

// number of rows for textarea of language dependent fields
var ROWS_IN_TEXT = 3;

// number of columns for textarea of language dependent fields
var COLS = [28,28,10];

// names of language dependent fields
var MYFIELDS = ['author','title','shortd'];

// names of not language dependent fields
var MYNOLANGFIELDS = ['type'];

// server names of languages fields
var MYLANGFIELDS = ['langa','langt','langs'];

// languages short cuts and name to be used in options down list
var MYLANGS = ['en','ro','ru','fr','he'];
var LANGOPTIONS = ['English','Romanian','Russian','French','Hebrew'];

// options for no language dependent field
var MYKINDS = [['novel','poetry','cooking','popular','fiction',
               'stories','history','album','manual','thriller','philosophy']];

// default values
var DEFAULTLANG = 'en';
// array length is the same with MYLANGFIELDS and MYFIELDS
var DEFAULTLANGS = [];
var DEFAULTKINDS = [];
// make the first letter in language dependent fields a capital one
var CAPITALIZE_FIRST = true;
var DISPLAY_ON_BEGIN = true;

/* initialize default language for all language dependent fields */
for (var j = 0; j < MYLANGFIELDS.length; j++)
{
    DEFAULTLANGS[j] = DEFAULTLANG;
}
/* initialize default selection for all language independent fields, as the first option */
for (var j = 0; j < MYKINDS.length; j++)
{
    DEFAULTKINDS[j] = MYKINDS[j][0];
}
function initHTML()
{
    /* initialize table header */
    var tr = document.getElementById('tbl').tHead.children[0];
    var th;
    for (var i = 0; i < MYFIELDS.length; i++)
    {
        th = document.createElement('th');
        th.colSpan = '2';
        th.innerHTML = MYFIELDS[i].charAt(0).toUpperCase() + MYFIELDS[i].slice(1);
        tr.appendChild(th);
    }
    for (var i = 0; i < MYNOLANGFIELDS.length; i++)
    {
        th = document.createElement('th');
        th.innerHTML = MYNOLANGFIELDS[i].charAt(0).toUpperCase() + MYNOLANGFIELDS[i].slice(1);
        tr.appendChild(th);
    }    
    th = document.createElement('th');
    th.innerHTML ='Cmd';
    tr.appendChild(th);

    var opt;
    var t;
    /* initialize sorting options */
    tr = document.getElementById('f1');
    for (var i = 0; i < MYFIELDS.length; i++)
    {
        opt = document.createElement("option");        
        t = document.createTextNode("Sort by "+MYFIELDS[i]);       // Create a text node        
        opt.appendChild(t);
        opt.setAttribute("value",MYFIELDS[i]);
        tr.appendChild(opt);
    }

    /* initialize filter options */
    tr = document.getElementById('filter');
    for (var j = 0; j < MYNOLANGFIELDS.length; j++)
    {
        var sel = document.createElement("select");
        sel.id = "filtertype"+MYNOLANGFIELDS[j];
        opt = document.createElement("option");        
        t = document.createTextNode(MYNOLANGFIELDS[j]);               
        opt.appendChild(t);
        opt.setAttribute("value","none");
        opt.setAttribute("selected","true");
        sel.appendChild(opt);

        for (var i = 0; i < MYKINDS[j].length; i++)
        {
            opt = document.createElement("option");        
            t = document.createTextNode(MYKINDS[j][i]);              
            opt.appendChild(t);
            opt.setAttribute("value",MYKINDS[j][i]);
            sel.appendChild(opt);
        }
        $(sel).on(
            'change',
            {i:sel.id},
            function(ev){
                displayTable.settype(ev.data.i);}
            );
        tr.appendChild(sel); 
    }
    
    /* initialize search options */
    tr = document.getElementById('f2');
    for (var i = 0; i < MYFIELDS.length; i++)
    {
        opt = document.createElement("option");        
        t = document.createTextNode("Search "+MYFIELDS[i]);              
        opt.appendChild(t);
        opt.setAttribute("value",MYFIELDS[i]);
        tr.appendChild(opt);
    }
}

/*
Object dealing with the typed text - in any of the given languages 
On 'add' event sends a request to the server with the input data 
*/
function debug(text,param,paramtype)
{
    var doit = true;
    if (doit)
    {    
        if (paramtype == 'text')
            console.log(text+"="+param);
        else
           console.log("text : %o", param);
   }       
}
function createCORSRequest(method, url) 
{
  var xhr = new XMLHttpRequest();
  if ("withCredentials" in xhr) 
  {
    // Check if the XMLHttpRequest object has a "withCredentials" property.
    // "withCredentials" only exists on XMLHTTPRequest2 objects.
    xhr.open(method, url, true);
  } 
  else if (typeof XDomainRequest != "undefined") 
  {
    // Otherwise, check if XDomainRequest.
    // XDomainRequest only exists in IE, and is IE's way of making CORS requests.
    xhr = new XDomainRequest();
    xhr.open(method, url);
  } 
  else 
  {
    // Otherwise, CORS is not supported by the browser.
    xhr = null;

  }
  if (xhr != null)
  	xhr.response.addHeader("Access-Control-Allow-Origin", "*");

  return xhr;
}

var manageText = 
{
    cap         : false,
    lowletters  : [],
    capletters  : [],
    txtid       : '',
    langs       : DEFAULTLANGS,
    kind        : DEFAULTKINDS,
    // callbacks for languages
    // html representation of the characters - decimal form of the UTF-8 encodeing
    ro          : 
    [
        ['&#259','&#226','&#238','&#351','&#355'],
        ['&#258','&#194','&#206','&#350','&#354']
    ],
    ru          : 
    [
        ['&#1072','&#1073','&#1074','&#1075','&#1076','&#1077','&#1078',
        '&#1079','&#1080','&#1081','&#1082','&#1083','&#1084','&#1085',
        '&#1086','&#1087','&#1088','&#1089','&#1090','&#1091','&#1092',
        '&#1093','&#1094','&#1095','&#1096','&#1097','&#1098','&#1099',
        '&#1100','&#1101','&#1102','&#1103'],
        
        ['&#1040','&#1041','&#1042','&#1043','&#1044','&#1045','&#1046',
        '&#1047','&#1048','&#1049','&#1050','&#1051','&#1052','&#1053',
        '&#1054','&#1055','&#1056','&#1057','&#1058','&#1059','&#1060',
        '&#1061','&#1062','&#1063','&#1064','&#1065','&#1066','&#1067',
        '&#1068','&#1069','&#1070','&#10071']
    ],
    fr          : 
    [
        ['&#224','&#226','&#230','&#231','&#232','&#233','&#234','&#235','&#236',
        '&#238','&#244','&#156','&#249','&#251','&#252'],
        
        ['&#192','&#194','&#198','&#199','&#200','&#201','&#202','&#203','&#206',
        '&#207','&#212','&#140','&#217','&#219','&#220']
    ],
    he          : 
    [
        ['&#1488','&#1489','&#1490','&#1491','&#1492','&#1493','&#1494',
        '&#1495','&#1496','&#1497','&#1498','&#1499','&#1500','&#1501',
        '&#1501','&#1503','&#1504','&#1505','&#1506','&#1507','&#1508',
        '&#1509','&#1510','&#1511','&#1512','&#1513','&#1514'],
        []
    ],
    init        : function()
    {
        this.cap         = false;
        this.lowletters  = [];
        this.capletters  = [];
        this.txtid       = '';
        this.kind        = DEFAULTKINDS;
        
        // hide any of the language boxes
        for (var i = 0; i < this.langs.length; i++)
        {
            lang_hide(this.langs[i]);
            this.langs[i] = DEFAULTLANG;
        }
    },
    // is fired on 'Add'/'Do'/'Undo'
    // send thru http a request to server to add a new line
    // on response it receives the inserted line id from mySQL
    // update the browser
    // index = row index in the table
    // typ = command
    // rowid == id of the record in MySQL, used by chg command
    getText     : function(index,typ,rowid)
    {        
        lang = this.langs.slice(0);
        if (typ == 'nothing')
            return false;
        if (typ == 'Undo') 
        {
            updateLine(rowid,typ,lang,index); 
            return false;
        }
        // build string for MySQL
        var s = '';
        for (var i = 0; i < MYFIELDS.length; i++)
        {
            var val = $('#'+MYFIELDS[i]).val();
            if (CAPITALIZE_FIRST)
            {
                var c = val.charAt(0);
                if (this.langs[i] == 'en')
                    c = c.toUpperCase();
                else if (this.langs[i] == 'ro')
                     c = this.capitalize(c,this.ro[0],this.ro[1],true);
                else if (this.langs[i] == 'ru')
                    c = this.capitalize(c,this.ru[0],this.ru[1],true);
                else if (this.langs[i] == 'fr')
                    c = this.capitalize(c,this.fr[0],this.fr[1],true);
                // he does not need capitalization
                val = c + val.slice(1);
           }
           s = s + '&' + MYFIELDS[i] + '=' + val;
        }
        for (var i = 0; i < MYLANGFIELDS.length; i++)
            s = s + '&' + MYLANGFIELDS[i] + '=' + this.langs[i];
        for (var i = 0; i < MYNOLANGFIELDS.length; i++)
            s = s + "&" + MYNOLANGFIELDS[i] + '=' + this.kind[i];
        debug("s",s,'text');
        // store locally the class languages - hhtp response is executed into other context
        this.init();
        // send request to the server
        if (typ == "Add")
            url = serverIP+"books.php?action=addBook"+s;
        else
            url = serverIP+"books.php?action=chgBook&rowid="+rowid+s;
        var xmlhttp = createCORSRequest('GET', url);
		if (!xmlhttp) 
  			throw new Error('CORS not supported');
        // function to be called on http response
        xmlhttp.onreadystatechange=function() 
        {
           if (xmlhttp.readyState==4 && xmlhttp.status==200) 
           {
             debug("gettext response",xmlhttp.responseText,'text');
             // create an object with the inserted line parameters 
             updateLine(xmlhttp.responseText,typ,lang,index); 
           }
        }
        debug("getText url",url,'text'); 
        xmlhttp.send(null);
        return false; // prevent further bubbling of event
    },
    // returns the capital equivalent
    capitalize  : function(v,lowletters,capletters,force)
    {
         var decodedv = this.htmlDecode(v);
         // add the new char to the text - take care of capital, if needed 
         for (var i = 0; i < this.lowletters.length; i++)
         {    
             if (decodedv == this.htmlDecode(lowletters[i]))
             {
                 if (force)
                     v = this.htmlDecode(capletters[i]);
                 break;
             }
         }
         return v;
    },
    // take care of the special characters
    echoletter  : function(v)
    {
        if (v != ' ')
        {
            // add the new char to the text - take care of capital, if needed 
            v = this.capitalize(v,this.lowletters,this.capletters,this.cap);
        }
        // encode and add to browser
        newtext = $("#"+this.txtid).val().concat(v);
        $("#"+this.txtid).val(newtext);
    },
    htmlDecode  : function(value)
    {
        return $("<div/>").html(value).text();
    },
    // fired on 'up' arrow
    setcapital  : function()
    {
        this.cap = !this.cap;
    }
};  // manageText

/*
Object dealing with the display of the rows into the table 
Take care of the display options 
*/
var displayTable =
{
    store       : 0,
    sorting     : MYFIELDS[0],
    lang        : '',
    kind        : [],
    
    init    : function()
    {
        this.store   = 0;
        this.sorting = MYFIELDS[0];
        this.lang    = '';
        for (var i = 0; i < MYNOLANGFIELDS.length; i++)
            this.kind[MYNOLANGFIELDS[i]] = '';
    },
    // fired on click of 'store' button 
    setStore    : function()
    {
        this.store = 1;
        return false;
    },
    // fired on sort selection
    setsort     : function()
    {
        this.sorting = document.getElementById("f1").value;
        if (this.sorting == 'filter')
            document.getElementById("filter").style.display = "block";
        return false;
    },
    // fired on language filter selection
    setlang     : function()
    {
        this.lang = document.getElementById("filterlang").value;
        return false;
    },
    // fired on type filter selection
    settype     : function(i)
    {
        var j = document.getElementById(i).value;
        if (j == 'none')
            return false;
        this.kind[i.substring("filtertype".length)] = j;
        return false;
    },
    // makes a request to server to receive all the data to be displayed
    showLine    : function(start) 
    {
        // for any display, besides the first, remove the old one
        if (!start)
        {
            if (this.sorting == 'none')
                return false;
        }
        // to make the request - format the selection parameters
        sel2 = 'none';
        sel1 = 'none';
        url = '';
        if (start)
        {        
            if (DISPLAY_ON_BEGIN)
                sel1 = this.sorting;
        }
        else
        {
            if (this.sorting != 'filter') // all sorted by title or author
                sel1 = this.sorting;
            else
            {   // apply filter
                if (this.lang != '') // check the language filter
                {
                    sel1 = 'lang.'+ this.lang;
                    for (var i = 0; i < MYNOLANGFIELDS.length; i++)
                        if (this.kind[MYNOLANGFIELDS[i]] != '') // check the type filter
                        {
                            sel2 = 'type.' + MYNOLANGFIELDS[i] +"." + this.kind[MYNOLANGFIELDS[i]];
                            break;
                        }
                }
                else  // check the type filter
                {
                    for (var i = 0; i < MYNOLANGFIELDS.length; i++)
                    {
                        if (this.kind[MYNOLANGFIELDS[i]] != '') // check the type filter
                        {
                            if (sel1 == 'none')
                                sel1 = 'type.' + MYNOLANGFIELDS[i] +"." + this.kind[MYNOLANGFIELDS[i]];
                            else
                            {
                                sel2 = 'type.' + MYNOLANGFIELDS[i] +"." + this.kind[MYNOLANGFIELDS[i]];
                                break;
                            }
                        }
                    } // for
                } // else first filter not language
            } // else of filter
        } // else of start
        if (!start || (start && DISPLAY_ON_BEGIN))
        {
            debug("showLine sel1",sel1+" sel2="+sel2,'text');            
            if (sel1 != 'none') 
            {
                seld = "&sel1="+sel1+"&sel2="+sel2+"&store="+this.store;
                url = serverIP+"books.php?action=displaybooks"+seld;  
            }
            else
                alert("You need to choose !!");
        }
        else if (start && !DISPLAY_ON_BEGIN)
        {
             url = serverIP+"books.php?action=countbooks";
        }
                
        var xmlhttp = createCORSRequest('POST', url);
		if (!xmlhttp) 
  			throw new Error('CORS not supported');
        xmlhttp.onreadystatechange=function() 
        {               
	       var rownumber;
           var table = document.getElementById("tbl");
           if (xmlhttp.readyState==4 && xmlhttp.status==200) 
           {
               // on http response
               // create a table - and its header
               debug("showLine response",xmlhttp.responseText,'text');
               var len;
               if (!start)
               {
                    while (table.rows.length > 1)
                        setTimeout(onTimeoutDelete(table),2); // 5 msec
               }
               // check errors of the server application
               if (xmlhttp.responseText.substring(0,2) == "[{")
               {
                   // the response is received encoded in JSON form
                   JJ = JSON.parse(xmlhttp.responseText);
                   // JJ is the object obtained from parseing the JSON string
                   // for each record in the object
                   for (booknumber = 1; booknumber <= JJ.length; booknumber++)
                   {
                       j = JJ[booknumber-1]; // json string for a row
                       // insert a new row
                       row = table.insertRow(booknumber);
                       // put into the row the received data
                       displayLine(row,booknumber,j);
                   }
                   len = JJ.length+1;
                   rownumber = len;
               }
               else if (start && !DISPLAY_ON_BEGIN)
               {
                   len = parseInt(xmlhttp.responseText) + 1;
                   rownumber = 1;
               }
               else // on "Error" (also when the data base is empty) draw only the insertion line
               {
                   rownumber = len;
                   len = 1;
               }    
           } // http response ready
           else 
           {                  
	           rownumber = len;
               len = 1;
	       }
	       row = table.insertRow(rownumber);
           setTimeout(addLine(row,len,DEFAULTLANGS),2);
           addDoUndo(row,len,["Add",'Undo'],0);   
     
           document.getElementById("f1").options[0].selected = true;
           document.getElementById("filterlang").options[0].selected = true;
           for (var i = 0; i < MYNOLANGFIELDS.length; i++)
                document.getElementById("filtertype"+MYNOLANGFIELDS[i]).options[0].selected = true;
        } // http response function
      

        if (url != '')
        {
            xmlhttp.send(null);
        }

        // reset old options
        this.init();
        // hide the filter options
        document.getElementById("filter").style.display = "none";  
        return false; // prevent further bubbling of event
    } // showLine
}; // displayTable
    
function createCORSRequest(method, url)
{
    var xmlhttp;
    
    if (window.XMLHttpRequest) 
    {
      // code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp=new XMLHttpRequest();
    } 
    else 
    { // code for IE6, IE5
      xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    if ("withCredentials" in xmlhttp) 
    {
    	// Check if the XMLHttpRequest object has a "withCredentials" property.
    	// "withCredentials" only exists on XMLHTTPRequest2 objects.
    	xmlhttp.open(method, url, true);
    } 
    else if (typeof XDomainRequest != "undefined") 
    {
    	// Otherwise, check if XDomainRequest.
    	// XDomainRequest only exists in IE, and is IE's way of making CORS requests.
    	xmlhttp = new XDomainRequest();
    	xmlhttp.open(method, url);
  	} 
    else 
    {
    	// Otherwise, CORS is not supported by the browser.
    	xmlhttp = null;
    }

    return xmlhttp;
}
function onTimeout(number,table)
{
    // update the rows numbers into the browser
    for (var i = number; i < table.rows.length; i++) 
    {
        table.rows[i].cells[0].innerHTML = i;
    }         
}; 
function onTimeoutDelete(table)
{
    // update the rows numbers into the browser
    while (table.rows.length > 1) 
    {
        table.deleteRow(1);
    }         
}; 
function onTimeoutChg(number,table,rowid,langs)
{
     table.deleteRow(table.rows.length-1);
     var cellContent = [];
     var i;
     for (var i = 0; i < MYFIELDS.length; i++)
     {
         cellContent[i] = table.rows[number].cells[i+1].innerHTML;
     }
     for (var j = 0; j < MYNOLANGFIELDS.length; j++)
     {
         cellContent[i+j] = table.rows[number].cells[i+j+1].innerHTML;
     }
     table.deleteRow(number);
     row = table.insertRow(number);
     for (var k = 0; k < manageText.langs.length; k++)
        manageText.langs[k] = langs[k];
     setTimeout(addLine(row,number,langs),2);  
     addDoUndo(row,number,["Do",'Undo'],rowid);
     for (var k = 0; k < MYFIELDS.length; k++)
     {
         if (langs[k] == "he")
         {
            $("#"+MYFIELDS[k]).css("direction","rtl"); 
            $("#"+MYFIELDS[k]).css("textAlign","right");
         } 
         $("#"+MYFIELDS[k]).val(cellContent[k]);
         $("#"+MYLANGFIELDS[k]).val(langs[k]);
     }
     for (var k = 0; k < MYNOLANGFIELDS.length; k++,i++)
        $("#seltype"+k).val(cellContent[i]);
}; 
// number is the no into html table
// row is the id into mySQL database
function choosecmd(number,row,cmd,langs) 
{
    if (cmd == "nothing")
        return;
    if (cmd == "delBook")
    {
        var r = confirm("Are you sure ?");
        if (!r)
            return;
        url = serverIP+"books.php?action=delBook&rowid="+row;
        var xmlhttp = createCORSRequest('GET', url);
		if (!xmlhttp) 
  			throw new Error('CORS not supported');
        xmlhttp.onreadystatechange=function() 
        {
           if (xmlhttp.readyState==4 && xmlhttp.status==200) 
           { // on response change delete the row from the browser
             var table = document.getElementById("tbl");
             table.deleteRow(number); 
             setTimeout(onTimeout(number,table),5); // 5 msec
           }
        }
        xmlhttp.send(null);
    }
    else
    {
        var table = document.getElementById("tbl");
        setTimeout(onTimeoutChg(number,table,row,langs),5); // 5 msec
    }
}
// fired on selection of the book kind
function choosetype(i,kind)
{
    manageText.kind[i] = kind;
    return false; 
}
// fired on selection of the language for a field
// tlang is the selection - temporary selection
function chooselang(field,tlang) 
{   
    for (var i = 0; i < manageText.langs.length; i++)
    {
        lang_hide(manageText.langs[i]);
    }
    manageText.txtid = field;
     
    for (var i = 0; i < MYFIELDS.length; i++)
    {
        if (field == MYFIELDS[i])
        {
           manageText.langs[i] = tlang;
           break;
        }
    }
    var elem = document.getElementById(field);     
    if (tlang == "he") 
    {
        elem.setSelectionRange(elem.cols-1, elem.cols-1);
        elem.style.direction = "rtl";
    }
    else
    {
        elem.setSelectionRange(0, 0);
        elem.style.direction = "ltr";
    }
    elem.focus();        
    // set class parameter according to the chosen language
    switch (tlang)
    {
    case "en":
        manageText.lowletters = [];
        manageText.capletters = [];
        break;
    case "ro":
        manageText.lowletters = manageText.ro[0];
        manageText.capletters = manageText.ro[1];
        break;
    case "fr":
        manageText.lowletters = manageText.fr[0];
        manageText.capletters = manageText.fr[1];
        break;
    case "ru":
        manageText.lowletters = manageText.ru[0];
        manageText.capletters = manageText.ru[1];
        break;
    case "he":
        manageText.lowletters = manageText.he[0];
        manageText.capletters = manageText.he[1];
        break;
    }
    // and make visible its box
    document.getElementById(tlang).style.display = "block";
    return false; // prevent further bubbling of event
}
// fired when click on 'X' of the language box
function lang_hide(txtid) 
{
    if (document.getElementById(txtid))
        document.getElementById(txtid).style.display = "none";
    manageText.cap = false;
}
// create an option - add the text and value
function createOption(txt,val)
{
    var opt = document.createElement("option");        
    var t = document.createTextNode(txt);       // Create a text node        
    opt.appendChild(t);
    opt.setAttribute("value",val);
    
    return opt;
}
// create language selection options
function createSelection(field,lang)
{
    var sel = document.createElement("select");
    sel.id = "sel"+field;
    sel.onchange = function(){chooselang(field,sel.value);};
    var opt;
    for (var i = 0; i < LANGOPTIONS.length; i++)
    {
        opt = createOption(LANGOPTIONS[i],MYLANGS[i]);
        if (lang == MYLANGS[i])
            opt.selected = true;
        sel.appendChild(opt);
    }
    
    return sel;
}
// create book type selection options
function createTypeSelection(i)
{
    var sel = document.createElement("select");
    sel.id = "seltype"+i;
    sel.onchange = function(){choosetype(i,sel.value);};
    for (var j = 0; j < MYKINDS[i].length; j++)
    {        
        var opt = createOption(MYKINDS[i][j],MYKINDS[i][j]);
        sel.appendChild(opt);
    }
    
    return sel;
}

function addCommand(cmds,number,rowid,param,row)
{
    // add the command button - "Del/Chg"
    var sel = document.createElement("select");
    
    sel.onchange = function(){choosecmd(number,rowid,sel.value,param);};
    for (var i = 0; i < cmds.length; i++)
    {
        var opt;
        if (i == 0)
        {
            opt = createOption('None','nothing');
            sel.appendChild(opt);
        }
        opt = createOption(cmds[i][0],cmds[i][1]);
        sel.appendChild(opt);
    }
    row.insertCell(MYFIELDS.length+MYNOLANGFIELDS.length+1).appendChild(sel);
}
// rowid - MySQL index
// typ - command
// lang - list of choosen languages for each fields
// index - HTML row index in the table
function updateLine(rowid,typ,lang,index)
{
      var newline = {};
      newline[rowid] = rowid;
      for (var i = 0; i < MYFIELDS.length; i++)
          newline[MYFIELDS[i]] = $('#'+MYFIELDS[i]).val();
      for (var i = 0; i < MYLANGFIELDS.length; i++)
          newline[MYLANGFIELDS[i]] = lang[i];
      for (var i = 0; i < MYNOLANGFIELDS.length; i++)
          newline[MYNOLANGFIELDS[i]] = $('#seltype'+i).val();
      debug("updateLine",newline,'object');
      var table = document.getElementById("tbl");
      if (DISPLAY_ON_BEGIN || (typ == 'Chg'))
      {      
            // current row
            var row = table.rows[index];
            // delete all the cells of the row
            while (row.cells.length > 0)
                row.deleteCell(0);
            // insert the parameters of the new line just for display   
            displayLine(row,index,newline);
            // add a new line for insertion
            if (typ == "Add")
               newind = index;
            else
               newind  = table.rows.length-1;
            setTimeout(console.log("wait"),2);
            row = table.insertRow(newind+1);
            setTimeout(addLine(row,newind+1,DEFAULTLANGS),2); 
            addDoUndo(row,newind,["Add",'Undo'],0); 
      }
      else
      {
            // current row
            var row = table.rows[1];
            // delete all the cells of the row
            while (row.cells.length > 0)
                row.deleteCell(0);
            row = table.insertRow(1);
            setTimeout(addLine(row,index+1,DEFAULTLANGS),2); 
            addDoUndo(row,index,["Add",'Undo'],0); 
      }
}

// put a line in browser
// row - row object
// number - the line number into the table
// j - data object
function displayLine(row, number, j)
{
    row.insertCell(0).innerHTML = number;
    var cell;
    var param = [];
    for (var i = 0; i < MYFIELDS.length; i++)
    {
        var val = j[MYFIELDS[i]];
        cell = row.insertCell(i+1);
        cell.colSpan = 2; 
        if (j[MYLANGFIELDS[i]] == 'he')
        {
            $(cell).css("direction","rtl"); 
            $(cell).css("textAlign","right");
        }
        else
        {
            $(cell).css("direction","ltr"); 
            $(cell).css("textAlign","left");
            if (CAPITALIZE_FIRST)
            {
                var c = val.charAt(0);
                if (c != "")
                {
                    if (j[MYLANGFIELDS[i]] == 'en')
                        c = c.toUpperCase();
                    else if (j[MYLANGFIELDS[i]] == 'ro')
                    {
                        var ch = c.toUpperCase();
                        if (ch == c)
                          c = manageText.capitalize(c,manageText.ro[0],manageText.ro[1],true);
                        else
                          c = ch; 
                    }
                    else if (j[MYLANGFIELDS[i]] == 'ru')
                        c = manageText.capitalize(c,manageText.ru[0],manageText.ru[1],true);
                    else if (j[MYLANGFIELDS[i]] == 'fr')
                    {
                        var ch = c.toUpperCase();
                        if (ch == c)
                          c = manageText.capitalize(c,manageText.fr[0],manageText.fr[1],true);
                        else
                          c = ch; 
                    }
                    // he does not need capitalization
                    val = c + val.slice(1);
                    console.log(MYFIELDS[i]+"="+val);
                }
            }
        }
        cell.innerHTML = val;
        param[i] = j[MYLANGFIELDS[i]];
    }
    i = MYFIELDS.length+1;
    for (var k = 0; k < MYNOLANGFIELDS.length; i++,k++)
    {
        cell = row.insertCell(i);
        cell.style.textAlign = "center";
        cell.innerHTML = j[MYNOLANGFIELDS[k]];
    }
    var cmds = [['Del','delBook'],['Chg','chgBook']];
    addCommand(cmds,number,j.rowid,param,row);
}
// create input fields
function createInput(field, colw)
{
    var inp = document.createElement("textarea");
    inp.rows = ROWS_IN_TEXT;
    inp.cols = colw;
    inp.id = field;
    
    return inp;
}  
// create a new line for insertions
// row - html row object
// index - html row number
// langs - list of chosen languages
function addLine(row,index,langs)
{
    row.insertCell(0).innerHTML = index;
    for (var i = 0; i < MYFIELDS.length; i++)
    {
        var j = i*2+1;
        row.insertCell(j).appendChild(createInput(MYFIELDS[i],COLS[i]));
        row.insertCell(j+1).appendChild(createSelection(MYFIELDS[i],langs[i])); 
    }
    for (var i = 0; i < MYNOLANGFIELDS.length; i++)
        row.insertCell(MYFIELDS.length*2+1+i).appendChild(createTypeSelection(i));
}
// add the command button - "Add/Do"
// row - html row object
// index - html row number
// typ - list of commands
// rowid - MySQL index
function addDoUndo(row,index,typ,rowid)
{
    var sel = document.createElement("select");
    sel.id = "selcmd";
    sel.onchange = function(){manageText.getText(index,sel.value,rowid);};
    var opt = createOption('None','nothing');
    sel.appendChild(opt);    
    opt = createOption(typ[0],typ[0]);
    sel.appendChild(opt);
    opt = createOption(typ[1],typ[1]);
    sel.appendChild(opt);
    row.insertCell(MYFIELDS.length*2+MYNOLANGFIELDS.length+1).appendChild(sel);
}
/*
Object dealing with the search possibility
*/
var searchBook =
{
    criteria    : "",    
    lang        : DEFAULTLANG,
    setSearch   : function()
    {
        this.criteria = document.getElementById("f2").value;
        if (this.criteria == 'none')
            return false;
        document.getElementById("searchcriteria").style.display = "block";
        document.getElementById("searchtext").value = "";
        return false;
    },
    setSearchLang   : function(field)
    {
        this.lang = document.getElementById("searchlang").value;
        for (var i = 0; i < manageText.langs.length; i++)
        {
            lang_hide(manageText.langs[i]);
        }
        manageText.txtid = field;
        for (var i = 0; i < MYFIELDS.length; i++)
        {
            if (this.criteria == MYFIELDS[i])
            {
                manageText.langs[i] = this.lang;
                break;
            }
        }
        var elem = document.getElementById(field);     
        if (this.lang == "he") 
        {
            elem.setSelectionRange(elem.cols-1, elem.cols-1);
            elem.style.direction = "rtl";
        }
        else
        {
            elem.setSelectionRange(0, 0);
            elem.style.direction = "ltr";
        }
        elem.focus();        
        // set class parameter according to the chosen language
        // and make visible its box
        switch (this.tlang)
        {
        case "en":
            manageText.en();
            break;
        case "ro":
            manageText.ro();
            break;
        case "fr":
            manageText.fr();
            break;
        case "ru":
            manageText.ru();
            break;
        case "he":
            manageText.he();
            break;
        }
        document.getElementById(this.lang).style.display = "block";
        return false; 
    },
    searchBook  : function()
    {
        tosearch = document.getElementById("searchtext").value;
        url = serverIP+"books.php?action=searchBook&lang="+this.lang+"&criteria="+this.criteria+"&text="+tosearch;        
        var xmlhttp = createCORSRequest('GET', url);
		if (!xmlhttp) 
  			throw new Error('CORS not supported');
        xmlhttp.onreadystatechange=function() 
        {
           if (xmlhttp.readyState==4 && xmlhttp.status==200) 
           {
               // on http response
               // create a table - and its header
               var len;
               var table = document.getElementById("tbl");
               // check errors of the server application
               if (xmlhttp.responseText.substring(0,2) == "[{")
               {
                    while (table.rows.length > 1)
                        setTimeout(onTimeoutDelete(table),2); // 5 msec
                   // the response is received encoded in JSON form
                   JJ = JSON.parse(xmlhttp.responseText);
                   // JJ is the object obtained from parseing the JSON string
                   // for each record in the object
                   for (var booknumber = 1; booknumber <= JJ.length; booknumber++)
                   {
                       j = JJ[booknumber-1]; // json string for a row
                       // insert a new row
                       row = table.insertRow(booknumber);
                       // put into the row the received data
                       displayLine(row,booknumber,j);
                   }
                   len = JJ.length+1;
               }
               else // on "Error" (also when the data base is empty) draw only the insertion line
               {
                   len = 1;
               }
               row = table.insertRow(len);
               setTimeout(addLine(row,len,DEFAULTLANGS),2);
               addDoUndo(row,len,["Add",'Undo'],0);         
               for (var i = 0; i < manageText.langs.length; i++)
               {
                   lang_hide(manageText.langs[i]);
               }
               document.getElementById("searchcriteria").style.display = "none";
               document.getElementById("f2").options[0].selected = true;
               document.getElementById("searchlang").options[0].selected = true;
           }
        }
        xmlhttp.send(null);
        return false; 
    }
};