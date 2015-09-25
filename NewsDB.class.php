<?php
require_once "INewsDB.class.php";
class NewsDB implements INewsDB{
	const DB_NAME = "../news.db";
	const RSS_NAME = "rss.xml";
	const RSS_TITLE = "News tape";
	const RSS_LINK = "http://phpoopsite.local/news/news.php";
	private $_db = null;
	function __get($name){
		if($name == "db")
			return $this->_db;
			throw new Exception("Unknown properties!");
//обеспечить доступ для классов наследников
	}
	function __construct(){
		$this->_db = new SQLite3(self::DB_NAME);
		if(filesize(self::DB_NAME) == 0){
			try{
			$sql = "CREATE TABLE msgs(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	title TEXT,
	category INTEGER,
	description TEXT,
	source TEXT,
	datetime INTEGER
)";

if(!$this->_db->exec($sql))
	throw new Exception($this->_db->lastErrorMsg());
	
$sql = "CREATE TABLE category(
	id INTEGER,
	name TEXT
)";
if(!$this->_db->exec($sql))
	throw new Exception($this->_db->lastErrorMsg());
$sql = "INSERT INTO category(id, name)
SELECT 1 as id, 'Политика' as name
UNION SELECT 2 as id, 'Культура' as name
UNION SELECT 3 as id, 'Спорт' as name ";
if(!$this->_db->exec($sql))
	throw new Exception($this->_db->lastErrorMsg());
		}catch(Exception $e){
			// $e->getMessage();
			echo "Sorry, you try to send request without respect!";
		}
	}
}
	function __destruct(){
		unset($this->_db);
	}
	function saveNews($title, $category, $description, $source){
		$dt = time();
		$sql = "INSERT INTO msgs(title, category, description, source, datetime)
		VALUES('$title', $category, '$description', '$source', $dt)";
		$res = $this->_db->exec($sql);
		if(!$res) return false;
		$this->createRss();
		return true;
		// if(!$res) return false;
	}
	private function db2Arr($data){
		$arr = [];
		while ($row = $data->fetchArray(SQLITE3_ASSOC)) {
		$arr[] = $row;
		}
		return $arr;
	}
	function getNews(){
	$sql = "SELECT msgs.id as id, title, category.name as category,
description, source, datetime
FROM msgs, category
WHERE category.id = msgs.category
ORDER BY msgs.id DESC";
$res = $this->_db->query($sql);
	if(!$res) return false;
	return $this->db2Arr($res);
	}
	function deleteNews($id){

	}
	function clearStr($data){
		$data = strip_tags($data);
		return $this->_db->escapeString($data);
	}
	function clearInt($data){
		return abs((int)$data);
	}
private function createRss(){
	$dom = new DomDocument("1.0", "utf-8");
	$dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
//create $dom and done format
    $rss = $dom->createElement("rss");
    $dom->appendChild($rss);
//create rss
    $version = $dom->createAttribute("version");
	$version->value = '2.0';
//choose version
	$rss->appendChild($version);

	$chanel = $dom->createElement("chanel");
	$title = $dom->createElement("title", self::RSS_TITLE);
	$link = $dom->createElement("link", self::RSS_LINK);
	$chanel->appendChild($title);
	$chanel->appendChild($link);
	$chanel->appendChild($link);
	$rss->appendChild($chanel);

	$lenta = $this->getNews();

	if(!$lenta) return false;
	foreach ($lenta as $news) {
		$item = $dom->createElement("item");
		$title = $dom->createElement("title", $news["title"]);
		$category = $dom->createElement("category", $news["category"]);
		//обёртка над description
		$desc = $dom->createElement("description");
		$cdata = $dom->createCDATASection($news["description"]);
		$desc->appendChild($cdata);

		$link = $dom->createElement("link", "#");

		$dt = date("r", $news["datetime"]);
		$pubDate = $dom->createElement("pubDate", $dt);

		$item->appendChild($title);
		$item->appendChild($link);
		$item->appendChild($desc);
		$item->appendChild($pubDate);
		$item->appendChild($category);
		$chanel->appendChild($item);
	}
	$dom->save(self::RSS_NAME);
    }
}