<?php
require_once 'search-request.php';
$query = $_REQUEST['q'] ?? "+homepage";
?>
<search>
    <input
        hx-post="/search" hx-target="next .search-results" hx-swap="innerHTML" hx-trigger="keyup changed delay:500ms, change, load"
        style="width: 100%" name="q" type="search" value="<?=$query?>" placeholder="Search"/>
</search>
