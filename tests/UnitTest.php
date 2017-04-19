<?php
class WebTest extends PHPUnit_Extensions_Selenium2TestCase
{
    protected $coverageScriptUrl = 'http://localhost:8193/';

    protected function setUp()
    {
        $this->setBrowser('firefox');
        $this->setBrowserUrl('http://localhost:8192/status_api/overview');
    }

    public function testTitle()
    {
        $session = $this->prepareSession();
        $session->cookie()->remove('PHPUNIT_SELENIUM_TEST_ID');
        $session->cookie()->add('PHPUNIT_SELENIUM_TEST_ID', 'WebTest__testTitle')->set();
        $this->url('http://localhost:8192/status_api/overview');
        $this->assertEquals('MyEMSL Status - Overview', $this->title());
    }
}
?>
