<?php
require_once 'vendor/autoload.php';

use Sunra\PhpSimple\HtmlDomParser;

class Rss
{
    protected $mode;
    protected $rawData;
    protected $entries;



    public function __construct($mode)
    {
        $this->mode = $mode;
        $data = file_get_contents('http://www.dubossary.ru');
        $this->rawData = HtmlDomParser::str_get_html( $data );
    }


    public function filterNews()
    {
        foreach($this->rawData->find('a.news') as $element) {
            $this->entries[$element->href] = [];
            foreach ($element->children as $el) {

                if( $el->nodeName() === 'b') {
                    $this->entries[$element->href]['heading'] = $el->xmltext;
                }
                if( $el->nodeName() === 'time') {
                    $this->entries[$element->href]['date'] = $el->datetime;
                }
                if( $el->nodeName() === 'img') {
                    $this->entries[$element->href]['img'] = 'http://www.dubossary.ru/'.$el->src;
                }
            }

            $this->entries[$element->href]['url'] = 'http://www.dubossary.ru/'.$element->href;

            $prevtextStart = strpos($element->xmltext, 'class="foto">')+13;
            $this->entries[$element->href]['previewtext'] = substr($element->xmltext, $prevtextStart);

            $this->entries[$element->href]['fulltext'] = $this->getFulltext($this->entries[$element->href]['url'] );
        }
        //echo "<pre>";var_dump($this->entries);echo "</pre>";
    }

    public function getFulltext($url)
    {
        $data = file_get_contents($url);
        $dom = HtmlDomParser::str_get_html( $data );


        $fulltext = '';
        foreach ($dom->find('main p') as $paragraph) {
            if($paragraph->class !== 'cent' ) {

                $fulltext .= '<p>'.$paragraph->xmltext.'</p>';
            }
        }

        $offset = 0;
        while(strpos($fulltext, 'src="', $offset)) {
            $imgPos = strpos($fulltext, 'src="', $offset) + 5;
            $fulltext = substr($fulltext, 0, $imgPos) . 'http://www.dubossary.ru/' . substr($fulltext, $imgPos);

            $offset = $imgPos;
        }

        return $fulltext;

    }

    public function createRss()
    {
        header("Content-Type: application/rss+xml; charset=utf-8");
        $this->rss =
            '<?xml version="1.0" encoding="utf-8"?>
                <rss version="2.0">
                    <channel>
                        <title>Заря Приднестровья</title>
                        <link>https://zpmr.ru/</link>
                        <description>Заря Приднестровья. Дубоссары</description>
                        <language>ru-RU</language>';

        foreach($this->entries as $entry) {
            $description = ( $this->mode === 'fulltext' ) ? $entry['fulltext'] : $entry['previewtext'];

            $this->rss .= '    <item>
                            <title>'.$entry['heading'].'</title>
                            <description>'.$description.'</description>
                            <link>'.$entry['url'].'</link>
                            <pubDate>'.$entry['date'].'</pubDate>
                         </item>';

        }
        $this->rss .= '</channel></rss>';

    }

    public function getRss()
    {
        return $this->rss;
    }

    public function printRss()
    {
        $this->createRss();
        echo $this->getRss();
    }
}

$dubRss = new Rss($_GET['mode']);
$dubRss->filterNews();
$dubRss->printRss();
