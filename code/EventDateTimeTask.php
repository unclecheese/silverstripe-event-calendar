<?php

/**
 *
 * Migration script to assists with upgrade
 *  Change: CalendarDateTime extends (introduced super) EventDateTime
 * Procedure
 *   Move $db MySQL table fields/columns from from CalendarDateTime to EventDateTime
 *
 * undo (?undo=1); tables are backed-up to *_backup in the process
 *
 * @package EventCalendar
 * @author	OliverBanjo
 */

class EventDateTimeTask extends MigrationTask {

    protected $title = "CalendarDateTime to EventDateTime table fields migration";
    protected $description = "Update MySQL database to reflect change in class model. Introducing base class EventDateTime, migrating date & time fields from CalendarDateTime which now extends EventDateTime";

    public function run($request)
    {
        $new = "EventDateTime";
        $original = "CalendarDateTime";
        $backupPostfix = "_backup";

        if($request->getVar('undo')) $this->restoreTables(array($original, $new), $backupPostfix);
        else $this->amendTables($new, $original, $backupPostfix);

    }



    private function amendTables($newBaseClass, $original, $backupPostfix)
    {
        $backup = $original . $backupPostfix;
        $newBaseTableBackup = $newBaseClass . $backupPostfix;	//incase user has already populated!

        //check can proceed to backup
        if ($this->tableExists($backup)) {
            echo "Cannot execute task. $backup already exists. Remove or rename!";
            return;
        }

        if ($this->tableExists($newBaseTableBackup)) {
            echo "Cannot execute task. $newBaseTableBackup already exists. Remove or rename!";
            return;
        }


        //select specific fields to copy
        $obj = $newBaseClass::create();
        $migrationFields = array_merge(
            array('ID' => true),
            $obj->database_fields($newBaseClass)
        );


        //check the CalendarDateTime expected fields (to move & remove) exist before proceeding
        $columns = DB::query("SHOW COLUMNS FROM $original")->column();


        if (!$this->anyFieldsExist($migrationFields, $columns)) {
            //fields already removed
            echo "Task already ran!";
            return;
        }


        //create backups of the tables before operating
        $this->copyTable($original, $backup);
        echo "Table $original backed-up to $backup <br />";

        $this->copyTable($newBaseClass, $newBaseTableBackup);
        echo "Table $newBaseClass backed-up to $newBaseTableBackup <br />";


        //copy specific fields from records to $newBaseClass
        echo "Populating new table $newBaseClass <br />";
        $migrationFieldsStr = implode(", ", array_keys($migrationFields));
        DB::query("INSERT $newBaseClass SELECT $migrationFieldsStr FROM $original");


        //remove the columns that have been moved to the new base class
        foreach ($columns as $column) {
            if ($column === 'ID') continue;
            elseif (isset($migrationFields[$column])) $this->removeColumn($original, $column);
                //remove unwanted columns from $original

        }

        echo "Task complete! <br />?undo=1 to restore tables";

    }



    private function anyFieldsExist($fields, $columns)
    {
        foreach ($columns as $column) {
            if ($column === 'ID') continue;
            elseif (isset($fields[$column])) return true;
        }
		return false;
    }



    private function tableExists($table)
    {
        $tableExists = DB::query("SHOW TABLES LIKE '$table'")->value();
        if ($tableExists != null) return true;
        return false;
    }



    private function copyTable($original, $destination)
    {
        echo "Creating $destination <br />";
        DB::query("CREATE TABLE $destination LIKE $original");
        DB::query("INSERT $destination SELECT * FROM $original");
    }



    private function removeTable($table)
    {
        echo "Removing table $table <br />";
        DB::query("DROP TABLE $table");
    }



    private function removeColumn($table, $column)
    {
        echo "Dropping $column from $table <br />";
        DB::query("ALTER TABLE $table DROP COLUMN $column");
    }



    private function restoreTables($tables, $backupPostfix)
    {
        foreach ($tables as $table) {
            $backup = $table . $backupPostfix;
            if ($this->tableExists($backup)) {
                echo "Restoring $table <br />";
                //remove existing (unwanted) table & replace with the backup
                if ($this->tableExists($table)) $this->removeTable($table);
                $this->copyTable($backup, $table);
                $this->removeTable($backup);	//delete old backup
            }
            else echo "Cannot restore $table,  $backup does not exist.<br />";
        }

        echo "Tables restored.";
    }


}
