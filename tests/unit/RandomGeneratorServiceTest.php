<?php

use creode\magiclogin\services\MagicLoginRandomGeneratorService;
use RandomLib\Factory;
use RandomLib\Generator;

class RandomGeneratorServiceTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Service class that we would like to test.
     *
     * @var \creode\magiclogin\services\MagicLoginRandomGeneratorService
     */
    protected $service;
    
    /**
     * @inheritdoc
     */
    protected function _before()
    {
    }

    /**
     * 
     * 
     * @param \RandomLib\Generator $generator Generator Class to test with.
     * 
     * @dataProvider createStrengthGeneratorsProvider
     */
    public function testStrengthGenerators($generator)
    {
        $this->assertInstanceOf(Generator::class, $generator);

        $this->assertTrue(
            method_exists($generator, 'generateString'), 
            'Class does not have method generateString'
        );
    }

    /**
     * Data Provider for Creating Generator Classes.
     * 
     * @return array
     */
    public function createStrengthGeneratorsProvider()
    {
        $service = new MagicLoginRandomGeneratorService(new Factory);
        return [
            'Medium Strength Generator' => [
                 $service->getMediumStrengthGenerator()
            ],
            'High Strength Generator' => [
                 $service->getHighStrengthGenerator()
            ]
        ];
    }
}