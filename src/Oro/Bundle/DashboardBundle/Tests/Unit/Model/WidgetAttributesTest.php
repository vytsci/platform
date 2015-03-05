<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;

class WidgetAttributesTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetConfigs */
    protected $target;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resolver;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = $this->getMock('Oro\Component\Config\Resolver\ResolverInterface');

        $dashboardManager = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\Manager')
                ->disableOriginalConstructor()
                ->getMock();

        $stateManager = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\StateManager')
                ->disableOriginalConstructor()
                ->getMock();

        $this->target = new WidgetConfigs($this->configProvider, $this->securityFacade, $this->resolver, $dashboardManager, $stateManager);
    }

    public function testGetWidgetAttributesForTwig()
    {
        $expectedWidgetName = 'widget_name';
        $configs            = [
            'route'            => 'sample route',
            'route_parameters' => 'sample params',
            'acl'              => 'view_acl',
            'items'            => [],
            'test-param'       => 'param'
        ];
        $expected           = ['widgetName' => $expectedWidgetName, 'widgetTestParam' => 'param'];
        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->will($this->returnValue($configs));

        $actual = $this->target->getWidgetAttributesForTwig($expectedWidgetName);
        $this->assertEquals($expected, $actual);
    }

    public function testGetWidgetItems()
    {
        $expectedWidgetName = 'widget_name';
        $notAllowedAcl      = 'invalid_acl';
        $allowedAcl         = 'valid_acl';
        $expectedItem       = 'expected_item';
        $expectedValue      = ['label' => 'test label', 'acl' => $allowedAcl, 'enabled' => true];
        $notGrantedItem     = 'not_granted_item';
        $notGrantedValue    = ['label' => 'not granted label', 'acl' => $notAllowedAcl, 'enabled' => true];
        $applicableItem     = 'applicable_item';
        $applicable         = [
            'label' => 'applicable is set and resolved to true',
            'applicable' => '@true',
            'enabled' => true
        ];
        $notApplicableItem  = 'not_applicable_item';
        $notApplicable      = [
            'label' => 'applicable is set and resolved to false',
            'applicable' => '@false',
            'enabled' => true
        ];
        $disabledItem  = 'not_applicable_item';
        $disabled      = [
            'label' => 'applicable is set and resolved to false',
            'acl' => $allowedAcl,
            'enabled' => false
        ];

        $configs            = [
            $expectedItem       => $expectedValue,
            $notGrantedItem     => $notGrantedValue,
            $applicableItem     => $applicable,
            $notApplicableItem  => $notApplicable,
            $disabledItem       => $disabled
        ];

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->will($this->returnValue(['items' => $configs]));

        $this->securityFacade->expects($this->exactly(3))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        [['@true'], [], true],
                        [$allowedAcl, null, true]
                    ]
                )
            );
        $this->resolver->expects($this->exactly(1))
            ->method('resolve')
            ->will(
                $this->returnValueMap(
                    [

                        [['@false'], [], [false]],
                        [['@true'], [], [true]],
                    ]
                )
            );

        $result = $this->target->getWidgetItems($expectedWidgetName);
        $this->assertArrayHasKey($applicableItem, $result);
        $this->assertArrayHasKey($expectedItem, $result);
    }

    public function testGetWidgetConfigs()
    {
        $notAllowedAcl      = 'invalid_acl';
        $allowedAcl         = 'valid_acl';
        $expectedItem       = 'expected_item';
        $expectedValue      = ['label' => 'test label', 'acl' => $allowedAcl, 'enabled' => true];
        $notGrantedItem     = 'not_granted_item';
        $notGrantedValue    = ['label' => 'not granted label', 'acl' => $notAllowedAcl, 'enabled' => true];
        $applicableItem     = 'applicable_item';
        $applicable         = [
            'label' => 'applicable is set and resolved to true',
            'applicable' => '@true',
            'enabled' => true
        ];
        $notApplicableItem  = 'not_applicable_item';
        $notApplicable      = [
            'label' => 'applicable is set and resolved to false',
            'applicable' => '@false',
            'enabled' => true
        ];
        $configs            = [
            $expectedItem      => $expectedValue,
            $notGrantedItem    => $notGrantedValue,
            $applicableItem    => $applicable,
            $notApplicableItem => $notApplicable
        ];

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfigs')
            ->will($this->returnValue($configs));

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        [['@true'], [], true],
                        [$allowedAcl, null, true]
                    ]
                )
            );
        $this->resolver->expects($this->exactly(2))
            ->method('resolve')
            ->will(
                $this->returnValueMap(
                    [

                        [['@false'], [], [false]],
                        [['@true'], [], [true]],
                    ]
                )
            );

        $result = $this->target->getWidgetConfigs();
        $this->assertArrayHasKey($applicableItem, $result);
        $this->assertArrayHasKey($expectedItem, $result);
    }
}
