<?php

require_once __DIR__ . "/../lib/IXR_Library.php";
require_once __DIR__ . '/../vendor/autoload.php';

$cli = new Goutte\Client();

// Wordpressのxmlrpc.phpのURL
$client = new IXR_Client("http://wpblog.com/xmlrpc.php");

$fileName = __DIR__ . '/../data/date.db';
$oldDate = file_get_contents($fileName);
// rss先
$rss = simplexml_load_file('http://hogehoge.com/rss');
$cnt = 0;

foreach($rss->channel->item as $item) {
    $title = (string)$item->title;
    $link = (string)$item->link;
    $date = new DateTime($item->pubDate);
    $date = (string)$date->format('Y-m-d H:i:s');

// pubDateとかじゃなくdc:dateの場合は以下
//    $dc = $item->children('http://purl.org/dc/elements/1.1/');
//    $date = new DateTime($dc->date);
//    $date = (string)$date->format('Y-m-d H:i:s');

    if ($title != "" && $date > $oldDate) {

        $crawler = $cli->request('GET', $link);

// 記事を読むなどさらにリンクがあったら下記の方法でリンクをたどる
//        $urls = $crawler->filter('div.class a')->extract(array('_text', 'href'));
// 配列で取得できる
//        $link = $urls[0][1];
// 更にスクレイピング
//        $crawler = $cli->request('GET', $link);

        $body = $crawler->filter('div.post-bodycopy')->html();

        $body .= '<br /><br /><a href="' . $link . '">元記事</a>';

        $category = 'ガジェット';

        // Wordpressの情報
        $status = $client->query(
            "wp.newPost", //使うAPIを指定
            1,     // blog ID: 通常は１、
            'User', // ユーザー名
            'Password', // パスワード
            array(
                'post_author' => '',     // 投稿者ID 未設定の場合投稿者名なしになる。
                'post_status' => 'publish', // 投稿状態（draftにすると下書きにできる）
                'post_title' => $title,   // タイトル
                'post_content' => $body,      //　本文
                'terms_names' => array(
                    'category' => array($category)
                )
            )
        );
        var_dump($status);
        if ($cnt <= 0) {
            file_put_contents($fileName, $date);
        }
        $cnt++;
    }
}
