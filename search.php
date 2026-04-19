<?php
require_once 'search-request.php';

function postTime($timestamp) {
    $date = new DateTimeImmutable('@' . $timestamp);
    $date = $date->setTimezone(new DateTimeZone('Europe/London'));
    return $date->format('d M Y, H:i');
}

$SEARCH_REQUEST = new SearchRequest($_REQUEST['q'] ?? "+homepage", $_REQUEST['sort'] ?? null, $_REQUEST['descending'] ?? null);

$db = new SQLite3("testdb.db", SQLITE3_OPEN_READONLY);
$POSTS = $SEARCH_REQUEST->requestPosts($db);
?>

<?php while ($post = $POSTS->fetchArray()): ?>
    <article hw-id="<?=$post['id']?>" style="border:solid black 1px">
        <h2><a href="/?q=<?='%23'.$post['id']?>"><?=$post['id']?></a></h2>
        <p>Created: <?=postTime($post['created'])?></p>
        <?php if ($post['created'] < $post['updated']): ?>
            <p>Updated: <?=postTime($post['updated'])?></p>
        <?php endif; ?>
        <?php
        $post_dom = Dom\HTMLDocument::createFromString($post['body']);
        $post_links = $post_dom->querySelectorAll('a');
        foreach ($post_links as $a) {
            $href = $a->getAttribute("href");
            if (!$href)
                continue;
            /* Juice up links that are "probably" relative/internal */
            if (str_starts_with($href, '?') or str_starts_with($href, '/')) {
                $a->setAttribute("hx-target", "next .linked-preview");
                $a->setAttribute("hx-swap", "innerHTML");
                $a->setAttribute("hx-get", $href);
            }
        }
        echo $post_dom->saveHtml();

        ?>
    </article>
<?php endwhile; ?>
