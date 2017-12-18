<?php
class QacYear {
	/**
	 * トレイト
	 */
	use Singleton;
	use Orm;

	/**
	 * 変数
	 */
	// QacYearのアトリビュート
	public $id;
	public $year;
	public $url;
	public $index_path;
	public $created_at;
	public $updated_at;

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		$this->db = Database::getInstance();
	}
}
