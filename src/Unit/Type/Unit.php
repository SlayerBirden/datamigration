<?php

namespace Maketok\DataMigration\Unit\Type;

use Maketok\DataMigration\Unit\ExportDbUnitInterface;
use Maketok\DataMigration\Unit\ExportFileUnitInterface;
use Maketok\DataMigration\Unit\GenerateUnitInterface;
use Maketok\DataMigration\Unit\ImportDbUnitInterface;
use Maketok\DataMigration\Unit\ImportFileUnitInterface;

class Unit extends GeneratorUnit
    implements ImportFileUnitInterface, ImportDbUnitInterface, ExportDbUnitInterface,
    ExportFileUnitInterface, GenerateUnitInterface
{
}
