<?php
	namespace cncflora\repository;
	include_once 'vendor/autoload.php';
	
	class BiblioTest extends \PHPUnit_Framework_TestCase{
		public function testBiblio(){
			$repo = new Biblio();
			$this->assertNotNull($repo);
		}
		
		public function testCRUD(){
			$this->markTestIncomplete('This test has not been implemented yet.');
		}
	}
	
	
	
?>