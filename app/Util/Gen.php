<?php

namespace App\Util;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Gen
{
    const SELECT_EQ = '=';
    const SELECT_NE = '!=';
    const SELECT_GT = '>';
    const SELECT_GE = '>=';
    const SELECT_LT = '<';
    const SELECT_LE = '<=';
    const SELECT_LIKE = 'LIKE';
    const SELECT_BETWEEN = 'BETWEEN';

    const TYPE_INPUT_TEXT = 'text';
    const TYPE_INPUT_TEXTAREA = 'textarea';
    const TYPE_SELECT = 'select';
    const TYPE_RADIO = 'radio';
    const TYPE_DATE = 'date';
    const TYPE_FILE = 'file';
    const TYPE_IMAGE = 'image';
    const TYPE_EDITOR = 'editor';

    /**
     * 获取数据库表
     *
     * @return array<string, mixed>
     */
    public static function getTableList(): array
    {
        $doctrineSchemaManager = DB::connection()->getDoctrineSchemaManager();
        $tables = [];
        try {
            $tables = $doctrineSchemaManager->listTables();
        } catch (Exception) {
        }

        return array_values(array_filter(array_map(function (Table $table) {
            $db = [];
            try {
                $table->getPrimaryKeyColumns();
                $name = $table->getName();
                $db['name'] = $name;
                foreach ($table->getOptions() as $key => $option) {
                    $db[$key] = $option;
                }
            } catch (Exception) {

            }
            return $db;
        }, $tables)));
    }

    /**
     * 获取数据库详情
     *
     * @param string $table
     * @return array
     */
    public static function getTableInfo(string $table): array
    {
        $doctrineSchemaManager = DB::connection()->getDoctrineSchemaManager();
        try {
            $exist = $doctrineSchemaManager->tablesExist($table);
            if ($exist) {
                $table = $doctrineSchemaManager->listTableDetails($table);
                $info['name'] = $table->getName();
                foreach ($table->getOptions() as $key => $option) {
                    $info[$key] = $option;
                }

                $primaryKeyColumns = array_keys($table->getPrimaryKeyColumns());
                $info['columns'] = array_values(array_map(function (Column $column) use ($primaryKeyColumns) {
                    $type = $column->getType()->getName();
                    $c = $column->toArray();
                    $c['type'] = $type;
                    $c['primary'] = in_array($column->getName(), $primaryKeyColumns);
                    return $c;
                }, $table->getColumns()));
                $info['indexes'] = array_values(array_map(function (Index $index) {
                    $i['name'] = $index->getName();
                    $i['columns'] = $index->getColumns();
                    return $i;
                }, $table->getIndexes()));
                $info['foreign_keys'] = array_values(array_map(function (ForeignKeyConstraint $constraint) {
                    $c['name'] = $constraint->getName();
                    $c['local_columns'] = $constraint->getUnquotedLocalColumns();
                    $c['unqualified_foreign_table_name'] = $constraint->getUnqualifiedForeignTableName();
                    $c['unqualified_foreign_columns'] = $constraint->getUnquotedForeignColumns();
                    $c['on_delete'] = $constraint->getOption('onDelete');
                    $c['on_update'] = $constraint->getOption('onUpdate');
                    return $c;
                }, $table->getForeignKeys()));
            } else {
                $info['exception'] = 'table does not exist';
            }
        } catch (Exception $e) {
            $info['exception'] = $e->getMessage();
        }
        return $info;
    }

    public static function getTableConfig(string $table): array
    {
        $info = Gen::getTableInfo($table);
        if (isset($info['exception'])) {
            return $info;
        }

        $columns = $info['columns'];

        $uniques = array_values(array_map(function (array $column) {
            return $column['columns'][0];
        }, array_filter($info['indexes'], function (array $index) {
            return count($index['columns']) === 1 && Str::of($index['name'])->endsWith('_unique');
        })));

        $foreignKeys = array_values(array_map(function (array $foreign) {
            return [
                'local_column' => $foreign['local_columns'][0],
                '_foreign' => true,
                '_foreign_table' => $foreign['unqualified_foreign_table_name'],
                '_foreign_column' => $foreign['unqualified_foreign_columns'][0],
            ];
        }, array_filter($info['foreign_keys'], function (array $index) {
            return count($index['local_columns']) === 1 && count($index['unqualified_foreign_columns']) === 1;
        })));

        $columnsConfigData = array_map(function (array $column) use ($uniques, $foreignKeys) {

            $foreign = array_values(array_filter($foreignKeys, function (array $fk) use ($column) {
                return $fk['local_column'] === $column['name'];
            }));

            $insert = true;
            $update = true;
            $list = true;
            $select = true;
            // 如果是自增主键
            if ($column['primary'] && $column['autoincrement']) {
                $insert = false;
                $list = false;
                $select = false;
            }

            // 如果是时间
            if (in_array($column['name'], [Model::CREATED_AT, Model::UPDATED_AT])) {
                $insert = false;
                $update = false;
            }

            $query = Gen::query($column['type']);
            $validate = Gen::validate($column['type']);
            $show = Gen::show($column['name']);
            // 外键使用select
            if (count($foreign) === 1) {
                $show = Gen::TYPE_SELECT;
            }

            $column['_insert'] = $insert;
            $column['_update'] = $update;
            $column['_list'] = $list;
            $column['_select'] = $select;
            $column['_query'] = $query;
            $column['_required'] = $column['notnull'];
            $column['_show'] = $show;
            $column['_validate'] = $validate;
            $column['dict_type_id'] = null;
            $column['_unique'] = in_array($column['name'], $uniques);

            $column['_foreign'] = false;
            $column['_foreign_table'] = null;
            $column['_foreign_column'] = null;
            $column['_foreign_show'] = null;

            if (count($foreign) === 1) {
                $column['_foreign'] = true;
                $column['_foreign_table'] = $foreign[0]['_foreign_table'];
                $column['_foreign_column'] = $foreign[0]['_foreign_column'];
                $info = Gen::getTableInfo($foreign[0]['_foreign_table']);
                $foreignColumn = array_values(array_map(function (array $column): string {
                    return $column['name'];
                }, array_filter($info['columns'], function (array $column) use ($foreign): bool {
                    return $column['name'] !== $foreign[0]['_foreign_column'];
                })));
                if (count($foreignColumn) > 0) {
                    $column['_foreign_show'] = $foreignColumn[0];
                }
            }

            return $column;
        }, $columns);

        $info['columns'] = $columnsConfigData;

        return $info;
    }

    /**
     * 验证方式
     *
     * @param string $type
     * @return string
     */
    public static function validate(string $type): string
    {
        $types = [
            'integer' => [
                'integer', 'bigint'
            ],
            'string' => [
                'string', 'text'
            ],
            'numeric' => [
                'decimal', 'float'
            ],
            'date' => [
                'datetime', 'date'
            ],
            'boolean' => [
                'boolean'
            ]
        ];

        $r = 'string';
        foreach ($types as $select => $array) {
            if (in_array($type, $array)) {
                $r = $select;
                break;
            }
        }

        return $r;
    }

    /**
     * 查询方式
     *
     * @param string $type
     * @return string
     */
    public static function query(string $type): string
    {
        $types = [
            Gen::SELECT_EQ => [
                'integer', 'boolean', 'bigint'
            ],
            Gen::SELECT_LIKE => [
                'string', 'text'
            ],
            Gen::SELECT_BETWEEN => [
                'datetime', 'time', 'date'
            ]
        ];

        $r = Gen::SELECT_EQ;
        foreach ($types as $select => $array) {
            if (in_array($type, $array)) {
                $r = $select;
                break;
            }
        }

        return $r;
    }

    /**
     * 显示类型
     *
     * @param string $name
     * @return string
     */
    public static function show(string $name): string
    {
        $types = [
            Gen::TYPE_SELECT => [
                'sex', 'type'
            ],
            Gen::TYPE_RADIO => [
                'status'
            ],
            Gen::TYPE_DATE => [
                '_at', '_time'
            ],
            Gen::TYPE_FILE => [
                'file'
            ],
            Gen::TYPE_IMAGE => [
                'pic', 'image'
            ]
        ];

        $r = Gen::TYPE_INPUT_TEXT;
        foreach ($types as $select => $array) {
            foreach ($array as $item) {
                if (($name === $item) || Str::of($name)->endsWith($item)) {
                    $r = $select;
                    break;
                }
            }
        }

        return $r;
    }

    /**
     * @return string[][][]
     */
    public static function columnMethodAndType(): array
    {
        return [
            'methods' => [
                ['name' => '等于', 'value' => Gen::SELECT_EQ],
                ['name' => '不等于', 'value' => Gen::SELECT_NE],
                ['name' => '大于', 'value' => Gen::SELECT_GT],
                ['name' => '大于等于', 'value' => Gen::SELECT_GE],
                ['name' => '小于', 'value' => Gen::SELECT_LT],
                ['name' => '小于等于', 'value' => Gen::SELECT_LE],
                ['name' => '包含', 'value' => Gen::SELECT_LIKE],
                ['name' => '介于', 'value' => Gen::SELECT_BETWEEN],
            ],
            'types' => [
                ['name' => '文本框', 'value' => Gen::TYPE_INPUT_TEXT],
                ['name' => '文本域', 'value' => Gen::TYPE_INPUT_TEXTAREA],
                ['name' => '下拉框', 'value' => Gen::TYPE_SELECT],
                ['name' => '单选框', 'value' => Gen::TYPE_RADIO],
                ['name' => '日期控件', 'value' => Gen::TYPE_DATE],
                ['name' => '上传文件控件', 'value' => Gen::TYPE_FILE],
                ['name' => '上传图片控件', 'value' => Gen::TYPE_IMAGE],
                ['name' => '富文本', 'value' => Gen::TYPE_EDITOR],
            ]
        ];
    }
}
