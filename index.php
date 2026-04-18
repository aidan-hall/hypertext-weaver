<?php
require 'vendor/autoload.php';

// Determine whether to respond with a full page.
$fetch_dest = $_SERVER['HTTP_SEC_FETCH_DEST'];
$document_dest = $fetch_dest === 'document';

$pane_id = uniqid();

function postTime($timestamp) {
    $date = new DateTimeImmutable('@' . $timestamp);
    $date = $date->setTimezone(new DateTimeZone('Europe/London'));
    return $date->format('d M Y, H:i');
}

// What to sort by
const SORT_METHODS = ['created', 'updated'];
$sort = $_REQUEST['sort'] ?? null;
if (!in_array($sort, SORT_METHODS)) {
    $sort = SORT_METHODS[0];
}

// Should results be sorted descending
$sort_direction = empty($_REQUEST['descending']) ? "" : "desc";

// Default search query: homepage
$query = $_REQUEST['q'] ?? "+homepage";
$query_terms = explode(',', $query);
// print_r($query_terms); echo '<br/>';

$include_tags = [];
$exclude_tags = [];
$post_ids = [];
$search_terms = [];
foreach ($query_terms as $term) {
    $term = trim($term);
    if (str_starts_with($term, '+')) {
        array_push($include_tags, substr($term, 1));
    } else if (str_starts_with($term, '-')) {
        array_push($exclude_tags, substr($term, 1));
    } else if (str_starts_with($term, '#') and is_numeric(substr($term, 1))) {
        array_push($post_ids, (int) substr($term, 1));
    } else if (!empty($term)){
        array_push($search_terms, $term);
    }
}

// print_r($include_tags); echo '<br/>';
// print_r($exclude_tags); echo '<br/>';
// print_r($post_ids); echo '<br/>';
// print_r($search_terms); echo '<br/>';

$db = new SQLite3("testdb.db");

// Prepare query extremely securely
$where_terms = [];
$where_binds = [];

foreach ($exclude_tags as $tag) {
    array_push($where_terms,
        "not exists (select 1 from tags where tags.tag = :"
        . sizeof($where_binds)
        . " and tags.id = posts.id and tags.created = posts.created)");
    array_push($where_binds, ['type' => SQLITE3_TEXT, 'value' => $tag]);
}

foreach ($include_tags as $tag) {
    array_push($where_terms, "exists (select 1 from tags where tags.tag = :"
        . sizeof($where_binds)
        . " and tags.id = posts.id and tags.created = posts.created)");
    array_push($where_binds, ['type' => SQLITE3_TEXT, 'value' => $tag]);
}

if (!empty($post_ids)) {
    $id_terms = [];
    foreach ($post_ids as $id) {
        array_push($id_terms, "posts.id = :" . sizeof($where_binds));
        array_push($where_binds, ['type' => SQLITE3_INTEGER, 'value' => $id]);
    }
    array_push($where_terms, '(' . implode(' or ', $id_terms) . ')');
}

foreach ($search_terms as $term) {
    array_push($where_terms, "posts.body like :" . sizeof($where_binds));
    array_push($where_binds, ['type' => SQLITE3_TEXT, 'value' => "%$term%"]);
}

$where_clause = empty($where_terms) ? '' : 'where ' . implode(' and ', $where_terms);
$sql_query = "select posts.id, body, min(created) created, max(created) updated from posts join (select id, max(created) latest from posts group by id) latest_posts on posts.id = latest_posts.id and posts.created = latest $where_clause group by posts.id order by $sort $sort_direction";
$prepared_query = $db->prepare($sql_query);

// echo $sql_query . "<br/>" . $prepared_query->paramCount() . "<br/>\n";
// echo var_dump($where_binds), "<br/>\n";
foreach (range(0, $prepared_query->paramCount() - 1) as $i) {
    $prepared_query->bindValue(':' . $i, $where_binds[$i]['value'], $where_binds[$i]['type']);
}

// echo $prepared_query->getSQL(), "<br/>\n";

$posts = $prepared_query->execute();
// $posts = $db->query("select id, body, min(created) created, max(created) updated from posts group by id");
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8"/>
    <title>Document</title>
    <link href="/stylesheet.css" rel="stylesheet"/>
  </head>
  <body>
    <div id="pane-<?=$pane_id?>">
      <search>
<button onclick="document.getElementById('pane-<?=$pane_id?>')?.remove()">X</button>
        <form action="/#pane-<?=$pane_id?>" method="POST" target=htmz>
          <input style="width: 100%" name="q" type="search" value="<?=$query?>" placeholder="Search"/>
<input type=checkbox name="replace" checked hidden/>
          <label>
            Sort
            <select name="sort">
<?php foreach (SORT_METHODS as $sort_method): ?>
              <option
<?php if ($sort_method == $sort) echo 'selected'; ?>
              ><?=$sort_method?></option>
<?php endforeach; ?>
            </select>
          </label>
          <label>
            Reverse
            <input name="descending" type="checkbox"
<?php if ($sort_direction) echo 'checked'; ?>
            />
          </label>
          <button>Go</button>
        </form>
      </search>
      <div id="results-<?=$pane_id?>">
<?php while ($post = $posts->fetchArray()): ?>
        <article id="post-<?=$post['id']?>" style="border:solid black 1px">
          <h2><?=$post['id']?></h2>
            <p>Created: <?=postTime($post['created'])?></p>
<?php if ($post['created'] < $post['updated']): ?>
            <p>Updated: <?=postTime($post['updated'])?></p>
<?php endif; ?>
<?php
    $post_dom = Dom\HTMLDocument::createFromString($post['body']);
    $post_links = $post_dom->querySelectorAll('a');
    foreach ($post_links as $a) {
        $href = $a->getAttribute("href");
        $a->setAttribute("href", $a->getAttribute("href") . '#new-results');
        $a->setAttribute("target", 'htmz');
    }
    echo $post_dom->saveHtml();
    ?>
        </article>
<?php endwhile; ?>
      </div>
    </div>
<?php if (empty($_REQUEST['replace'])): ?>
    <a href="/?q=<?=$query?>#new-results" id="new-results" target=htmz>+</a>
<?php endif; ?>
<?php if ($document_dest) include 'htmz.html'; ?>
  </body>
</html>
