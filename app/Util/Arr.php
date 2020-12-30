<?php

namespace App\Util;

class Arr
{
    /**
     * 获取树形结构
     * @param array $data
     * @param int $increment
     * @param string $id
     * @param string $pid
     * @param string $nodes
     * @return array
     */
    public static function getTree(
        array $data,
        int $increment = 0,
        string $id = 'id',
        string $pid = 'pid',
        string $nodes = 'children'
    ): array {
        $ret = [];
        foreach ($data as $key => $value) {
            if ($value[$pid] === $increment) {
                $value[$nodes] = self::getTree($data, $value['id'], $id, $pid, $nodes);
                $ret[] = $value;
            }
        }
        return $ret;
    }

    /**
     * 二维数组根据指定KEY排序
     * @param array $array
     * @param string $key
     * @param int $sort
     * @return array
     */
    public static function arraySort(array $array, string $key, int $sort = SORT_DESC): array
    {
        $return = [];
        foreach ($array as $k => $v) {
            $return[$k] = $v[$key];
        }
        array_multisort($return, $sort, $array);
        return $array;
    }
}
