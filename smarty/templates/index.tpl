<!DOCTYPE html>
<html>
 <head>
  <meta charset="utf-8">
  <title>vea {$version} - virtual email admin</title>
  <meta name="csrf-token" content="{$csrf_token}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="js/jquery-4.0.0.min.js"></script>
  <script src="js/vea.js"></script>
  <link href="vea.css" rel="stylesheet" type="text/css">
 </head>

 <body>
   <div id="mainframe">
     <h1>vea {$version} - virtual email admin</h1>
     <div id="auth">signed in as {$auth_user}</div>
     <div id="domains">
       loading...
     </div>
     <div id="content">
     </div>
   </div>
 </body>

</html>
