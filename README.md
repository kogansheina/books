# books

makes a data base of books (supported languages are : English, French, Romanian, Russian, Hebrew)
sorts by author/type/language
creates documents (tables) according to your selections

run under apache 2.4 php 5.6 mysqli 5.6.26

to make it run on windows10, after installing xampp as above, you need to make the fillowing change:
add to httpd.conf 

<IfModule mod_headers.c>
   Header set Access-Control-Allow-Origin "*"
 </IfModule>

- in xampp/htdocs 
   create a directory named books
   copy in books, books.php file
   
 - to start it click or drag to browser or write on browser:
   table.html
