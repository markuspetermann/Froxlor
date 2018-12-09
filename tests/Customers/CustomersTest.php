<?php
use PHPUnit\Framework\TestCase;

/**
 *
 * @covers ApiCommand
 * @covers ApiParameter
 * @covers Customers
 * @covers Admins
 */
class CustomersTest extends TestCase
{

	public function testAdminCustomersAdd()
	{
		global $admin_userdata;
		
		$data = [
			'new_loginname' => 'test1',
			'email' => 'team@froxlor.org',
			'firstname' => 'Test',
			'name' => 'Testman',
			'customernumber' => 1337,
			'diskspace' => 0,
			'diskspace_ul' => 1,
			'traffic' => - 1,
			'subdomains' => 15,
			'emails' => - 1,
			'email_accounts' => 15,
			'email_forwarders' => 15,
			'email_imap' => 1,
			'email_pop3' => 0,
			'ftps' => 15,
			'tickets' => 15,
			'mysqls' => 15,
			'createstdsubdomain' => 1,
			'new_customer_password' => 'h0lYmo1y',
			'sendpassword' => TRAVIS_CI == 1 ? 0 : 1,
			'phpenabled' => 1,
			'store_defaultindex' => 1,
			'custom_notes' => 'secret',
			'custom_notes_show' => 0,
			'gender' => 5,
			'allowed_phpconfigs' => array(
				1
			)
		];
		
		$json_result = Customers::getLocal($admin_userdata, $data)->add();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(1, $result['customerid']);
		$this->assertEquals('team@froxlor.org', $result['email']);
		$this->assertEquals(1337, $result['customernumber']);
		$this->assertEquals(15, $result['subdomains']);
		$this->assertEquals('secret', $result['custom_notes']);
	}

	public function testAdminCustomersAddEmptyMail()
	{
		global $admin_userdata;
		
		$data = [
			'new_loginname' => 'test2',
			'email' => ' ',
			'firstname' => 'Test2',
			'name' => 'Testman2'
		];
		
		$this->expectExceptionMessage('Requested parameter "email" is empty where it should not be for "Customers:add"');
		Customers::getLocal($admin_userdata, $data)->add();
	}

	public function testAdminCustomersAddInvalidMail()
	{
		global $admin_userdata;
		
		$data = [
			'new_loginname' => 'test2',
			'email' => 'test.froxlor.org',
			'firstname' => 'Test2',
			'name' => 'Testman2'
		];
		
		$this->expectExceptionMessage("Email-address test.froxlor.org contains invalid characters or is incomplete");
		Customers::getLocal($admin_userdata, $data)->add();
	}

	/**
	 *
	 * @depends testAdminCustomersAdd
	 */
	public function testAdminCustomersList()
	{
		global $admin_userdata;
		
		$json_result = Customers::getLocal($admin_userdata)->listing();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(1, $result['count']);
	}

	/**
	 *
	 * @depends testAdminCustomersAdd
	 */
	public function testResellerCustomersList()
	{
		global $admin_userdata;
		// get reseller
		$json_result = Admins::getLocal($admin_userdata, array(
			'loginname' => 'reseller'
		))->get();
		$reseller_userdata = json_decode($json_result, true)['data'];
		$reseller_userdata['adminsession'] = 1;
		$json_result = Customers::getLocal($reseller_userdata)->listing();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(0, $result['count']);
	}

	/**
	 *
	 * @depends testAdminCustomersAdd
	 */
	public function testCustomerCustomersList()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'id' => 1
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage("Not allowed to execute given command.");
		
		$json_result = Customers::getLocal($customer_userdata)->listing();
	}

	/**
	 *
	 * @depends testAdminCustomersAdd
	 */
	public function testCustomerCustomersGet()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'id' => 1
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		
		$json_result = Customers::getLocal($customer_userdata, array(
			'id' => $customer_userdata['customerid']
		))->get();
		$result = json_decode($json_result, true)['data'];
		
		$this->assertEquals(1, $result['customerid']);
		$this->assertEquals('team@froxlor.org', $result['email']);
		$this->assertEquals(1337, $result['customernumber']);
		$this->assertEquals(15, $result['subdomains']);
		$this->assertEquals('Sparkle', $result['theme']);
		$this->assertEquals('', $result['custom_notes']);
	}

	public function testAdminCustomersGetNotFound()
	{
		global $admin_userdata;
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage("Customer with id #999 could not be found");
		Customers::getLocal($admin_userdata, array(
			'id' => 999
		))->get();
	}

	/**
	 *
	 * @depends testAdminCustomersAdd
	 */
	public function testCustomerCustomersGetForeign()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'id' => 1
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		
		$this->expectException(Exception::class);
		$this->expectExceptionCode(401);
		
		Customers::getLocal($customer_userdata, array(
			'id' => 2
		))->get();
	}

	/**
	 *
	 * @depends testAdminCustomersAdd
	 */
	public function testAdminCustomerUpdateDeactivate()
	{
		global $admin_userdata;
		// get customer
		Customers::getLocal($admin_userdata, array(
			'id' => 1,
			'deactivated' => 1
		))->update();
		
		// get customer and check results
		$json_result = Customers::getLocal($admin_userdata, array(
			'id' => 1
		))->get();
		$result = json_decode($json_result, true)['data'];
		
		$this->assertEquals(1, $result['customerid']);
		$this->assertEquals(1, $result['deactivated']);
		// standard-subdomains will be removed on deactivation
		$this->assertEquals(0, $result['standardsubdomain']);
	}

	/**
	 *
	 * @depends testAdminCustomersAdd
	 */
	public function testCustomerCustomersGetWhenDeactivated()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'id' => 1
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		
		$this->expectException(Exception::class);
		$this->expectExceptionCode(406);
		$this->expectExceptionMessage("Account suspended");
		
		Customers::getLocal($customer_userdata, array(
			'id' => $customer_userdata['customerid']
		))->get();
	}

	/**
	 *
	 * @depends testAdminCustomersAdd
	 */
	public function testCustomerCustomersUpdate()
	{
		global $admin_userdata;
		
		// reactivate customer
		// get customer
		Customers::getLocal($admin_userdata, array(
			'id' => 1,
			'deactivated' => 0
		))->update();
		
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'id' => 1
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		
		Customers::getLocal($customer_userdata, array(
			'id' => $customer_userdata['customerid'],
			'def_language' => 'English',
			'theme' => 'Froxlor',
			'new_customer_password' => 'h0lYmo1y2'
		))->update();
		
		$json_result = Customers::getLocal($customer_userdata, array(
			'id' => $customer_userdata['customerid']
		))->get();
		$result = json_decode($json_result, true)['data'];
		
		$this->assertEquals('Froxlor', $result['theme']);
		$this->assertEquals('English', $result['def_language']);
	}

	/**
	 *
	 * @depends testAdminCustomersAdd
	 */
	public function testResellerCustomersAddAllocateMore()
	{
		global $admin_userdata;
		// get reseller
		$json_result = Admins::getLocal($admin_userdata, array(
			'loginname' => 'reseller'
		))->get();
		$reseller_userdata = json_decode($json_result, true)['data'];
		$reseller_userdata['adminsession'] = 1;
		
		$this->expectExceptionMessage("You cannot allocate more resources than you own for yourself.");
		// add new customer
		$data = [
			'new_loginname' => 'test2',
			'email' => 'test2@froxlor.org',
			'firstname' => 'Test',
			'name' => 'Testman',
			'customernumber' => 1338,
			'subdomains' => - 1,
			'new_customer_password' => 'h0lYmo1y'
		];
		Customers::getLocal($reseller_userdata, $data)->add();
	}

	public function testCustomerCustomersDelete()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage("Not allowed to execute given command.");
		Customers::getLocal($customer_userdata, array(
			'loginname' => 'test1'
		))->delete();
	}

	public function testResellerCustomersDeleteNotOwned()
	{
		global $admin_userdata;
		// get reseller
		$json_result = Admins::getLocal($admin_userdata, array(
			'loginname' => 'reseller'
		))->get();
		$reseller_userdata = json_decode($json_result, true)['data'];
		$reseller_userdata['adminsession'] = 1;
		$this->expectExceptionCode(404);
		Customers::getLocal($reseller_userdata, array(
			'loginname' => 'test1'
		))->delete();
	}

	public function testAdminCustomersDelete()
	{
		global $admin_userdata;
		// add new customer
		$data = [
			'new_loginname' => 'test2',
			'email' => 'test2@froxlor.org',
			'firstname' => 'Test',
			'name' => 'Testman',
			'customernumber' => 1338,
			'new_customer_password' => 'h0lYmo1y'
		];
		Customers::getLocal($admin_userdata, $data)->add();
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test2'
		))->delete();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('test2', $result['loginname']);
	}

	public function testAdminCustomersUnlock()
	{
		global $admin_userdata;
		// update customer to have correct test-data
		Database::query("UPDATE `" . TABLE_PANEL_CUSTOMERS . "` SET `loginfail_count` = '5' WHERE `loginname` = 'test1'");
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->unlock();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(0, $result['loginfail_count']);
	}

	public function testAdminCustomersUnlockNotAllowed()
	{
		global $admin_userdata;
		$testadmin_userdata = $admin_userdata;
		$testadmin_userdata['adminsession'] = 0;
		
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage("Not allowed to execute given command.");
		Customers::getLocal($testadmin_userdata, array(
			'loginname' => 'test1'
		))->unlock();
	}

	public function testAdminCustomersMoveNotAllowed()
	{
		global $admin_userdata;
		$testadmin_userdata = $admin_userdata;
		$testadmin_userdata['change_serversettings'] = 0;
		
		$this->expectExceptionCode(403);
		$this->expectExceptionMessage("Not allowed to execute given command.");
		Customers::getLocal($testadmin_userdata, array(
			'loginname' => 'test1',
			'adminid' => 1
		))->move();
	}

	public function testAdminCustomersMoveTargetIsSource()
	{
		global $admin_userdata;
		$this->expectExceptionCode(406);
		$this->expectExceptionMessage("Cannot move customer to the same admin/reseller as he currently is assigned to");
		Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1',
			'adminid' => 1
		))->move();
	}

	public function testAdminCustomersMove()
	{
		global $admin_userdata;
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1',
			'adminid' => 2
		))->move();
		$result = json_decode($json_result, true)['data'];
		
		$this->assertEquals(2, $result['adminid']);
	}

	/**
	 *
	 * @depends testAdminCustomersMove
	 */
	public function testAdminCustomersAddLoginnameIsSystemaccount()
	{
		global $admin_userdata;
		
		$data = [
			'new_loginname' => 'web1',
			'email' => 'team@froxlor.org',
			'firstname' => 'Test',
			'name' => 'Testman',
			'customernumber' => 1338,
			'diskspace' => - 1,
			'traffic' => - 1,
			'subdomains' => 15,
			'emails' => - 1,
			'email_accounts' => 15,
			'email_forwarders' => 15,
			'email_imap' => 1,
			'email_pop3' => 0,
			'ftps' => 15,
			'tickets' => 15,
			'mysqls' => 15,
			'createstdsubdomain' => 1,
			'new_customer_password' => 'h0lYmo1y',
			'sendpassword' => 1,
			'phpenabled' => 1,
			'store_defaultindex' => 1,
			'custom_notes' => 'secret',
			'custom_notes_show' => 0,
			'gender' => 5,
			'allowed_phpconfigs' => array(
				1
			)
		];
		
		$this->expectExceptionMessage('You cannot create accounts which are similar to system accounts (as for example begin with "web"). Please enter another account name.');
		Customers::getLocal($admin_userdata, $data)->add();
	}

	/**
	 *
	 * @depends testAdminCustomersAddLoginnameIsSystemaccount
	 */
	public function testAdminCustomersAddAutoLoginname()
	{
		global $admin_userdata;
		
		Settings::Set('system.lastaccountnumber', 0, true);
		Settings::Set('ticket.enabled', 0, true);
		
		$data = [
			'new_loginname' => '',
			'email' => 'team@froxlor.org',
			'firstname' => 'Test2',
			'name' => 'Testman2',
			'customernumber' => 1338,
			'sendpassword' => 0,
			'perlenabled' => 2,
			'dnsenabled' => 4
		];
		
		$json_result = Customers::getLocal($admin_userdata, $data)->add();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('web1', $result['loginname']);
		$this->assertEquals(1338, $result['customernumber']);
	}

	/**
	 *
	 * @depends testAdminCustomersAddAutoLoginname
	 */
	public function testAdminCustomersAddLoginnameExists()
	{
		global $admin_userdata;
		
		$data = [
			'new_loginname' => 'test1',
			'email' => 'team@froxlor.org',
			'firstname' => 'Test2',
			'name' => 'Testman2',
			'customernumber' => 1339
		];
		
		$this->expectExceptionMessage('Loginname test1 already exists');
		Customers::getLocal($admin_userdata, $data)->add();
	}

	/**
	 *
	 * @depends testAdminCustomersAddLoginnameExists
	 */
	public function testAdminCustomersAddLoginnameInvalid()
	{
		global $admin_userdata;
		
		$data = [
			'new_loginname' => 'user-',
			'email' => 'team@froxlor.org',
			'firstname' => 'Test2',
			'name' => 'Testman2',
			'customernumber' => 1339
		];
		
		$this->expectExceptionMessage('Loginname "user-" contains illegal characters.');
		Customers::getLocal($admin_userdata, $data)->add();
	}

	/**
	 *
	 * @depends testAdminCustomersAddLoginnameExists
	 */
	public function testAdminCustomersAddLoginnameInvalid2()
	{
		global $admin_userdata;
		
		$data = [
			'new_loginname' => 'useruseruseruseruseruserX',
			'email' => 'team@froxlor.org',
			'firstname' => 'Test2',
			'name' => 'Testman2',
			'customernumber' => 1339
		];
		
		$this->expectExceptionMessage('Loginname contains too many characters. Only ' . (14 - strlen(Settings::Get('customer.mysqlprefix'))) . ' characters are allowed.');
		Customers::getLocal($admin_userdata, $data)->add();
	}
}
