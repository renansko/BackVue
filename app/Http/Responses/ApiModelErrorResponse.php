<?php

namespace App\Http\Responses;

use Exception;
use Illuminate\Database\Eloquent\Model;

class ApiModelErrorResponse
{
    public $message;

    public $model;

    public $exceptions;

    protected $statusCode;

    public function __construct(string $message, Exception $exceptions, Model|array $model, int $statusCode)
    {
        $this->message = $message;
        $this->model = $model;
        $this->statusCode = $statusCode;
        $this->exceptions = $exceptions;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray()
    {
        // Nome da tabela se for um model retorna no sigular, se for qualquer outra coisa tablename vira 'data'
        $tableName = $this->model instanceof Model ? $this->model->getTable() : 'data';
        $data = [];

        // Retorna um array com varios models
        if (is_array($this->model)) {
            foreach ($this->model as $model) {
                if (count($this->model) > 0 && isset($this->model[0]) instanceof Model) {
                    $tableName = $model->getTable();
                    $modelData = $model->toArray();

                    $data[$tableName][] = $modelData;

                }
            }
        } else {
             // Retorna um objeto com 1 model
            $modelData = $this->model instanceof Model ? $this->model->toArray() : $this->model;

            $data[$tableName] = $modelData;
        }
        if (env("APP_DEBUG") === true) {
            return [
                'message' => $this->message,
                'error' => substr($this->exceptions->getMessage(), 0, 255),
                'data' => $data,
            ];
        } else {
            return [
                'message' => $this->message,
                'data' => $data,
            ];
        }
    }
}