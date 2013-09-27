<?php

/*
* @copyright  Copyright (c) 2013 by  ESS-UA.
*/

class Ess_M2ePro_Model_Upgrade_RepairTables extends Ess_M2ePro_Model_Abstract
{
    const SHORTCUT_MODE_ON = true;
    const SHORTCUT_MODE_OFF = false;
    const PREFIX = 'm2epro_';

    //####################################

    public function getBrokenTables($mode = self::SHORTCUT_MODE_OFF, array $tables = array())
    {
        $allTables = Mage::helper('M2ePro/Module')->getHorizontalTables();
        if (empty($tables)) {
            $tables = Mage::helper('M2ePro/Module')->getMySqlTables();
        }
        $result = array();

        foreach ($allTables as $parentTable => $childs) {

            $parentWitchPref = Mage::getSingleton('core/resource')->getTableName($parentTable);
            $column = str_replace(self::PREFIX,'',$parentTable) . '_id';

            foreach ($childs as $componentMode => $childTable) {

                if (!empty($tables) and !in_array($childTable,$tables) and !in_array($parentTable,$tables)) {
                    continue;
                }

                $childWitchPref = Mage::getSingleton('core/resource')->getTableName($childTable);
                $query = "SELECT `parent`.`id`, `child`.`{$column}`
                           FROM (
                                SELECT `id`
                                FROM `{$parentWitchPref}`
                                WHERE `component_mode` = '{$componentMode}'
                                ) as `parent`
                           LEFT JOIN `{$childWitchPref}` as `child`
                           ON `parent`.`id` = `child`.`{$column}`
                           WHERE `child`.`{$column}` IS NULL
                           UNION
                           SELECT `parent`.`id`, `child`.`{$column}`
                           FROM (
                                SELECT `id`
                                FROM `{$parentWitchPref}`
                                WHERE `component_mode` = '{$componentMode}'
                                ) as `parent`
                           RIGHT JOIN `{$childWitchPref}` as `child`
                           ON `parent`.`id` = `child`.`{$column}`
                           WHERE `parent`.`id` IS NULL";

                $data = Mage::getSingleton('core/resource')->getConnection('core_read')->query($query);
                $data->setFetchMode(PDO::FETCH_ASSOC);

                while ($row = $data->fetch()) {

                    if ($row['id'] == '' and in_array($childTable, $tables)) {
                        if ($mode == self::SHORTCUT_MODE_OFF) {
                            $result['childs'][$childTable][] = (int)$row[$column];
                        } else {
                            $result[$childTable][] = (int)$row[$column];
                        }
                    } elseif ($row[$column] == '' and in_array($parentTable, $tables)) {
                        if ($mode == self::SHORTCUT_MODE_OFF) {
                            $result['parents'][$parentTable][$componentMode][] = (int)$row['id'];
                        } else {
                            $result[$parentTable][] = (int)$row['id'];
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function renderBrokenTables()
    {
        $broken = $this->getBrokenTables();

        if (empty($broken)) {
            echo 'All is OK. Tables are synchronized';
            exit();
        }

        $message = Mage::helper('M2ePro')->getSessionValue('message');
        if (!empty($message)) {
            $message = "<div class='container success'>
                            {$message}
                        </div>";
        }

        $components = Mage::helper('M2ePro/Component')->getAllowedComponents();
        $colspan = count ($components);
        $columns = $colspan + 2;
        $url = Mage::helper('adminhtml')->getUrl('*/*/*').'?table[]=';

        $legendComponents = '';
        $parentsBlock = '';
        $childsBlock = '';

        foreach ($components as $component) {
            $legendComponents .= "<td width='85' class='center'><b>{$component}</b></td>";
        }

        $parentHead = <<<PARENT_HEAD
        <tr>
            <td width='450'><span class='blue'><b>Parent Tables</b></span></td>
        </tr>
        <tr class='legend'>
            <td><b>Name</b></td>
            {$legendComponents}
            <td width='85'></td>
        </tr>
PARENT_HEAD;

        $childHead = <<<CHILD_HEAD
        <tr>
            <td><span class=blue><b>Child Tables</b></span></td>
        </tr>
        <tr class='legend'>
            <td width=450><b>Name</b></td>
            <td colspan='{$colspan}' class='center'><b>Count</b></td>
            <td></td>
        </tr>
CHILD_HEAD;

        if (isset($broken['parents'])) {
            $parentsBlock .= $parentHead;

            foreach ($broken['parents'] as $parentTable => $data) {

                $rowComponent = '';
                foreach ($components as $component) {

                    if (isset($data[$component])) {
                        $countsByComponent = count($data[$component]);
                    } else {
                        $countsByComponent = 0;
                    }

                    $rowComponent .= "<td class='center'>{$countsByComponent}</td>";
                }

                $row = <<<ROW
        <tr>
            <td>{$parentTable}</td>
            {$rowComponent}
            <td class='center'>
                <input type='button' value='Repair' name='table[]' onclick = "location.href='{$url}{$parentTable}'">
            </td>
        </tr>
ROW;
                $parentsBlock .= $row;
            }
        }

        if (isset($broken['childs'])) {

            $childsBlock .= $childHead;
            foreach ($broken['childs'] as $childTable => $data) {

                $counts = count($data);
                $row = <<<ROW
        <tr>
            <td>{$childTable}</td>
            <td colspan='{$colspan}' class='center'>{$counts}</td>
            <td class='center'>
                <input type='button' value='Repair' name='table[]' onclick = "location.href='{$url}{$childTable}'">
            </td>
        </tr>
ROW;
                $childsBlock .= $row;
            }
        }

        echo $html = <<<TEST
        <html>
            <head>
                <style>
                    .center {
                        text-align: center;
                    }
                    .blue {
                        color: #586A88;
                    }
                    .container {
                        margin-bottom: 15px;
                        padding: 10px 15px;
                        font-size: 14px;
                        text-align: left;
                    }
                    .success {
                        background-color: #ebfcdd;
                        border: 1px solid #5aed16;
                    }
                    .legend {
                        background-color: #F8F8F8;
                    }
                </style>
            </head>
            <body>
                {$message}
                <table>
                    {$parentsBlock}
                    <tr>
                        <td colspan='{$columns}'><hr/></td>
                    </tr>
                    {$childsBlock}
                </table>
            </body>
        </html>
TEST;

        Mage::helper('M2ePro')->setSessionValue('message', '');
    }

    public function repairBrokenTables(array $tableNames)
    {
        $data = $this->getBrokenTables(self::SHORTCUT_MODE_ON, $tableNames);

        foreach ($data as $table => $ids) {

            $tableWitchPref = Mage::getSingleton('core/resource')->getTableName($table);
            $str = implode (',',$ids);
            $counts = count ($ids);

            $column = str_replace(self::PREFIX,'',$table) . '_id';
            $arr = explode('_',$column);
            unset($arr[0]);
            $column = implode('_',$arr);

            $query = "DELETE FROM `{$tableWitchPref}`
                          WHERE `{$column}` IN ({$str})";

            $result = Mage::getSingleton('core/resource')->getConnection('core_write')->query($query);

            $log = "
                        Table: {$table} ## Counts: $counts
                        Query: {$query}";

            Mage::log($log, null, "repair_tables.log");
            $filename = Mage::getBaseDir('log') . ' repair_tables.log';
            Mage::helper('M2ePro')->setSessionValue('message', "Repaired <b>{$table}</b>.
                                                                    Deleted <b>{$counts}</b> id.
                                                                    Logged to {$filename}");
        }
    }

    //####################################
}