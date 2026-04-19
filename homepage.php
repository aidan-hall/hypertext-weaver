<?php
require_once 'search-request.php';
if (empty($POSTS)) $POSTS = null;
if (empty($CONTENT)) $CONTENT = "";
?>
<div class="search-layer">
    <div class="search-pane">
        <?php include 'search-box.php'; ?>
        <div class="search-results"></div>
    </div>
    <div class="linked-preview"></div>
</div>
