<?php

namespace App\Http\Responses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ApiModelResponse
{
    public $message;
    public $model;
    protected $statusCode;

    public function __construct(string $message, Collection|Model|array $model, int $statusCode)
    {
        $this->message = $message;
        $this->model = $model;
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray()
    {
        $data = [];

        if ($this->model instanceof Collection) {
            $data = $this->handleCollection($this->model);
        } elseif (is_array($this->model)) {
            $data = $this->handleModelArray($this->model);
        } elseif ($this->model instanceof Model) {
            $data = $this->handleSingleModel($this->model);
        } else {
            $data = $this->handleOtherData($this->model);
        }

        return [
            'message' => $this->message,
            'data' => $data,
        ];
    }

    protected function handleCollection(Collection $collection): array
    {
        if ($collection->isNotEmpty() && $collection->first() instanceof Model) {
            $tableName = $collection->first()->getTable();
            return [$tableName => $collection->toArray()];
        } else {
            return ['data' => $collection->toArray()];
        }
    }

    protected function handleModelArray(array $models): array
    {
        foreach ($models as $model) { 
            if(count($model) > 0 && $model[0] instanceof Model)
            {
                $tableName = $model->getTable();
                $data = [];
                $data[$tableName][] = $model->toArray();
            }
        }
        return $data;
    }

    protected function handleSingleModel(Model $model): array
    {
        $tableName = $model->getTable();
        return [$tableName => $model->toArray()];
    }

    protected function handleOtherData($data): array
    {
        return ['data' => $data];
    }

    public function getModel()
    {
        return $this->model;
    }
}