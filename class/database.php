<?php
class Database {
	/**
	 * トレイト
	 */
	use Singleton;
	use Utility;

	/**
	 * DBハンドラ
	 */
	private $dbh;

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		$this->dbh = new PDO(DATABASE_DSN, DATABASE_USERNAME, DATABASE_PASSWORD);
	}

	/**
	 * テーブル名の取得
	 */
	private function getTableName( $class ) {
		return $this->plurize( $this->snakize( $class ) );
	}

	/**
	 * レコードの取得
	 */
	public function select( $class, $condition_params = array() ) {
		if ( ! is_array( $condition_params ) ) {
			return false;
		}

		$sql = "SELECT * FROM {$this->getTableName( $class )}";

		if ( ! empty( $condition_params ) ) {
			$conditions = array();
			foreach ( array_keys( $condition_params ) as $key ) {
				$conditions[] = "{$key} = :{$key}";
			}
			$sql .= ' WHERE ' . implode( ' AND ', $conditions );
		}

		$sth = $this->dbh->prepare( $sql );
		if ( $sth->execute( $condition_params ) ) {
			return $sth->fetchAll( PDO::FETCH_CLASS, $class );
		} else {
			return false;
		}
	}

	/**
	 * レコードの挿入
	 */
	public function insert( $class, $data_params ) {
		if ( empty( $data_params ) || ! is_array( $data_params ) ) {
			return false;
		}

		$columns = implode( ', ', array_keys( $data_params ) );
		$values  = implode( ', ', array_map( function( $key ) { return ":{$key}"; }, array_keys( $data_params ) ) );

		$sql = "INSERT INTO {$this->getTableName( $class )} ( {$columns} ) VALUES ( {$values} )";

		$sth = $this->dbh->prepare( $sql );
		if ( $sth->execute( $data_params ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * レコードの変更
	 */
	public function update( $class, $data_params, $condition_params = array() ) {
		if ( empty( $data_params ) || ! is_array( $data_params ) ) {
			return false;
		}

		if ( ! is_array( $condition_params ) ) {
			return false;
		}

		$sql = "UPDATE {$this->getTableName( $class )} SET ";

		$values = array();
		foreach ( array_keys( $data_params ) as $key ) {
			$values[] = "{$key} = :{$key}";
		}
		$sql .= implode(', ', $values);

		if ( ! empty( $condition_params ) ) {
			$conditions = array();
			foreach ( array_keys( $condition_params ) as $key ) {
				$conditions[] = "{$key} = :{$key}";
			}
			$sql .= ' WHERE ' . implode( ' AND ', $conditions );
		}

		$params = array_merge( $data_params, $condition_params );

		$sth = $this->dbh->prepare( $sql );
		if ( $sth->execute( $params ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * レコードの削除
	 */
	public function delete( $class, $condition_params = array() ) {
		if ( ! is_array( $condition_params ) ) {
			return false;
		}

		$sql = "DELETE {$this->getTableName( $class )}";

		if ( ! empty( $condition_params ) ) {
			$conditions = array();
			foreach ( array_keys( $condition_params ) as $key ) {
				$conditions[] = "{$key} = :{$key}";
			}
			$sql .= ' WHERE ' . implode( ' AND ', $conditions );
		}

		$sth = $this->dbh->prepare( $sql );
		if ( $sth->execute( $condition_params ) ) {
			return true;
		} else {
			return false;
		}
	}
}
