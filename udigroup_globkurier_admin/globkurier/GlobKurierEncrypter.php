<?php

namespace udigroup_globkurier;

class GlobKurierEncrypter{
	
	private $appKey = 'TMHJhd6eQBzRL33AZCE7qHxc3FbYog59ekw3eu7k7CDCyV2ACY5sd9SUxB7jMqJtSWatDBXjgRMHpQZvtjay6rhvvLLrrHJZ4GhqjhVvBc4otumqpVMk6KGvrwv7Ai7y';
	private $secret_iv = 'HTFmiFBbad6jRhU8QfzrkQP8c';
	
	public function encrypt( $password ){
		
		return $this->encrypter( $password, 'encrypt' );
	}
	public function decrypt( $password ){
		
		return $this->encrypter( $password, 'decrypt' );
	}
	
	private function encrypter( $string, $action = 'encrypt' ){
		$output         = FALSE;
		$encrypt_method = 'AES-256-CBC';
		$key            = hash( 'sha256', $this->appKey );
		$iv             = substr( hash( 'sha256', $this->secret_iv ), 0, 16 );
		
		if( $action == 'encrypt' ){
			$output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
		} else if( $action == 'decrypt' ){
			$output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
		}
		
		return $output;
	}
	
}