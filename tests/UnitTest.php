<?php
use PHPUnit\Framework\TestCase;

class WebTest extends PHPUnit_Extensions_Selenium2TestCase
{
    protected $coverageScriptUrl = 'http://localhost:8193/';

    protected function setUp()
    {
        $this->setBrowser('firefox');
        $this->setBrowserUrl('http://dmlb2001:1234@localhost:8192/status_api/overview');
    }

    public function testTitle()
    {
        $this->url('http://dmlb2001:1234@localhost:8192/status_api/overview');
        $this->assertEquals('MyEMSL Status - Overview', $this->title());
    }
}
?>
