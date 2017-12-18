<?php
trait Utility {
	/**
	 * スネークケースに変換
	 * @param string $str
	 */
	final private function snakize( $str ) {
		return ltrim( strtolower( preg_replace( '/([A-Z])/', '_$1', $str ) ), '_' );
	}

	/**
	 * 複数形に変換
	 * @param string $str
	 */
	final private function plurize( $str ) {
		$str = preg_replace('/(s|sh|ch|o|x)$/i', '$1es', $str);
		$str = preg_replace('/(f|fe)$/i',        '$ves', $str);
		$str = preg_replace('/(a|i|u|e|o)y$/i',  '$1ys', $str);
		$str = preg_replace('/y$/i',             'ies',  $str);
		if ( ! preg_match('/s$/i', $str) ) {
			$str = $str . 's';
		}

		return $str;
	}
}
