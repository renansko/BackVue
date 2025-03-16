<?php

namespace App\Http\Controllers\Api;

use App\Domain\Services\CompanyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;

class CompanyController extends Controller
{
    protected $companyService;
    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    public function index() {

        $response = $this->companyService->searchCompanys();

        return response()->json($response->toArray(), $response->getStatusCode());
    }

    public function show(Company $company) {
        $response = $this->companyService->findCompany($company);

        return response()->json($response->toArray(), $response->getStatusCode());
    }
    public function store(CompanyRequest $companyRequest) {

        $validatedDate = $companyRequest->validated();

        $response = $this->companyService->createCompany($validatedDate);

        return response()->json($response->toArray(), $response->getStatusCode());
    }

    public function destroy(Company $company) {

        $response = $this->companyService->destroy($company);

        return response()->json($response->toArray(), $response->getStatusCode());
    }

    public function update(CompanyRequest $companyRequest,Company $company) {

        $validatedDate = $companyRequest->validated();

        $response = $this->companyService->update($company, $validatedDate);

        return response()->json($response->toArray(), $response->getStatusCode());
    }
}
