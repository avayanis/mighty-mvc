<!DOCTYPE html> 
<html lang="en"> 
    <head>
        <title>Server Error</title>
    </head>
    <body>
        <h1><?php echo $this->error->getMessage();?></h1>
        <pre><?php echo $this->error->getTraceAsString();?></pre>
    </body>
</html>