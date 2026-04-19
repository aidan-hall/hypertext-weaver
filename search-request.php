<?php
class SearchRequest {
    // Sorting
    public const SORT_METHODS = ['created', 'updated'];
    public const SORT_PREFIX = 'sort:';
    public string $sort = self::SORT_METHODS[0];
    public string $sort_direction = "";

    // Raw search query
    public string $query;

    // Search fields derived from the query.
    public $include_tags = [];
    public $exclude_tags = [];
    public $post_ids = [];
    public $search_terms = [];

    public function __construct(string $query, ?string $sort, ?string $sort_descending) {
        // Sorting
        if (in_array($sort, self::SORT_METHODS)) {
            $this->sort = $sort;
        }
        if ($sort_descending) {
            $this->sort_direction = "desc";
        }

        $this->query = $query;
        $query_terms = explode(',', $query);


        foreach ($query_terms as $term) {
            $term = trim($term);
            if (str_starts_with($term, '+')) {
                array_push($this->include_tags, substr($term, 1));
            } else if (str_starts_with($term, '-')) {
                array_push($this->exclude_tags, substr($term, 1));
            } else if (str_starts_with($term, '#') and is_numeric(substr($term, 1))) {
                array_push($this->post_ids, (int) substr($term, 1));
            } else if (!empty($term)){
                array_push($this->search_terms, $term);
            }
        }        
    }

    public function requestPosts(SQLite3 &$db): ?SQLite3Result {
        $where_terms = [];
        $where_binds = [];

        foreach ($this->exclude_tags as $tag) {
            array_push($where_terms,
                       "not exists (select 1 from tags where tags.tag = :"
                       . sizeof($where_binds)
                     . " and tags.id = posts.id and tags.created = posts.created)");
            array_push($where_binds, ['type' => SQLITE3_TEXT, 'value' => $tag]);
        }

        foreach ($this->include_tags as $tag) {
            array_push($where_terms, "exists (select 1 from tags where tags.tag = :"
                                   . sizeof($where_binds)
                                   . " and tags.id = posts.id and tags.created = posts.created)");
            array_push($where_binds, ['type' => SQLITE3_TEXT, 'value' => $tag]);
        }

        if (!empty($this->post_ids)) {
            $id_terms = [];
            foreach ($this->post_ids as $id) {
                array_push($id_terms, "posts.id = :" . sizeof($where_binds));
                array_push($where_binds, ['type' => SQLITE3_INTEGER, 'value' => $id]);
            }
            array_push($where_terms, '(' . implode(' or ', $id_terms) . ')');
        }

        foreach ($this->search_terms as $term) {
            array_push($where_terms, "posts.body like :" . sizeof($where_binds));
            array_push($where_binds, ['type' => SQLITE3_TEXT, 'value' => "%$term%"]);
        }

        $where_clause = empty($where_terms) ? '' : 'where ' . implode(' and ', $where_terms);
        $sql_query = "select posts.id, body, min(created) created, max(created) updated"
                   . " from posts"
                   . " join (select id, max(created) latest from posts group by id) latest_posts"
                   . " on posts.id = latest_posts.id and posts.created = latest"
                   . " $where_clause group by posts.id order by $this->sort $this->sort_direction";
        error_log($sql_query);
        $prepared_query = $db->prepare($sql_query);
        if ($prepared_query == false) {
            return null;
        }

        foreach (range(0, $prepared_query->paramCount() - 1) as $i) {
            $prepared_query->bindValue(':' . $i, $where_binds[$i]['value'], $where_binds[$i]['type']);
        }

        error_log($prepared_query->getSQL(true));

        return $prepared_query->execute();
    }
}
?>
