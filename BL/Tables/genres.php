<?php
require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . "/DL/DAL.php";
use data\TableItem;
class genres extends TableItem {
	// fields
	public $ID;
	public $genre;
	public $status;
	public $properGenre;
	public $google;
	public $contentType;
	public $createdBy;
	public $dateCreated;
	public $isDeleted;
	
	
	
	// Counctructor
	function __construct($ID = NULL) {
		parent::__construct ();
		$this->ID = $ID;
		$this->settable ( "genres" );
		$this->refresh ( $ID );
	}
	function __set($property, $value) {
		$this->$property = $value;
	}
	function __get($property) {
		if (isset ( $this->$property )) {
			return $this->$property;
		}
	}
	function getGenres ($contentType, $languageCode) {
		// $sql = "select ID,properGenre as genre from genres  where contentType = $contentType ";
		$sql = "select genres.ID,localization.label as genre from genres inner join localization on localization.identifier = genres.genre where genres.contentType = $contentType  and localization.lang_cd = '$languageCode' order by localization.label";
		return $this->executenonquery($sql,true);
	}

	public static function getGenreID($genreText, $contentType) {
	    $intc = new self();
	    $intc->refreshProcedure("select * from genres inner join localization on localization.identifier=genres.genre and localization.lang_cd='en' and genres.contentType = $contentType and localization.label = '$genreText' limit 1;");
	    return $intc;
	}

	public static function getGenreIDtr($genreText, $contentType) {
	    $intc = new self();
	    $intc->refreshProcedure("select genres.* from genres inner join localization on localization.identifier=genres.genre and localization.lang_cd='tr' and genres.contentType = $contentType and localization.label = '$genreText' limit 1;");
	    return $intc;
	}
}
?>
