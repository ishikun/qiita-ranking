<?php
trait Orm {
	/**
	 * 変数
	 */
	// Databaseのインスタンス
	protected $db;

	/**
	 * IDによるデータの取得
	 * @param integer $id
	 */
	final public function find( $id ) {
		$input_params = array( 'id' => $id );
		return $this->db->select( __CLASS__, $input_params )[0];
	}

	/**
	 * 条件によるデータの取得
	 * @param array $conditions
	 */
	final public function find_by( $conditions ) {
		$result = $this->db->select( __CLASS__, $conditions );
		if ( count( $result ) > 0 ) {
			return $result[0];
		} else {
			return false;
		}
	}

	/**
	 * 全データの取得
	 */
	final public function all( $conditions = array() ) {
 		return $this->db->select( __CLASS__, $conditions );
	}

	/**
	 * データの作成
	 * @param array $data
	 */
	final public function create( $data ) {
		return $this->db->insert( __CLASS__, $data );
	}

	/**
	 * データの変更
	 * @param array $data
	 * @param array $conditions
	 */
	final public function update( $data, $conditions = array() ) {
		return $this->db->update( __CLASS__, $data, $conditions );
	}

	/**
	 * データの削除
	 * @param array $conditions
	 */
	final public function destroy( $conditions = array() ) {
		return $this->db->delete( __CLASS__, $conditions );
	}
}
