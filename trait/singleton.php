<?php
trait Singleton {
	/**
	 * コンストラクタ
	 */
	protected function __construct() {}

	/**
	 * クローンの禁止
	 */
	final public function __clone() {
		throw new \Excption('This instance is singleton class.');
	}

	/**
	 * インスタンスの取得
	 */
	final public function getInstance() {
		static $instance;
		return $instance ?: $instance = new static;
	}
}
