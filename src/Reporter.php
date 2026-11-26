<?php

namespace LaveroAdmin\Admin\Reporter;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use LaveroAdmin\Admin\Extension;
use Throwable;

class Reporter extends Extension
{
    use BootExtension;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Reporter constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param Throwable $exception
     *
     * @return void
     */
    public static function report(Throwable $exception)
    {
        $reporter = new static(request());

        $reporter->reportException($exception);
    }

    protected const SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'passwd',
        'pwd',
        'secret',
        'token',
        'api_key',
        'apikey',
        'authorization',
        'cookie',
        'cvv',
        'cvc',
        'credit_card',
        'card_number',
        'private_key',
    ];

    /**
     * @param Throwable $exception
     *
     * @return void
     */
    public function reportException(Throwable $exception)
    {
        $rawBody = $this->request->getContent();
        $decodedBody = json_decode((string) $rawBody, true);
        $safeBody = is_array($decodedBody)
            ? json_encode($this->sanitizeData($decodedBody), JSON_UNESCAPED_UNICODE)
            : $rawBody;

        $data = [

            // Request info.
            'method'    => $this->request->getMethod(),
            'ip'        => $this->request->getClientIps(),
            'path'      => $this->request->path(),
            'query'     => $this->sanitizeData(Arr::except($this->request->all(), ['_pjax', '_token', '_method', '_previous_'])),
            'body'      => $safeBody,
            'cookies'   => $this->sanitizeData($this->request->cookies->all()),
            'headers'   => $this->sanitizeData(Arr::except($this->request->headers->all(), 'cookie')),

            // Exception info.
            'type'        => get_class($exception),
            'fingerprint' => hash('sha256', get_class($exception) . '|' . $exception->getFile() . '|' . $exception->getLine()),
            'code'        => $exception->getCode(),
            'file'        => $exception->getFile(),
            'line'        => $exception->getLine(),
            'message'     => $exception->getMessage(),
            'trace'       => $exception->getTraceAsString(),
        ];

        $data = $this->stringify($data);

        try {
            $this->store($data);
        } catch (Throwable $e) {
        }
    }

    public function sanitizeData(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $lower = strtolower((string) $key);
            $isSensitive = false;
            foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
                if (str_contains($lower, $fragment)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $result[$key] = '[REDACTED]';
                continue;
            }

            $result[$key] = is_array($value) ? $this->sanitizeData($value) : $value;
        }

        return $result;
    }

    /**
     * Convert all items to string.
     *
     * @param $data
     *
     * @return array
     */
    public function stringify($data): array
    {
        return array_map(function ($item) {
            return is_array($item) ? json_encode($item, JSON_OBJECT_AS_ARRAY) : (string) $item;
        }, $data);
    }

    /**
     * Store exception info to db.
     *
     * @param array $data
     *
     * @return bool
     */
    public function store(array $data): bool
    {
        try {
            $model = new ExceptionModel();
            $connection = $model->getConnectionName() ?: config('admin.database.connection') ?: config('database.default');
            $table = $model->getTable();
            $hasGrouping = \Illuminate\Support\Facades\Schema::connection($connection)->hasColumn($table, 'fingerprint');

            if ($hasGrouping && !empty($data['fingerprint'])) {
                $existing = ExceptionModel::query()
                    ->where('fingerprint', $data['fingerprint'])
                    ->where('status', 'open')
                    ->first();

                if ($existing) {
                    $existing->occurrences = (int) $existing->occurrences + 1;
                    $existing->last_seen_at = now();
                    $existing->message = $data['message'];
                    $existing->trace = $data['trace'];
                    return $existing->save();
                }

                $data['occurrences'] = 1;
                $data['status'] = 'open';
                $data['last_seen_at'] = now();
            } else {
                unset($data['fingerprint']);
            }

            ExceptionModel::query()->create($data);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
