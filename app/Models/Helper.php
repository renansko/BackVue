<?php

namespace App\Models;


use App\Http\Responses\ApiModelErrorResponse;
use App\Http\Responses\ApiModelResponse;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class Helper
{
    public static function filtrar($filtroRequest, $query, Model $model, bool $returnQuery = false, bool $passarArrayJaValidada = false, string $nome = null)
    {
        if(!$passarArrayJaValidada){
            $filtroRequest->validated();
            $filtroRequest = $filtroRequest->except('page');
            
        }
        foreach ($filtroRequest as $chave => $valor) {
            $filtros[] = ['chave' => $chave, 'valor' => $valor];
        }
        

        if (isset($filtros) && count($filtros) > 0) {
            foreach ($filtros as $filtro) {
                 $response = self::keyValueWhere($query, $filtro['chave'], $filtro['valor'], $model);
            }

            if ($response instanceof ApiModelErrorResponse) {
                return response()->json($response->toArray(), $response->getStatusCode());
            }

            $data = $response->orderByDesc('created_at')->paginate(20);
            if($returnQuery){
                return $data;
            }
            $response = new ApiModelResponse('{$nome} fitlrado com sucesso', $model, 200);
        }
        $response = $query->orderByDesc('created_at')->paginate(20);
        if($returnQuery){
            return $response;
        }
        $response = new ApiModelResponse('{$nome} fitlrado com sucesso', $model, 200);
    }

    public function keyValueWhere($query, $chave, $valor, $model)
    {
        try {
            Log::info($model::class . ' :Procurando registro por {$chave} :function-filtrar', ['filtro' => $valor]);

            $query = $query->where(
                $chave,
                '=',
                $valor,
            );

            if ($query->get()->isEmpty()) {
                Log::error($model::class . ' :Registro não encontrado! :function-filtrar', ['filtro' => $valor]);
                return new ApiModelErrorResponse(
                   'Não foi possivel encontrar o '. $model->getNomeModel()  . ' com o valor: ' . $valor,
                    new NotFoundResourceException('Não foi encontrado '. $model->getNomeModel()  .' com esse filtrar'),
                     [],
                    200,
                );
            }
            Log::info($model::class . ' :Foi encontrado registros com {$chave} para filtrar :function-filtrar');

            return $query;

        } catch (Exception $e) {
            return new ApiModelErrorResponse(
                'Não foi possivel encontrar o '. $model->getNomeModel()  .' com esse filtro',
                $e,
                $model,
                500,
            );
        }
    }

}