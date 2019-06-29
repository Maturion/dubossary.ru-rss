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
                    $date = new DateTime($el->datetime . ' '. substr($el->xmltext, -5).':00');
                    $this->entries[$element->href]['date'] = $date->format(DateTime::RFC822);
                }
                if( $el->nodeName() === 'img') {
                    $this->entries[$element->href]['img'] = 'http://www.dubossary.ru/'.$el->src;
                }
            }

            $this->entries[$element->href]['url'] = 'http://www.dubossary.ru/'.$element->href;

            $prevtextStart = strpos($element->xmltext, 'class="foto">')+13;
            $this->entries[$element->href]['description'] = substr($element->xmltext, $prevtextStart);

            if( $this->mode === 'full' ) {
                $this->entries[$element->href]['content'] = $this->getFulltext($this->entries[$element->href]['url'] );
            }


        }
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

        $pathAddition = ( $this->mode === 'full' ) ? '?mode=full' : '';

        $this->rss =
            '<?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0"
                	xmlns:content="http://purl.org/rss/1.0/modules/content/"
                    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
                    xmlns:dc="http://purl.org/dc/elements/1.1/"
                    xmlns:atom="http://www.w3.org/2005/Atom"
                    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
                    xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	>
                    <channel>
                        <title>Заря Приднестровья</title>
                        <atom:link href="http://devlab.sega-news.de/pmr/dubossaryrss/'.$pathAddition.'" rel="self" type="application/rss+xml" />
                        <link>https://zpmr.ru/</link>
                        <description>Заря Приднестровья. Дубоссары</description>
                        <language>ru-RU</language>';

        foreach($this->entries as $entry) {
            $content = ( $this->mode === 'full' ) ? '<content:encoded><![CDATA['.$entry['content'].']]></content:encoded>' : '';

            $this->rss .= '
                        <item>
                            <title>'.$entry['heading'].'</title>
                            <description><![CDATA['.$entry['description'].']]></description>
                            <link>'.$entry['url'].'</link>
                            <pubDate>'.$entry['date'].'</pubDate>
                            '.$content.'
                         </item>
                         ';

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
