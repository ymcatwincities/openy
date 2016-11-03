<?php
use Sourcefuse\TangoCard as TangoCard;
class TangoCardTests extends PHPUnit_Framework_TestCase{
	var $tango=null;
	protected static $accountid = null;
	protected static $accountname = null;
	protected static $ccToken = null;

    public static function setUpBeforeClass()
    {
    	$timestamp=time();
        self::$accountid = "Id".$timestamp;
        self::$accountname = "Name".$timestamp;
    }

	public static function tearDownAfterClass()
    {
        self::$accountid = NULL;
        self::$accountname = NULL;
    }

	public function setup(){
		$this->tango=new TangoCard("TangoTest","5xItr3dMDlEWAa9S4s7vYh7kQ01d5SFePPUoZZiK/vMfbo3A5BvJLAmD4tI=");
		$this->tango->setAppMode("sandbox");
	}

	public function testProductionAppMode(){
		$this->tango=new TangoCard("TangoTest","5xItr3dMDlEWAa9S4s7vYh7kQ01d5SFePPUoZZiK/vMfbo3A5BvJLAmD4tI=");
		$this->tango->setAppMode("production");
	}

	/**
	* @expectedException Sourcefuse\TangoCardAppModeInvalidException
	*/
	public function testFakeAppMode(){
		$this->tango=new TangoCard("TangoTest","5xItr3dMDlEWAa9S4s7vYh7kQ01d5SFePPUoZZiK/vMfbo3A5BvJLAmD4tI=");

		$this->tango->setAppMode("product");

	}

	public function testAccountCreation(){
		echo 'testing account creation ';
		$response=$this->tango->createAccount(self::$accountname,self::$accountid,'aaa@aaada.com');
		var_dump($response);
		$this->assertTrue($response->success);
		
		// //$tangoCard->fundAccount('tesa223d4sd','tsst234ds',100,'27710537','123'));
	}
	public function testAccountInfo(){
		echo 'testing accont info';
		$info=$this->tango->getAccountInfo(self::$accountname, self::$accountid);
		var_dump($info);
		$this->assertTrue($info->success);
		$this->assertEquals($info->account->identifier,self::$accountid);
		$this->assertEquals($info->account->customer,self::$accountname);
	}

	public function testRegisterCreditCard(){
		echo 'register registertCreditCard';
		$response=$this->tango->registerCreditCard(self::$accountname, self::$accountid,'4111111111111111','123','2016-01','FName','LName','Address','Seattle','WA','98116','USA','test@example.com');
		$this->assertTrue($response->success);
		self::$ccToken = $response->cc_token;
		var_dump($response);
		echo 'token ='. self::$ccToken;
	}

	public function testFundAccount(){
		echo 'test FundAccount';
		$response=$this->tango->fundAccount(self::$accountname, self::$accountid,100,self::$ccToken,'123');
		$this->assertFalse($response->success);
		var_dump($response);
	}

	public function testPlaceOrder(){
		echo 'test Place order';
		$response=$this->tango->placeOrder(self::$accountname, self::$accountid,'Seattle','From','Subject of the message','message','AMCA-E-500-STD',100,'name','a@a.com',TRUE);
		$this->assertFalse($response->success);
		var_dump($response);
	}
	public function testGetOrderInfo(){
		echo 'test Get Order Info';
		$response=$this->tango->getOrderInfo('114-12657558-11');
		$this->assertFalse($response->success);
		var_dump($response);
	}
	public function testGetOrderhistory(){
		echo 'test Get Order History';
		$response=$this->tango->getOrderHistory(self::$accountname, self::$accountid);
		$this->assertTrue($response->success);
		var_dump($response);
	}

	public function testDeleteCreditCard(){
		echo 'test DeleteCreditCard';
		$response=$this->tango->deleteCreditCard(self::$accountname, self::$accountid,self::$ccToken);
		$this->assertTrue($response->success);
		var_dump($response);
	}
	public function testListRewards(){
		echo 'test ListRewards';
		$response=$this->tango->listRewards();
		$this->assertTrue($response->success);
		var_dump($response);
	}
}
?>
