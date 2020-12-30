<?php

namespace App\Util;

use App\Http\Requests\Admin\FileSystem\UploadRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem as DiskFileSystem;
use Illuminate\Support\Str;

/**
 * Class FileSystem
 * @package App\Util
 */
class FileSystem
{
    /**
     * 目录路径
     * @var string
     */
    private $directory = '/';

    /**
     * 驱动
     * @var
     */
    private $disk;

    /**
     * 驱动名称
     * @var string
     */
    private $diskName = 'public';

    /**
     * FileSystem constructor.
     * @param string $directory
     * @param string $diskName public
     */
    public function __construct(string $directory, string $diskName = 'public')
    {
        $this->setDirectory($directory);
        $this->setDiskName($diskName);
        $this->setDisk();
    }

    /**
     * 获取列表
     * @param int $offset 起始页
     * @param int $length 结束页
     * @param string|null $search 搜索项
     * @return Collection 返回集合
     */
    public function lists(int $offset = 0, int $length = 100, string $search = null): Collection
    {
        $data = $this->directories()->merge($this->files());
        if ($search !== null && $search !== '') {
            $data = $data->filter(function ($value) use ($search) {
                return Str::contains($value['name'], $search);
            });
        }
        $total = $data->count();
        $data = $data->slice($offset, $length);
        if ($this->getDirectory() !== '/') {
            $name = array_filter(explode('/', $this->getDirectory()));
            array_unshift($name, '/');
            $data->prepend([
                'type' => 'path',
                'size' => '',
                'name' => $name,
                'lastModified' => '',
                'pathinfo' => [
                    'extension' => 'path'
                ]
            ]);
        }
        return collect([
            'total' => $total,
            'data' => $data->values()->toArray()
        ]);
    }

    /**
     * 文件列表
     *
     * @return Collection
     */
    public function files(): Collection
    {
        $disk = $this->getDisk();
        $fileArray = $disk->files($this->getDirectory());
        $files = [];
        foreach ($fileArray as $file) {
            $files[] = [
                'type' => 'file',
                'size' => $this->formatBytes($disk->size($file)),
                'name' => $file,
                'url' => asset('storage/' . $file),
                'lastModified' => Carbon::parse($disk->lastModified($file))->toDateTimeString(),
                'pathinfo' => pathinfo($file)
            ];
        }
        $files = collect($files);
        $files = $files->reject(function ($value) {
            return $value['name'] === '.gitignore';
        })->values();
        return $files;
    }

    /**
     * 文件夹列表
     *
     * @return Collection
     */
    public function directories(): Collection
    {
        $directoryArray = $this->getDisk()->directories($this->getDirectory());
        $directories = [];
        foreach ($directoryArray as $directory) {
            $directory = explode('/', $directory);
            $directories[] = [
                'type' => 'directory',
                'size' => '',
                'name' => end($directory) . '/',
                'lastModified' => '',
                'pathinfo' => [
                    'extension' => 'folder'
                ]
            ];
        }
        $directories = collect($directories);
        return $directories;
    }

    /**
     * 创建文件
     *
     * @param string $directory
     * @return bool
     */
    public function makeDirectory(string $directory): bool
    {
        return $this->getDisk()->makeDirectory($directory);
    }

    /**
     * 删除文件
     *
     * @param string $directory
     * @return bool
     */
    public function deleteDirectory(string $directory): bool
    {
        return $this->getDisk()->deleteDirectory($directory);
    }

    /**
     * 上传文件
     *
     * @param UploadRequest $request
     * @param string $key
     * @return mixed
     */
    public function putFile(UploadRequest $request, string $key = 'file')
    {
        return $this->getDisk()->putFile($this->getDirectory(), $request->file($key));
    }

    /**
     * 指定文件名上传文件
     *
     * @param UploadRequest $request
     * @param string $key
     * @param string|null $fileName
     * @return mixed
     */
    public function putFileAs(UploadRequest $request, string $key = 'file', string $fileName = null)
    {
        $file = $request->file($key);
        if ($fileName === '' || $fileName === null) {
            $pathInfo = pathinfo($file->getClientOriginalName());
            $fileName = $pathInfo['filename'];
        }
        return $this->getDisk()->putFileAs($this->getDirectory(), $file, $fileName);
    }

    /**
     * 下载文件(流)
     *
     * @param string $file
     * @return mixed
     */
    public function download(string $file)
    {
        return $this->getDisk()->download($file);
    }

    /**
     * 删除文件
     *
     * @param string|array $paths
     * @return bool
     */
    public function delete($paths)
    {
        return $this->getDisk()->delete($paths);
    }

    /**
     * @param int $size
     * @return string
     */
    private function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size >= 1024 && $i < 4; $i++) {
            $size /= 1024;
        }
        return round($size, 3) . $units[$i];
    }

    /**
     * @return string
     */
    private function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     * @return FileSystem
     */
    private function setDirectory(string $directory): FileSystem
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDisk(): DiskFileSystem
    {
        return $this->disk;
    }

    /**
     * @return FileSystem
     */
    private function setDisk(): FileSystem
    {
        $this->disk = Storage::disk($this->getDiskName());
        return $this;
    }

    /**
     * @return string
     */
    private function getDiskName(): string
    {
        return $this->diskName;
    }

    /**
     * @param string $diskName
     * @return FileSystem
     */
    private function setDiskName(string $diskName): FileSystem
    {
        $this->diskName = $diskName;
        return $this;
    }
}
