<?php
if (empty($TITLE)) $TITLE="Hypertext Weaver";
if (empty($DESC)) $DESC="";
if (empty($CONTENT)) $CONTENT="";
?>
<!doctype html>
<html>
    <head>
        <title><?= $TITLE ?></title>
        <meta name="description" content="<?= $DESC ?>"/>
        <link href="/stylesheet.css" rel="stylesheet"/>
    </head>
    <body>
        <?= $CONTENT ?>
        <script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.8/dist/htmx.min.js"></script>
    </body>
</html>
