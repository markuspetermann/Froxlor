<?php
use PHPUnit\Framework\TestCase;

/**
 *
 * @covers ApiCommand
 * @covers ApiParameter
 * @covers FpmDaemons
 */
class FpmDaemonsTest extends TestCase
{
	private static $id = 0;

	public function testAdminFpmDaemonsAdd()
	{
		global $admin_userdata;
		$data = [
			'description' => 'test2 fpm',
			'reload_cmd' => 'service php7.1-fpm reload',
			'config_dir' => '/etc/php/7.1/fpm/pool.d',
			'limit_extensions' => ''
		];
		$json_result = FpmDaemons::getLocal($admin_userdata, $data)->add();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('/etc/php/7.1/fpm/pool.d/', $result['config_dir']);
		$this->assertEquals(0, $result['max_children']);
		$this->assertEquals('.php', $result['limit_extensions']);
		self::$id = $result['id'];
	}
	
	public function testAdminFpmDaemonsAddUnknownPM()
	{
		global $admin_userdata;
		$data = [
			'description' => 'test2 fpm',
			'reload_cmd' => 'service php7.3-fpm reload',
			'config_dir' => '/etc/php/7.3/fpm/pool.d',
			'pm' => 'supermegapm'
		];
		$this->expectExceptionCode(406);
		$this->expectExceptionMessage("Unknown process manager");
		FpmDaemons::getLocal($admin_userdata, $data)->add();
	}
	
	public function testAdminFpmDaemonsAddInvalidDesc()
	{
		// max 50. characters
		global $admin_userdata;
		$data = [
			'description' => str_repeat('test', 30),
			'reload_cmd' => 'service php7.3-fpm reload',
			'config_dir' => '/etc/php/7.3/fpm/pool.d'
		];
		$this->expectExceptionMessage("The description is too short, too long or contains illegal characters.");
		FpmDaemons::getLocal($admin_userdata, $data)->add();
	}

	/**
	 * @depends testAdminFpmDaemonsAdd
	 */
	public function testAdminFpmDaemonsUpdate()
	{
		global $admin_userdata;
		$data = [
			'id' => self::$id,
			'description' => 'test2 fpm edit',
			'pm' => 'dynamic',
			'max_children' => '10',
			'start_servers' => '4',
			'limit_extensions' => '.php .php.xml',
		];
		$json_result = FpmDaemons::getLocal($admin_userdata, $data)->update();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('/etc/php/7.1/fpm/pool.d/', $result['config_dir']);
		$this->assertEquals(10, $result['max_children']);
		$this->assertEquals('.php .php.xml', $result['limit_extensions']);
	}

	/**
	 * @depends testAdminFpmDaemonsUpdate
	 */
	public function testAdminFpmDaemonsUpdate2()
	{
		global $admin_userdata;
		$data = [
			'id' => self::$id,
			'limit_extensions' => '',
		];
		$json_result = FpmDaemons::getLocal($admin_userdata, $data)->update();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('.php', $result['limit_extensions']);
	}

	/**
	 * @depends testAdminFpmDaemonsAdd
	 */
	public function testAdminFpmDaemonsUpdateUnknownPM()
	{
		global $admin_userdata;
		$data = [
			'id' => self::$id,
			'pm' => 'supermegapm'
		];
		$this->expectExceptionCode(406);
		$this->expectExceptionMessage("Unknown process manager");
		FpmDaemons::getLocal($admin_userdata, $data)->update();
	}
	
	/**
	 * @depends testAdminFpmDaemonsAdd
	 */
	public function testAdminFpmDaemonsUpdateInvalidDesc()
	{
		// max 50. characters
		global $admin_userdata;
		$data = [
			'id' => self::$id,
			'description' => str_repeat('test', 30)
		];
		$this->expectExceptionMessage("The description is too short, too long or contains illegal characters.");
		FpmDaemons::getLocal($admin_userdata, $data)->update();
	}

	/**
	 * @depends testAdminFpmDaemonsUpdate
	 */
	public function testAdminFpmDaemonsList()
	{
		global $admin_userdata;
		$json_result = FpmDaemons::getLocal($admin_userdata)->listing();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(2, $result['count']);
		$this->assertEquals('System default', $result['list'][0]['description']);
		$this->assertEquals('test2 fpm edit', $result['list'][1]['description']);
	}

	public function testAdminFpmDaemonsGetNotFound()
	{
		global $admin_userdata;
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage("fpm-daemon with id #-1 could not be found");
		FpmDaemons::getLocal($admin_userdata, array('id' => -1))->get();
	}

	public function testCustomerFpmDaemonsAdd()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage("Not allowed to execute given command.");
		FpmDaemons::getLocal($customer_userdata)->add();
	}
	
	public function testCustomerFpmDaemonsGet()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage("Not allowed to execute given command.");
		FpmDaemons::getLocal($customer_userdata)->get();
	}

	public function testCustomerFpmDaemonsList()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage("Not allowed to execute given command.");
		FpmDaemons::getLocal($customer_userdata)->listing();
	}

	public function testCustomerFpmDaemonsUpdate()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage("Not allowed to execute given command.");
		FpmDaemons::getLocal($customer_userdata)->update();
	}

	public function testCustomerFpmDaemonsDelete()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage("Not allowed to execute given command.");
		FpmDaemons::getLocal($customer_userdata)->delete();
	}

	/**
	 * @depends testAdminFpmDaemonsList
	 */
	public function testAdminFpmDaemonsDelete()
	{
		global $admin_userdata;
		$data = [
			'id' => self::$id
		];
		$json_result = FpmDaemons::getLocal($admin_userdata, $data)->delete();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('/etc/php/7.1/fpm/pool.d/', $result['config_dir']);
		$this->assertEquals(10, $result['max_children']);
		$this->assertEquals('.php', $result['limit_extensions']);
	}

	/**
	 * @depends testAdminFpmDaemonsDelete
	 */
	public function testAdminFpmDaemonsDeleteDefaultConfig()
	{
		global $admin_userdata;
		$data = [
			'id' => 1
		];
		$this->expectExceptionMessage("This PHP-configuration is set as default and cannot be deleted.");
		FpmDaemons::getLocal($admin_userdata, $data)->delete();
	}
}
