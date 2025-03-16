<?php

namespace App\Domain\Services;

use App\Http\Responses\ApiModelErrorResponse;
use App\Http\Responses\ApiModelResponse;
use App\Models\Contact;
use App\Models\Company;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CompanyService
{
    public function searchCompanys(): ApiModelResponse|ApiModelErrorResponse
    {
        Log::info(Company::class . ' Searching all companys : function-searchCompanys');
        $companys = Company::all();

        if($companys->isEmpty()){
            Log::info(Company::class . ' No companys found : function-searchCompanys');
            return new ApiModelErrorResponse('No companys found', new Exception(), [], 404);
        }

        Log::info(Company::class . ' Found ' . $companys->count() . ' companys : function-searchCompanys');
        $response = new ApiModelResponse('Companys retrieved successfully', $companys, 200);
       
        return $response;
    }

    public function findCompany(Company $company): ApiModelResponse|ApiModelErrorResponse
    {
        Log::info(Company::class . ' Finding company : function-findCompany', ['company_id' => $company->id]);
        
        if(!$company){
            Log::warning(Company::class . ' Company not found : function-findCompany', ['company_id' => $company->id]);
            return new ApiModelErrorResponse('Company not found', new Exception(), [], 404);
        }

        Log::info(Company::class . ' Company found successfully : function-findCompany', ['company_id' => $company->id]);
        $response = new ApiModelResponse('Company found successfully', $company, 200);
       
        return $response;
    }

    public function createCompany(array $companyData){
        DB::beginTransaction();
        try {
            $company = new Company();

            Log::info(Company::class . ' Creating company : function-createCompany', ['company data' => $companyData]);

            $companyExistente = Company::where('email', $companyData['email'])->withTrashed()->first();
            if ($companyExistente && $companyExistente->trashed()) {
                // Caso exista um registro com soft delete, restaure-o
                $companyExistente->restore();
                // $companyExistente->update($companyData); -> Updated If new values ( I think it's not necessary )
    
                DB::commit();
                $response = new ApiModelResponse('Company restored successfully!', $companyExistente, 200);
                return $response;
            } elseif ($companyExistente) {
                throw new ConflictHttpException('The email is already in use.');
            }

            $company->name = $companyData['name'];
            $company->email = $companyData['email'];

            if ($company->save()) {
                Log::info(Company::class . ' Created company : function-createCompany', ['company created' => $company]);
                DB::commit();
                $response = new ApiModelResponse('Company created successfully', $company, 201);
            } else {
                Log::error(Company::class . ' Error creating company : function-createCompany', ['company data' => $companyData]);
                DB::rollBack();
                $response = new ApiModelErrorResponse('Error creating company', new Exception(), [], 500);
            }

            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error(Company::class . ' Exception creating company : function-createCompany', ['error' => $e->getMessage()]);
            return new ApiModelErrorResponse('Error creating company', $e, [], 500);
        }
    }

    public function update(Company $company, array $updatedArray){
        DB::beginTransaction();

        $company->fill($updatedArray);

        if ($company->isDirty()) {
            $statusCompany = $company->update();
            $company->contacts()->update($updatedArray['phone']);
            if ($statusCompany) {
                DB::commit();
                Log::info(Company::class . ' : Successfully updated : function-updatedCompany', ['company' => $statusCompany]);

                return new ApiModelResponse(
                    'Company updated successfully!',
                    $company,
                    200
                );
            } else {
                $e = new Exception('Unexpected error');
                DB::rollBack();
                return new ApiModelErrorResponse(
                    'Unable to edit company',
                    $e,
                    [],
                    500
                );
            }
        } else {
            Log::info(Company::class . ' : No changes made : function-updatedCompany', ['company' => $company]);
            DB::commit();
            return new ApiModelResponse(
                'No changes made',
                $company,
                200
            );
        }
    }

    public function destroy(Company $company): ApiModelResponse|ApiModelErrorResponse
    {
        try {
            Log::info(Company::class . ' : Deleting Company : function-destroy', ['company_id' => $company->id]);
            DB::beginTransaction();

            $company->delete();
            Log::info(Company::class . ' : Company deleted : function-destroy', ['company' => $company]);

            DB::commit();
            return new ApiModelResponse(
                'Company deleted successfully!',
                $company,
                200
            );

        } catch (Exception $e) {
            DB::rollBack();
            Log::error(Company::class . ' : Error deleting company : function-destroy', ['error' => $e->getMessage()]);
            return new ApiModelErrorResponse(
                'Unable to find company with this id',
                $e,
                [],
                400
            );
        }
    }
}