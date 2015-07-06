<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Db\ResourceInterface as DbResourceInterface;

class AbstractDbAction extends AbstractAction
{
    /**
     * @var DbResourceInterface
     */
    protected $resource;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param LanguageInterface $language
     * @param DbResourceInterface $resource
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        LanguageInterface $language,
        DbResourceInterface $resource
    ) {
        parent::__construct($bag, $config, $language);
        $this->resource = $resource;
    }
}
