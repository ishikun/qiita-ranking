<?php
require_once('../trait/singleton.php');
require_once('../trait/orm.php');
require_once('../trait/utility.php');
require_once('../config/config.php');

class Qac {
	/**
	 * トレイト
	 */
	use Singleton;
	use Utility;

	/**
	 * 定数
	 */
	// クラスパス
	const QAC_CLASS_PATH = '../class';
	// ディレクトリセパレータ
	const DS = '/';

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		spl_autoload_register( array( $this, 'autoloader' ) );
	}

	/**
	 * クラスのオートロード
	 * @param string $class
	 */
	private function autoloader( $class ) {
		$file =  self::QAC_CLASS_PATH . self::DS . $this->snakize($class) . '.php';
		if ( is_readable( $file ) ) {
			require_once( $file );
		}
	}

	/**
	 * Qiitaからのデータ取得
	 */
	public function cron_exec() {
		// インスタンス
		$qac_year     = QacYear::getInstance();
		$qac_calendar = QacCalendar::getInstance();
		$qac_item     = QacItem::getInstance();

		// すべての年を取得
		$qac_years = $qac_year->all();
		foreach ( $qac_years as $current_qac_year ) {
			// 現在の年の12月のみ、スクレイピングを実行
			if ( (int) date( 'Y' ) === (int) $current_qac_year->year && (int) date('m') === 12 ) {
				$qac_calendar->createByScraping( $current_qac_year );
			}

			// 対象のテーマカレンダーを取得
			$qac_calendars = $qac_calendar->all( array( 'year_id' => $current_qac_year->id, 'is_active' => true ) );
			foreach ( $qac_calendars as $current_qac_calendar ) {
				$qac_item->createByScraping( $current_qac_calendar );
			}
		}
	}

	/**
	 * カレンダー表示
	 * @param string $slug
	 */
	public function calendar( $year, $slug ) {
		// インスタンス
		$qac_year     = QacYear::getInstance();
		$qac_calendar = QacCalendar::getInstance();
		$qac_item     = QacItem::getInstance();

		// 指定されたカレンダーを呼び出し
		$year = (int) $year;
		$current_qac_year     = $qac_year->find_by( array( 'year' => $year ) );
		$current_qac_calendar = $qac_calendar->find_by( array( 'year_id' => $current_qac_year->id, 'url' => $current_qac_year->url . self::DS . $slug ) );
		$current_qac_items    = $qac_item->all( array( 'calendar_id' => $current_qac_calendar->id ) );

		$params = array(
			'year'     => $current_qac_year,
			'calendar' => $current_qac_calendar,
		);

		if ( $current_qac_calendar === false ) {
			$body = <<<EOT
<section class="section hero is-primary">
<div class="hero-body">
<div class="container">
	<h1 class="title">Advent Calendar いいね数ランキング</h1>
</div>
</div>
</section>
<section class="section">
	<div class="container">
		<div class="notification is-danger">
			指定された Advent Calendar は存在しません。
		</div>
	</div>
</section>
EOT;
			$this->render( $body, $params );
			exit;
		}

		$body = <<<EOT
<section class="section hero is-primary">
<div class="hero-body">
<div class="container">
	<h1 class="title">{$params['calendar']->name} Advent Calendar {$params['year']->year} いいね数ランキング</h1>
	<div class="subtitle"><a href="{$params['calendar']->url}" target="_blank">{$params['calendar']->url}</a></div>
</div>
</div>
</section>

EOT;

		if ( $current_qac_calendar->is_active ) {
			if ( count( $current_qac_items ) > 0 ) {
				usort( $current_qac_items, function( $a, $b ) {
					$a_likes_count = (int) $a->likes_count;
					$b_likes_count = (int) $b->likes_count;
					if ( $a_likes_count === $b_likes_count ) {
						return 0;
					}
					return $a_likes_count > $b_likes_count ? -1 : 1;
				} );

				$rank = 1;
				$previous_likes_count = 99999999;
				$qiita_records = array();
				$other_records = array();
				foreach ( $current_qac_items as $key => $current_qac_item ) {
					$rank = ( $previous_likes_count === $current_qac_item->likes_count ) ? $rank : $key + 1;
					if ( $current_qac_item->is_qiita ) {
						$qiita_records[] = "<tr><td>{$rank}</td><td><a href=\"{$current_qac_item->url}\" target=\"_blank\">{$current_qac_item->title}</a></td><td>{$current_qac_item->date}</td><td>{$current_qac_item->author}</td><td>{$current_qac_item->likes_count}</td></tr>";
						$previous_likes_count = $current_qac_item->likes_count;
					} else {
						$other_records[] = "<tr><td><a href=\"{$current_qac_item->url}\" target=\"_blank\">{$current_qac_item->title}</a></td><td>{$current_qac_item->date}</td><td>{$current_qac_item->author}</td></tr>";
					}
				}
				$qiita_ranking_data = implode( "\n", $qiita_records );

				$body .= <<<EOT
<section class="section">
	<div class="container">
		<h2 class="title is-4">Qiitaでの投稿</h2>
		<table class="table is-striped is-hoverable is-fullwidth">
			<thead>
				<tr>
					<th>順位</th><th>タイトル</th><th>投稿日</th><th>投稿者</th><th>いいね数</th>
				</tr>
			</thead>
			<tbody>
				{$qiita_ranking_data}
			</tbody>
		</table>
	</div>
</section>
EOT;
				if ( count( $other_records ) > 0 ) {
					$other_data = implode( "\n", $other_records );
					$body .= <<<EOT
<section class="section">
	<div class="container">
		<h2 class="title is-4">Qiita以外での投稿</h2>
		<table class="table is-striped is-hoverable is-fullwidth">
			<thead>
				<tr>
					<th>タイトル</th><th>投稿日</th><th>投稿者</th>
				</tr>
			</thead>
			<tbody>
				{$other_data}
			</tbody>
		</table>
	</div>
</section>
EOT;
				}
			} else {
				$body .= <<<EOT
<section class="section">
	<div class="container">
		<div class="notification is-warning">
			データが取得されていないか、記事がないため表示できません。<br>
			なお、データの取得は毎日8時に行われます。
		</div>
	</div>
</section>

EOT;
			}
		} else {
			if ( $current_qac_calendar->update( array( 'is_active' => true ), array( 'id' => $current_qac_calendar->id ) ) ) {
				$body .= <<<EOT
<section class="section">
	<div class="container">
		<div class="notification is-success">
			データの取得を設定しました。<br>
			データの取得は毎日8時に行われるため、それまでしばらくお待ちください。
		</div>
	</div>
</section>

EOT;
			} else {
				$body .= <<<EOT
<section class="section">
	<div class="container">
		<div class="notification is-danger">
			エラーが発生しました。<br>
			しばらく時間をおいてお試しいただくか、管理者までお問い合わせください。
		</div>
	</div>
</section>

EOT;
			}
		}
		$this->render( $body, $params );
	}

	/**
	 * 表示
	 * @param string $body
	 * @param array  $params
	 */
	public function render( $body, $params = array() ) {
		echo $this->header( $params );
		echo $body;
		echo $this->footer( $params );
	}

	/**
	 * ヘッダー
	 * @param array  $params
	 */
	private function header( $params ) {
		if ( ! empty( $params['calendar']->name ) ) {
			$title = "{$params['calendar']->name} Advent Calendar {$params['year']->year} いいね数ランキング";
		} else {
			$title = "Advent Calendar いいね数ランキング";
		}
		return <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.6.1/css/bulma.min.css">
<link rel="stylesheet" media="all" href="/css/style.css">
</head>
<body>

EOT;
	}

	/**
	 * フッター
	 * @param array  $params
	 */
	private function footer( $params ) {
		return <<<EOT
</body>
</html>
EOT;
	}
}
