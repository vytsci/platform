<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\Migrations\MigrateTypesQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTrackerBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** placed here to load in last order */
        $queries->addQuery(new MigrateTypesQuery($schema, 'oro_integration_channel', 'id', Type::INTEGER));
        $queries->addQuery(new MigrateTypesQuery($schema, 'oro_integration_channel_status', 'id', Type::INTEGER));
        $queries->addQuery(new MigrateTypesQuery($schema, 'oro_integration_transport', 'id', Type::INTEGER));

        $queries->addQuery(new MigrateTypesQuery($schema, 'oro_access_group', 'id', Type::INTEGER));
        $queries->addQuery(new MigrateTypesQuery($schema, 'oro_access_role', 'id', Type::INTEGER));
        $queries->addQuery(new MigrateTypesQuery($schema, 'oro_user_email', 'id', Type::INTEGER));
        $queries->addQuery(new MigrateTypesQuery($schema, 'oro_user_status', 'id', Type::INTEGER));

        $table = $schema->getTable('oro_tracking_event');
        $table->getColumn('value')->setType(Type::getType('integer'));

        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'user',
            'user_identifier'
        );
    }
}
