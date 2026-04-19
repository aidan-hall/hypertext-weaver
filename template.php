<?php
/* http://tltech.com/info/dead-simple-templates/ */
function __template_shutdown_function_1() {
    global $TEMPLATE;
    if (empty($TEMPLATE)) $TEMPLATE="main.tpl.php";
    $CONTENT = ob_get_clean();
    /* $TEMPLATE_DIR=realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR; */
    require(/* $TEMPLATE_DIR. */$TEMPLATE);
}
register_shutdown_function('__template_shutdown_function_1');
ob_start();
?>
