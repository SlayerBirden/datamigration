#General
The overall info about the library and decisions

## Structure

### Units
**Unit** is the smallest structure unit of each Workflow,
 and the most important one.
Each unit contains data required for different actions to work.
Unit by itself doesn't do any work, however the configuration
 of units is the most important part of each workflow.
The `Maketok\DataMigration\Unit\Type\Unit` class encapsulates
 all config properties needed for every Action.

### Action
**Action** is the logical division of complex processes like *import* or *export*.
The typical import consists of `CreateTmpFiles`, `Load` and `Move`.
Export: `ReverseMove`, `Dump`, `AssembleInput`.
There are a couple of reasons why both import and export are moving
 data using temporary CSV files.

* MySQL's `LOAD DATA INFILE` only works with CSV-like files
 (the delimiter doesn't necessarily need to be a comma).
And it's really important to have that in place for big files.
That allows utilize this powerful feature for other input formats like XML,
 YAML or even different (non-file) resources entirely
* Allows to debug the intermediate state of mapped data safely.

There's also a Generate action that can be utilized both
 to generate data and populate DB
  and to create a sample import file without the need to have
   anything ready in the DB to export.

### Workflow
**Workflow** consists of Actions that it utilizes in predefined order.
 The is the wrapper entity.
Workflow accepts config instance and `Result` object that will contain meta
 info after Workflow is executed.
