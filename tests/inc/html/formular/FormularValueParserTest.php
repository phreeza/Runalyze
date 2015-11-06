<?php

use Runalyze\Configuration;
use Runalyze\Parameter\Application\TemperatureUnit;

/**
 * Test class for FormularValueParser.
 * Generated by PHPUnit on 2012-03-05 at 12:45:41.
 */
class FormularValueParserTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var FormularValueParser
	 */
	protected $object;

	protected function setUp() {
		$this->object = new FormularValueParser;
		$_POST = array();
	}

	protected function tearDown() {
		$_POST = array();
	}

	public function testValidatePost_PARSER_DATE() {
		$Parser = FormularValueParser::$PARSER_DATE;
		$_POST  = array(
			'empty'	=> '',
			'text'	=> 'test',
			'int'	=> '27',
			'd.m'	=> '13.4.',
			'd.m.Y'	=> '13.4.10',
			'date'	=> '13.4.2010'
		);

		$this->assertTrue( true !== $this->object->validatePost('empty', $Parser) );
		$this->assertTrue( true !== $this->object->validatePost('text', $Parser) );
		$this->assertTrue( true !== $this->object->validatePost('int', $Parser) );
		$this->assertTrue( $this->object->validatePost('d.m', $Parser) );
		$this->assertTrue( $this->object->validatePost('d.m.Y', $Parser) );
		$this->assertTrue( $this->object->validatePost('date', $Parser) );

		$this->assertEquals( $_POST['d.m'], mktime(0, 0, 0, 4, 13, date('Y')) );
		$this->assertEquals( $_POST['d.m.Y'], mktime(0, 0, 0, 4, 13, 2010) );
		$this->assertEquals( $_POST['date'], mktime(0, 0, 0, 4, 13, 2010) );
	}

	public function testValidatePost_PARSER_DAYTIME() {
		$Parser = FormularValueParser::$PARSER_DAYTIME;
		$_POST  = array(
			'empty'	=> '',
			'text'	=> 'test',
			'int'	=> '27',
			'tooBig'=> '25:17',
			'time'	=> '13:41',
			'time2'	=> '02:17'
		);

		$this->assertTrue( $this->object->validatePost('empty', $Parser) );
		$this->assertTrue( true !== $this->object->validatePost('text', $Parser) );
		$this->assertTrue( true !== $this->object->validatePost('int', $Parser) );
		$this->assertTrue( true !== $this->object->validatePost('tooBig', $Parser) );
		$this->assertTrue( $this->object->validatePost('time', $Parser) );
		$this->assertTrue( $this->object->validatePost('time2', $Parser) );

		$this->assertEquals( $_POST['empty'], 0 );
		$this->assertEquals( $_POST['time'], 13*60*60 + 41*60 );
		$this->assertEquals( $_POST['time2'], 2*60*60 + 17*60 );
	}

	public function testValidatePost_PARSER_STRING() {
		$Parser  = FormularValueParser::$PARSER_STRING;
		$_POST   = array(
			'empty'		=> '',
			'string'	=> 'Test'
		);

		$this->assertTrue( true !== $this->object->validatePost('empty', $Parser, array('notempty' => true)) );
		$this->assertTrue( $this->object->validatePost('string', $Parser) );
	}

	public function testValidatePost_PARSER_INT() {
		$Parser  = FormularValueParser::$PARSER_INT;
		$Options = array('precision' => '2');
		$_POST   = array(
			'empty'	=> '',
			'toBig'	=> '270',
			'float'	=> '27.5',
			'int'	=> '27'
		);

		$this->assertTrue( true !== $this->object->validatePost('empty', $Parser, $Options) );
		$this->assertTrue( true !== $this->object->validatePost('toBig', $Parser, $Options) );
		$this->assertTrue( true !== $this->object->validatePost('float', $Parser, $Options) );
		$this->assertTrue( $this->object->validatePost('int', $Parser, $Options) );

		$this->assertEquals( $_POST['int'], 27);
	}

	public function testValidatePost_PARSER_DECIMAL() {
		// TODO: Uses Helper::CommaToPoint, not testable because of mysql-connection
		$Parser  = FormularValueParser::$PARSER_DECIMAL;
		$Options = array('precision' => '3,1');
		$_POST   = array(
			'empty'	=> '',
			'toBig'	=> '270',
			'comma'	=> '27,5',
			'point'	=> '27.5',
			'zero'	=> '0'
		);

		$this->assertTrue( true !== $this->object->validatePost('empty', $Parser, $Options) );
		$this->assertTrue( true !== $this->object->validatePost('toBig', $Parser, $Options) );
		$this->assertTrue( $this->object->validatePost('comma', $Parser, $Options) );
		$this->assertTrue( $this->object->validatePost('point', $Parser, $Options) );
		$this->assertTrue( $this->object->validatePost('zero', $Parser, $Options) );

		$this->assertEquals( $_POST['comma'], 27.5);
		$this->assertEquals( $_POST['point'], 27.5);
		$this->assertEquals( $_POST['zero'], 0.0);
	}

	public function testParse() {
		$date = time();
		$this->object->parse($date, FormularValueParser::$PARSER_DATE);
		$this->assertEquals($date, date('d.m.Y'));

		$daytime = time();
		$this->object->parse($daytime, FormularValueParser::$PARSER_DAYTIME);
		$this->assertEquals($daytime, date('H:i'));

		$int = 27;
		$this->object->parse($int, FormularValueParser::$PARSER_INT);
		$this->assertEquals($int, 27);

		$string = 'Test';
		$this->object->parse($string, FormularValueParser::$PARSER_STRING);
		$this->assertEquals($string, 'Test');
	}

	public function testParsingTemperature() {
		Configuration::General()->temperatureUnit()->set(TemperatureUnit::CELSIUS);
		$null = null;
		$this->object->parse($null, FormularValueParser::$PARSER_TEMPERATURE);
		$this->assertEquals('', $null);

		$zero = 0;
		$this->object->parse($zero, FormularValueParser::$PARSER_TEMPERATURE);
		$this->assertEquals('0', $zero);

		$celsius = 10;
		$this->object->parse($celsius, FormularValueParser::$PARSER_TEMPERATURE);
		$this->assertEquals('10', $celsius);

		Configuration::General()->temperatureUnit()->set(TemperatureUnit::FAHRENHEIT);
		$this->object->parse($celsius, FormularValueParser::$PARSER_TEMPERATURE);
		$this->assertEquals('50', $celsius);
	}

	public function testValidatingTemperatureInCelsius() {
		Configuration::General()->temperatureUnit()->set(TemperatureUnit::CELSIUS);
		$_POST   = array(
			'empty'	=> '',
			'negative'	=> '-10',
			'comma'	=> '13,5',
			'point'	=> '13.5',
			'zero'	=> '0',
			'large'	=> '33'
		);

		foreach (array_keys($_POST) as $key) {
			$this->object->validatePost($key, FormularValueParser::$PARSER_TEMPERATURE);
		}

		$this->assertEquals(null, $_POST['empty']);
		$this->assertEquals(-10, $_POST['negative']);
		$this->assertEquals(13.5, $_POST['comma']);
		$this->assertEquals(13.5, $_POST['point']);
		$this->assertEquals(0, $_POST['zero']);
		$this->assertEquals(33, $_POST['large']);
	}

	public function testValidatingTemperatureInFahrenheit() {
		Configuration::General()->temperatureUnit()->set(TemperatureUnit::FAHRENHEIT);
		$_POST   = array(
			'empty'	=> '',
			'negative'	=> '-10',
			'comma'	=> '13,5',
			'point'	=> '13.5',
			'zero'	=> '0',
			'large'	=> '33'
		);

		foreach (array_keys($_POST) as $key) {
			$this->object->validatePost($key, FormularValueParser::$PARSER_TEMPERATURE);
		}

		$this->assertEquals(null, $_POST['empty']);
		$this->assertEquals(-23.3, $_POST['negative'], '', 0.1);
		$this->assertEquals(-10.3, $_POST['comma'], '', 0.1);
		$this->assertEquals(-10.3, $_POST['point'], '', 0.1);
		$this->assertEquals(-17.8, $_POST['zero'], '', 0.1);
		$this->assertEquals(0.56, $_POST['large'], '', 0.1);
	}

}
