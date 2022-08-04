<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FileSystem\FileRequest;
use App\Http\Requests\Admin\GenTable\CreateRequest;
use App\Http\Requests\Admin\GenTable\GenRequest;
use App\Http\Requests\Admin\GenTable\GetListRequest;
use App\Http\Requests\Admin\GenTable\IdRequest;
use App\Http\Requests\Admin\GenTable\UpdateRequest;
use App\Http\Response\ApiCode;
use App\Models\DictData;
use App\Models\DictType;
use App\Models\GenTable;
use App\Models\Permission;
use App\Util\Gen;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Support\Facades\DB;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GenTableController extends Controller
{
    /**
     * 列表
     *
     * @param GetListRequest $request
     * @return Response
     */
    public function list(GetListRequest $request): Response
    {
        $validated = $request->validated();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData(GenTable::list($validated))
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 下拉
     *
     * @return Response
     */
    public function select(): Response
    {
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'data' => GenTable::selectAll()
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 详情
     *
     * @param IdRequest $request
     * @return Response
     */
    public function info(IdRequest $request): Response
    {
        $validated = $request->validated();
        $info = GenTable::find($validated['id']);
        $info->genTableColumns;
        $info->pid = [0];
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($info)
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 详情
     *
     * @return Response
     */
    public function columnConfig(): Response
    {
        $select = [Gen::SELECT_EQ, Gen::SELECT_NE, Gen::SELECT_GT, Gen::SELECT_GE, Gen::SELECT_LT, Gen::SELECT_LE, Gen::SELECT_LIKE, Gen::SELECT_BETWEEN];
        $type = [Gen::TYPE_INPUT_TEXT, Gen::TYPE_INPUT_TEXTAREA, Gen::TYPE_SELECT, Gen::TYPE_RADIO, Gen::TYPE_DATE, Gen::TYPE_FILE, Gen::TYPE_IMAGE, Gen::TYPE_EDITOR];
        $dict = DictType::selectAll();
        $dictData = DictData::selectAll();
        $permission['id'] = 0;
        $permission['icon'] = 'el-icon-star-on';
        $permission['name'] = 'top';
        $permission['pid'] = 0;
        $permission['title'] = __('message.gen.top_nav');
        $children = Permission::tree(['guard_name' => 'admin', 'hidden' => 0]);
        $permission['children'] = $children;
        $doctrineSchemaManager = DB::connection()->getDoctrineSchemaManager();
        try {
            $tables = array_values(array_filter(array_map(function (Table $table) use ($doctrineSchemaManager) {
                $db = [];
                $name = $table->getName();
                $db['name'] = $name;
                foreach ($table->getOptions() as $key => $option) {
                    $db[$key] = $option;
                }
                $db['info'] = Gen::getTableInfo($name);
                return $db;
            }, $doctrineSchemaManager->listTables())));
        } catch (Exception) {
            $tables = [];
        }
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'select' => $select,
                'type' => $type,
                'dict' => $dict,
                'dictData' => $dictData,
                'permission' => [$permission],
                'tables' => $tables
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 创建
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $validated = $request->validated();
        $table = $validated['table'];
        $error = [];
        foreach ($table as $item) {
            try {
                GenTable::importTable($item);
            } catch (Throwable $e) {
                $error[] = $item . ' ' . $e->getMessage();
            }
        }
        if (count($error) > 0) {
            $message = implode(" | ", $error);
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($message)
                ->build();
        }
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withMessage(__('message.common.create.success'))
            ->build();
    }

    public function importTable(): Response
    {
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData([
                'list' => GenTable::getImportTableList()
            ])
            ->withMessage(__('message.common.search.success'))
            ->build();
    }

    /**
     * 更新
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $validated = $request->validated();
        $model = GenTable::find($validated['id']);
        $model->update($validated);
        $model->genTableColumns()->delete();
        $model->genTableColumns()->insert($validated['gen_table_columns']);
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withData($model)
            ->withMessage(__('message.common.update.success'))
            ->build();
    }

    /**
     * 删除
     *
     * @param IdRequest $request
     * @return Response
     */
    public function delete(IdRequest $request): Response
    {
        $validated = $request->validated();
        GenTable::where('id', $validated['id'])->delete();
        return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
            ->withHttpCode(ApiCode::HTTP_OK)
            ->withMessage(__('message.common.delete.success'))
            ->build();
    }

    public function gen(GenRequest $request): Response
    {
        $validated = $request->validated();
        $pid = (array)$validated['pid'];
        if (empty($pid)) {
            $pid = 0;
        } else {
            $pid = end($pid);
        }
        try {
            $path = GenTable::gen($validated['name'], $pid, $validated['comment']);
            return ResponseBuilder::asSuccess(ApiCode::HTTP_OK)
                ->withHttpCode(ApiCode::HTTP_OK)
                ->withData([
                    'path' => $path
                ])
                ->withMessage(__('message.common.create.success'))
                ->build();
        } catch (\Exception $e) {
            return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
                ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function download(FileRequest $request): Response
    {
        $validated = $request->validated();
        $file = $validated['file'];
        if (file_exists($file)) {
            return response()->download($file, basename($file));
        }
        return ResponseBuilder::asError(ApiCode::HTTP_BAD_REQUEST)
            ->withHttpCode(ApiCode::HTTP_BAD_REQUEST)
            ->withMessage(__('message.file.not_found'))
            ->build();
    }
}
