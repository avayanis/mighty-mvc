<!DOCTYPE html> 
<html lang="en"> 
    <head>
        <title>Server Error</title>
    </head>
    <body>
        <h1><?=$this->error->getMessage();?></h1>
        <pre><?=$this->error->getTraceAsString();?></pre>
    </body>
</html>