<?php
class QacItem {
	/**
	 * トレイト
	 */
	use Singleton;
	use Orm;

	/**
	 * 変数
	 */
	// QacItemのアトリビュート
	public $id;
	public $calendar_id;
	public $title;
	public $is_qiita;
	public $url;
	public $date;
	public $likes_count;
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
	 * @param object $qac_calendar
	 */
	public function createByScraping( $qac_calendar ) {
		$qac_year = QacYear::getInstance();
		$year = $qac_year->find( $qac_calendar->year_id )->year;
		$target_url = $qac_calendar->url . '/feed';

		$xml = file_get_contents( $target_url );
		$xml = preg_replace( '/<feed.*>/i', '<feed>', $xml );
		$dom = new DOMDocument;
		@$dom->loadXML( $xml );
		$xpath = new DOMXpath( $dom );

		$items = array();
		foreach ( $xpath->query( '//entry' ) as $node ) {
			$url   = $xpath->evaluate( 'string(link/@href)', $node );
			$title = $xpath->evaluate( 'string(title)', $node );
			if ( preg_match( '/^https:\/\/qiita.com/i', $url ) ) {
				$is_qiita = true;
			} else {
				$is_qiita = false;
			}

			if ( $is_qiita ) {
				if ( preg_match( '|https://qiita.com/.+?/items/([0-9a-f]*)|i', $url, $matches ) ) {
					$item_id = $matches[1];
					$context = stream_context_create(
						array(
							'http' => array(
								'method' => 'GET',
								'header' => 'Authorization: Bearer ' . QIITA_ACCESS_TOKEN . "\n",
							),
						)
					);
					$response = file_get_contents( "https://qiita.com/api/v2/items/{$item_id}", false, $context );
					$json_response = json_decode( $response );
					$likes_count = $json_response->likes_count;
				}
			} else {
				$likes_count = 0;
			}

			$items[] = array(
				'calendar_id' => $qac_calendar->id,
				'title'       => $title,
				'url'         => $url,
				'date'        => date('Y-m-d', strtotime( $year . '-12-' . $xpath->evaluate( 'string(rank)', $node ) ) ),
				'is_qiita'    => $is_qiita,
				'author'      => $xpath->evaluate( 'string(author/name)', $node ),
				'likes_count' => $likes_count,
			);

			sleep(1);
		}

		krsort($items);

		foreach ($items as $item) {
			$db_item = $this->find_by( array( 'calendar_id' => $item['calendar_id'], 'date' => $item['date']) );
			if ( $db_item !== false ) {
				if ( ! $this->update( $item, array( 'id' => $db_item->id ) ) ) {
					return false;
				}
			} else {
				if ( ! $this->create( $item ) ) {
					return false;
				}
			}
		}

		return true;
	}
}
