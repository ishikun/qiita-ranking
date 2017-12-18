<?php
class QacCalendar {
	/**
	 * トレイト
	 */
	use Singleton;
	use Orm;

	/**
	 * 変数
	 */
	// QacCalendarのアトリビュート
	public $id;
	public $year_id;
	public $name;
	public $url;
	public $created_at;
	public $updated_at;

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		$this->db = Database::getInstance();
	}

	/**
	 * スクレイピングして差分を作成する
	 * @param object $qac_year
	 */
	public function createByScraping( $qac_year ) {
		$url = $qac_year->url . $qac_year->index_path;

		// ページ数の取得
		$html = file_get_contents( $url );
		$html = preg_replace('/<meta charset="(.*)"/i', '<meta http-equiv="Content-Type" content="text/html; charset=$1"', $html);
		$dom = new DOMDocument;
		@$dom->loadHTML($html);
		$xpath = new DOMXpath($dom);
		if ( preg_match( '/\?page=(\d+)$/', $xpath->query( '//ul[@class="pagination"]/li[last()]/a/@href' )[0]->value, $matches ) ) {
			$max_page = (int) $matches[1];
		} else {
			return false;
		}

		// 全ページスクレイピング
		$calendars = array();
		for ( $i = 1; $i <= $max_page; $i++ ) {
			$target_url = $url . '?page=' . $i;
			$html = file_get_contents( $target_url );
			$html = preg_replace( '/<meta charset="(.*)"/i', '<meta http-equiv="Content-Type" content="text/html; charset=$1"', $html );
			@$dom->loadHTML( $html );
			$xpath = new DOMXpath( $dom );
			foreach ( $xpath->query( '//td/a' ) as $node ) {
				$calendars[] = array(
					'name' => $node->textContent,
					'url'  => $xpath->evaluate( 'concat("https://qiita.com", @href)', $node ),
				);
			}
			sleep(2);
		}
		krsort( $calendars );

		// データベースから全件取得
		$db_calendars = $this->all( array( 'year_id' => $qac_year->id ) );

		// 一致するのものがなければデータ作成
		foreach ( $calendars as $calendar ) {
			foreach ( $db_calendars as $db_calendar ) {
				if ( $db_calendar->name === $calendar['name'] && $db_calendar->url === $calendar['url'] ) {
					continue 2;
				}
			}
			$calendar['year_id'] = $qac_year->id;
			if ( ! $this->create( $calendar ) ) {
				return false;
			}
		}

		return true;
	}
}
