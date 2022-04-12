<?php

namespace App\Util;

use App\Http\Response\ApiCode;
use Carbon\Carbon;
use ErrorException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NginxLog
{
    private mixed $filePath;

    /**
     * NginxLog constructor.
     * @param $filePath
     * @throws FileNotFoundException
     */
    public function __construct($filePath)
    {
        $this->setFilePath($filePath);
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        $logs = Storage::disk('logs')->get($this->getFilePath());
        $collect = collect(explode("\n", $logs));
        return $collect
            ->filter(function ($value) {
                return $value !== '';
            })
            ->map(function ($item) {
                $string = Str::replaceArray('- ', [''], $item);
                $keywords = preg_split("/[\"*]/", $string);
                $array = [];
                foreach ($keywords as $key => $keyword) {
                    switch ($key) {
                        case 0:
                            $keyword = trim(str_replace(['[', ']'], '', $keyword));
                            list($ip, $user, $time, $timeZone) = explode(' ', $keyword);
                            $array['ip'] = $ip;
                            $array['user'] = $user;
                            $array['time'] = Carbon::create($time . ' ' . $timeZone)->format('Y-m-d H:i:s');
                            break;
                        case 1:
                            try {
                                list($method, $uri, $http) = explode(' ', trim($keyword));
                                $array['method'] = $method;
                                $array['uri'] = $uri;
                                $array['http'] = $http;
                            } catch (ErrorException) {
                                $array['method'] = $keyword;
                                $array['uri'] = $keyword;
                                $array['http'] = $keyword;
                            }
                            break;
                        case 2:
                            try {
                                list($httpCode, $size) = explode(' ', trim($keyword));
                                $array['http_code'] = $httpCode;
                                $array['size'] = $size;
                            } catch (ErrorException) {
                                $array['http_code'] = 'UnKnow';
                                $array['size'] = 'UnKnow';
                            }
                            break;
                        case 3:
                            $array['return'] = $keyword;
                            break;
                        case 5:
                            $array['user_agent'] = $keyword;
                            break;
                        default:
                            $array[] = '';
                    }
                }
                $array['string'] = $item;
                return array_filter($array);
            })
            ->reverse()
            ->values()
            ->map(function ($item) {
                $ua = new UserAgent($item['user_agent'] ?? '');
                $item['user_agent_info']['platform'] = $ua->platform();
                $item['user_agent_info']['browser'] = $ua->browser();
                $item['user_agent_info']['version'] = $ua->version();
                $item['is_robot'] = $ua->isRobot();
                $item['is_mobile'] = $ua->isMobile();
                return $item;
            })
            ->map(function ($item) {
                if (isset($item['uri'])) {
                    $item['is_warning'] = !(Str::startsWith($item['uri'], ['/api', '/static']) || $item['uri'] === '/');
                    return $item;
                } else {
                    $item['is_warning'] = true;
                }
                return $item;
            })
            ->map(function ($item) {
                $item['is_error'] = !ApiCode::isSuccessful($item['http_code']);
                return $item;
            });
    }

    /**
     * @return Collection
     */
    public function first(): Collection
    {
        return $this->find(0);
    }

    /**
     * @param int $key
     * @return Collection
     */
    public function find(int $key): Collection
    {
        return collect($this->get()->get($key));
    }

    /**
     * @return mixed
     */
    public function getFilePath(): mixed
    {
        return $this->filePath;
    }

    /**
     * @param mixed $filePath
     * @return void
     * @throws FileNotFoundException
     */
    private function setFilePath(mixed $filePath)
    {
        if (Storage::disk('logs')->get($filePath) === null) {
            throw new FileNotFoundException(__('message.file.not_found'));
        }
        $this->filePath = $filePath;
    }
}
