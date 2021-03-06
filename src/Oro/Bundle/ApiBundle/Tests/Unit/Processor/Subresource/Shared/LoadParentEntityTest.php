<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadParentEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\EntitySerializer\QueryFactory;

class LoadParentEntityTest extends ChangeRelationshipProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdHelper */
    private $entityIdHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryFactory */
    private $queryFactory;

    /** @var LoadParentEntity */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityIdHelper = $this->createMock(EntityIdHelper::class);
        $this->queryFactory = $this->createMock(QueryFactory::class);

        $this->processor = new LoadParentEntity(
            $this->doctrineHelper,
            $this->entityIdHelper,
            $this->queryFactory
        );
    }

    public function testProcessWhenParentEntityIsAlreadyLoaded()
    {
        $parentEntity = new \stdClass();

        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');

        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);

        self::assertSame($parentEntity, $this->context->getParentEntity());
    }

    public function testProcessForNotManageableEntity()
    {
        $parentClass = 'Test\Class';
        $parentConfig = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn(null);

        $this->context->setParentClassName($parentClass);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasParentEntity());
    }

    public function testProcessForManageableEntity()
    {
        $parentClass = 'Test\Class';
        $parentId = 123;
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata();
        $parentEntity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn($parentClass);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($parentClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::once())
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $parentId, self::identicalTo($parentMetadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryFactory->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($qb), self::identicalTo($parentConfig))
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($parentEntity);

        $this->context->setParentClassName($parentClass);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertSame($parentEntity, $this->context->getParentEntity());
    }

    public function testProcessForManageableEntityWhenEntityNotFound()
    {
        $parentClass = 'Test\Class';
        $parentId = 123;
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn($parentClass);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($parentClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::once())
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $parentId, self::identicalTo($parentMetadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryFactory->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($qb), self::identicalTo($parentConfig))
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $this->context->setParentClassName($parentClass);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasParentEntity());
    }

    public function testProcessForResourceBasedOnManageableEntity()
    {
        $parentClass = 'Test\Class';
        $parentResourceClass = 'Test\ParentResourceClass';
        $parentId = 123;
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata();
        $parentEntity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentResourceClass, $parentConfig)
            ->willReturn($parentClass);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($parentClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::once())
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $parentId, self::identicalTo($parentMetadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryFactory->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($qb), self::identicalTo($parentConfig))
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($parentEntity);

        $this->context->setParentClassName($parentResourceClass);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertSame($parentEntity, $this->context->getParentEntity());
    }
}
