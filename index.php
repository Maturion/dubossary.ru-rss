<?php
require_once 'Rss.php';

$dubRss = new Rss($_GET['mode']);
$dubRss->filterNews();
$dubRss->printRss();

